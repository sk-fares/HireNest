
<?php

session_start();

// Only logged-in applicants can apply
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'applicant'
) {
    header('Location: /hirenest/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get job ID
$jobId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$jobId || $jobId < 1) {
    header('Location: /hirenest/jobs.php');
    exit;
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';

$coverLetter = '';
$job = null;
$alreadyApplied = false;

// Fetch job details
try {

    $jobStmt = $pdo->prepare("
        SELECT j.id, j.title, j.location, j.job_type,
               c.company_name
        FROM jobs j
        INNER JOIN companies c ON j.company_id = c.id
        WHERE j.id = ? AND j.status = 'active'
        LIMIT 1
    ");

    $jobStmt->execute([$jobId]);
    $job = $jobStmt->fetch();

    if (!$job) {
        $errors[] = 'This job does not exist or is no longer available.';
    } else {

        // Check whether applicant already applied
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM applications
            WHERE job_id = ? AND applicant_id = ?
            LIMIT 1
        ");

        $checkStmt->execute([$jobId, $_SESSION['user_id']]);

        $alreadyApplied = (bool) $checkStmt->fetch();
    }

} catch (PDOException $e) {

    error_log('HireNest application page error: ' . $e->getMessage());

    $errors[] = 'Unable to load the application page. Please try again.';
}


// Handle application submission
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $job &&
    !$alreadyApplied
) {

    $coverLetter = trim($_POST['cover_letter'] ?? '');

    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid form submission. Please refresh the page.';
    }

    // Validate cover letter
    if (strlen($coverLetter) > 5000) {
        $errors[] = 'Cover letter cannot exceed 5000 characters.';
    }

    // Validate uploaded resume
    if (
        !isset($_FILES['resume']) ||
        $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $errors[] = 'Please upload your resume.';
    } elseif ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Resume upload failed. Please try again.';
    } else {

        $resume = $_FILES['resume'];

        // Maximum file size: 5 MB
        if ($resume['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Resume must be smaller than 5 MB.';
        }

        // Check file extension
        $extension = strtolower(
            pathinfo($resume['name'], PATHINFO_EXTENSION)
        );

        $allowedTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]
        ];

        if (!array_key_exists($extension, $allowedTypes)) {
            $errors[] = 'Only PDF, DOC, and DOCX files are allowed.';
        }

        // Check actual file MIME type
        if (
            empty($errors) &&
            is_uploaded_file($resume['tmp_name'])
        ) {

            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $resume['tmp_name']);
            finfo_close($fileInfo);

            if (!in_array(
                $mimeType,
                $allowedTypes[$extension],
                true
            )) {
                $errors[] = 'The uploaded file does not match its file extension.';
            }

        }
    }


    // Save application if validation passes
    if (empty($errors)) {

        $storageDirectory = __DIR__ . '/../storage/resumes/';

        if (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {

            $errors[] = 'Resume storage is unavailable. Please contact the administrator.';

        } else {

            // Generate a random filename
            $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

            $destination = $storageDirectory . $newFilename;

            // Store resume in protected folder
            if (!move_uploaded_file(
                $resume['tmp_name'],
                $destination
            )) {

                $errors[] = 'Unable to save your resume. Please try again.';

            } else {

                $relativePath = 'storage/resumes/' . $newFilename;

                try {

                    // Save application in database
                    $insertStmt = $pdo->prepare("
                        INSERT INTO applications
                            (job_id, applicant_id, resume_path, cover_letter)
                        VALUES (?, ?, ?, ?)
                    ");

                    $insertStmt->execute([
                        $jobId,
                        $_SESSION['user_id'],
                        $relativePath,
                        $coverLetter !== '' ? $coverLetter : null
                    ]);

                    // Generate a fresh security token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Flash message
                    $_SESSION['application_success'] =
                        'Your application has been submitted successfully!';

                    // Redirect to prevent duplicate submissions
                    header(
                        'Location: /hirenest/applicant/apply.php?id=' . $jobId
                    );
                    exit;

                } catch (PDOException $e) {

                    // Remove uploaded file if database insertion fails
                    if (file_exists($destination)) {
                        unlink($destination);
                    }

                    error_log('HireNest application submission error: ' . $e->getMessage());

                    if ($e->getCode() === '23000') {
                        $errors[] = 'You have already applied for this job.';
                        $alreadyApplied = true;
                    } else {
                        $errors[] = 'Unable to submit your application. Please try again.';
                    }
                }
            }
        }
    }
}


// Show success message once
if (isset($_SESSION['application_success'])) {
    $successMessage = $_SESSION['application_success'];
    unset($_SESSION['application_success']);
}

require_once __DIR__ . '/../includes/header.php';

?>

<main class="container">

    <section style="margin-bottom: 28px;">

        <a href="/hirenest/jobs.php" class="btn btn-outline">
            &larr; Back to Jobs
        </a>

        <h1 class="page-title" style="margin-top: 24px;">
            Apply for Job
        </h1>

        <p class="page-subtitle">
            Submit your application and take the next step in your career.
        </p>

    </section>


    <?php if ($job): ?>

        <section class="card" style="margin-bottom: 24px;">

            <h2 style="color: #17365D; margin-bottom: 12px;">
                <?= htmlspecialchars(
                    $job['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h2>

            <p style="margin-bottom: 8px;">
                <strong>Company:</strong>
                <?= htmlspecialchars(
                    $job['company_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p style="margin-bottom: 8px;">
                <strong>Location:</strong>
                <?= htmlspecialchars(
                    $job['location'] ?: 'Not specified',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p>
                <strong>Job Type:</strong>
                <?= htmlspecialchars(
                    $job['job_type'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </section>


        <section class="card">

            <?php if ($successMessage !== ''): ?>

                <div class="alert alert-success" role="status">
                    <?= htmlspecialchars(
                        $successMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($errors)): ?>

                <div class="alert alert-error" role="alert">

                    <ul style="margin-left: 20px;">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <?php if ($alreadyApplied): ?>

                <h2 style="color: #17365D; margin-bottom: 12px;">
                    Application Already Submitted
                </h2>

                <p style="color: #64748B; margin-bottom: 20px;">
                    You have already applied for this job.
                    You can check your application status from your dashboard.
                </p>

                <a
                    href="/hirenest/applicant/dashboard.php"
                    class="btn btn-primary"
                >
                    Go to Dashboard
                </a>


            <?php elseif ($job): ?>

                <h2 style="color: #17365D; margin-bottom: 20px;">
                    Your Application
                </h2>

                <form
                    method="POST"
                    action="/hirenest/applicant/apply.php?id=<?= (int) $jobId ?>"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <div class="form-group">

                        <label for="resume">
                            Upload Resume *
                        </label>

                        <input
                            type="file"
                            id="resume"
                            name="resume"
                            class="form-control"
                            accept=".pdf,.doc,.docx"
                            required
                        >

                        <p style="color: #64748B;
                                  font-size: 13px;
                                  margin-top: 6px;">
                            Accepted formats: PDF, DOC, DOCX.
                            Maximum size: 5 MB.
                        </p>

                    </div>


                    <div class="form-group">

                        <label for="cover_letter">
                            Cover Letter
                        </label>

                        <textarea
                            id="cover_letter"
                            name="cover_letter"
                            class="form-control"
                            rows="6"
                            maxlength="5000"
                            placeholder="Tell the company why you are suitable for this position..."
                        ><?= htmlspecialchars(
                            $coverLetter,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>


                    <button type="submit" class="btn btn-primary">
                        Submit Application
                    </button>

                </form>

            <?php endif; ?>

        </section>

    <?php else: ?>

        <section class="card">

            <div class="alert alert-error">
                This job is unavailable. Please browse other opportunities.
            </div>

            <a href="/hirenest/jobs.php" class="btn btn-primary">
                Browse Jobs
            </a>

        </section>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
