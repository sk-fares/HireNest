
<?php

session_start();

// Only logged-in companies can post jobs
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header('Location: /hirenest/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Create a CSRF token for form protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';

$title = '';
$description = '';
$requirements = '';
$location = '';
$jobType = 'Full-time';
$salary = '';

$allowedJobTypes = [
    'Full-time',
    'Part-time',
    'Internship',
    'Contract'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $jobType = $_POST['job_type'] ?? '';
    $salary = trim($_POST['salary'] ?? '');

    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    }

    // Validate required fields
    if ($title === '') {
        $errors[] = 'Job title is required.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Job title cannot exceed 150 characters.';
    }

    if ($description === '') {
        $errors[] = 'Job description is required.';
    }

    if (!in_array($jobType, $allowedJobTypes, true)) {
        $errors[] = 'Please select a valid job type.';
    }

    // Validate optional salary
    $salaryValue = null;

    if ($salary !== '') {

        if (
            !is_numeric($salary) ||
            (float) $salary < 0 ||
            (float) $salary > 99999999.99
        ) {
            $errors[] = 'Enter a valid non-negative salary.';
        } else {
            $salaryValue = (float) $salary;
        }
    }

    if (empty($errors)) {

        try {

            // Find the company profile belonging to this account
            $companyStmt = $pdo->prepare(
                'SELECT id FROM companies WHERE user_id = ? LIMIT 1'
            );

            $companyStmt->execute([$_SESSION['user_id']]);
            $company = $companyStmt->fetch();

            if (!$company) {

                $errors[] = 'Company profile not found. Please contact the administrator.';

            } else {

                // Save the new job as active
                $insertStmt = $pdo->prepare(
                    "INSERT INTO jobs
                        (company_id, title, description, requirements,
                         location, job_type, salary, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
                );

                $insertStmt->execute([
                    $company['id'],
                    $title,
                    $description,
                    $requirements !== '' ? $requirements : null,
                    $location !== '' ? $location : null,
                    $jobType,
                    $salaryValue
                ]);

                $successMessage = 'Job posted successfully! It is now available in the job listings.';

                // Clear the form after successful submission
                $title = '';
                $description = '';
                $requirements = '';
                $location = '';
                $jobType = 'Full-time';
                $salary = '';

                // Generate a fresh token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

        } catch (PDOException $e) {

            error_log('HireNest post-job error: ' . $e->getMessage());

            $errors[] = 'Unable to post the job right now. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<main class="container">

    <section style="margin-bottom: 28px;">

        <a href="/hirenest/company/dashboard.php"
           class="btn btn-outline">
            &larr; Back to Dashboard
        </a>

        <h1 class="page-title" style="margin-top: 24px;">
            Post a Job
        </h1>

        <p class="page-subtitle">
            Share an opportunity and connect with the right candidates.
        </p>

    </section>


    <section class="card">

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


        <?php if ($successMessage !== ''): ?>

            <div class="alert alert-success" role="status">
                <?= htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <p style="margin-bottom: 20px;">
                <a href="/hirenest/jobs.php" class="btn btn-primary">
                    View Job Listings
                </a>
            </p>

        <?php endif; ?>


        <form method="POST" action="/hirenest/company/post-job.php">

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
                <label for="title">Job Title *</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-control"
                    placeholder="e.g. Junior PHP Developer"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >
            </div>


            <div class="form-group">
                <label for="description">Job Description *</label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="6"
                    placeholder="Describe the role and responsibilities..."
                    required
                ><?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>
            </div>


            <div class="form-group">
                <label for="requirements">Requirements</label>

                <textarea
                    id="requirements"
                    name="requirements"
                    class="form-control"
                    rows="5"
                    placeholder="Skills, education, and experience required..."
                ><?= htmlspecialchars(
                    $requirements,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>
            </div>


            <div class="form-group">
                <label for="location">Job Location</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    class="form-control"
                    placeholder="e.g. Kolkata, West Bengal or Remote"
                    value="<?= htmlspecialchars(
                        $location,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>


            <div class="form-group">
                <label for="job_type">Job Type *</label>

                <select
                    id="job_type"
                    name="job_type"
                    class="form-control"
                    required
                >
                    <?php foreach ($allowedJobTypes as $type): ?>
                        <option
                            value="<?= htmlspecialchars(
                                $type,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $jobType === $type ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars(
                                $type,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label for="salary">Salary (₹)</label>

                <input
                    type="number"
                    id="salary"
                    name="salary"
                    class="form-control"
                    placeholder="e.g. 25000"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    value="<?= htmlspecialchars(
                        $salary,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <p style="color: #64748B; font-size: 13px; margin-top: 6px;">
                    Optional. Enter the salary amount, not a salary range.
                </p>
            </div>


            <button type="submit" class="btn btn-primary">
                Publish Job
            </button>

        </form>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
