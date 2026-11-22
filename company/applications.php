
<?php

session_start();

// Only logged-in companies can access this page
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header('Location: /hirenest/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$applications = [];
$errorMessage = '';
$successMessage = '';

$allowedStatuses = [
    'Applied',
    'Shortlisted',
    'Rejected'
];

try {

    // Find the company profile
    $companyStmt = $pdo->prepare("
        SELECT id
        FROM companies
        WHERE user_id = ?
        LIMIT 1
    ");

    $companyStmt->execute([$_SESSION['user_id']]);
    $company = $companyStmt->fetch();

    if (!$company) {

        $errorMessage = 'Company profile not found.';

    } else {

        // Handle application status update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $applicationId = filter_input(
                INPUT_POST,
                'application_id',
                FILTER_VALIDATE_INT
            );

            $newStatus = $_POST['status'] ?? '';
            $submittedToken = $_POST['csrf_token'] ?? '';

            // Verify CSRF token
            if (
                !is_string($submittedToken) ||
                !hash_equals($_SESSION['csrf_token'], $submittedToken)
            ) {

                $errorMessage = 'Invalid form submission. Please refresh the page.';

            } elseif (!$applicationId || $applicationId < 1) {

                $errorMessage = 'Invalid application selected.';

            } elseif (!in_array($newStatus, $allowedStatuses, true)) {

                $errorMessage = 'Please select a valid application status.';

            } else {

                // Update only applications belonging to this company
                $updateStmt = $pdo->prepare("
                    UPDATE applications a
                    INNER JOIN jobs j ON a.job_id = j.id
                    SET a.status = ?
                    WHERE a.id = ?
                    AND j.company_id = ?
                ");

                $updateStmt->execute([
                    $newStatus,
                    $applicationId,
                    $company['id']
                ]);

                if ($updateStmt->rowCount() >= 0) {

                    // Confirm that the application belongs to this company
                    $verifyStmt = $pdo->prepare("
                        SELECT a.id
                        FROM applications a
                        INNER JOIN jobs j ON a.job_id = j.id
                        WHERE a.id = ?
                        AND j.company_id = ?
                        LIMIT 1
                    ");

                    $verifyStmt->execute([
                        $applicationId,
                        $company['id']
                    ]);

                    if ($verifyStmt->fetch()) {

                        $_SESSION['application_status_success'] =
                            'Application status updated successfully!';

                        // Generate a fresh CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        // Redirect to prevent duplicate form submission
                        header('Location: /hirenest/company/applications.php');
                        exit;

                    } else {

                        $errorMessage = 'Application not found or you are not authorized to update it.';

                    }

                }
            }
        }


        // Fetch success message once
        if (isset($_SESSION['application_status_success'])) {

            $successMessage = $_SESSION['application_status_success'];

            unset($_SESSION['application_status_success']);
        }


        // Fetch all applications belonging to this company
        $applicationStmt = $pdo->prepare("
            SELECT
                a.id AS application_id,
                a.cover_letter,
                a.resume_path,
                a.status,
                a.applied_at,
                j.id AS job_id,
                j.title AS job_title,
                u.name AS applicant_name,
                u.email AS applicant_email,
                p.phone AS applicant_phone,
                p.skills AS applicant_skills,
                p.education AS applicant_education,
                p.experience AS applicant_experience
            FROM applications a

            INNER JOIN jobs j
                ON a.job_id = j.id

            INNER JOIN users u
                ON a.applicant_id = u.id

            LEFT JOIN applicant_profiles p
                ON a.applicant_id = p.user_id

            WHERE j.company_id = ?

            ORDER BY a.applied_at DESC
        ");

        $applicationStmt->execute([$company['id']]);
        $applications = $applicationStmt->fetchAll();

    }

} catch (PDOException $e) {

    error_log('HireNest applications management error: ' . $e->getMessage());

    $errorMessage = 'Unable to process applications right now. Please try again later.';
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
            Received Applications
        </h1>

        <p class="page-subtitle">
            Review candidates and manage their application status.
        </p>

    </section>


    <?php if ($successMessage !== ''): ?>

        <div class="alert alert-success" role="status">
            <?= htmlspecialchars(
                $successMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars(
                $errorMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php elseif (empty($applications)): ?>

        <section class="card" style="text-align: center;">

            <h2 style="color: #17365D; margin-bottom: 12px;">
                No Applications Yet
            </h2>

            <p style="color: #64748B;">
                Applications will appear here when candidates apply.
            </p>

        </section>

    <?php else: ?>

        <p class="page-subtitle">
            Total Applications: <?= count($applications) ?>
        </p>


        <?php foreach ($applications as $application): ?>

            <article class="card" style="margin-bottom: 24px;">

                <!-- Job information -->
                <div style="margin-bottom: 20px;">

                    <p style="color: #3B82F6;
                              font-size: 13px;
                              font-weight: 700;
                              margin-bottom: 6px;">
                        APPLICATION FOR
                    </p>

                    <h2 style="color: #17365D;
                               font-size: 22px;
                               margin-bottom: 8px;">

                        <?= htmlspecialchars(
                            $application['job_title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h2>

                    <p style="color: #64748B; font-size: 13px;">
                        Applied on
                        <?= htmlspecialchars(
                            date(
                                'd M Y, h:i A',
                                strtotime($application['applied_at'])
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <hr style="border: 0;
                           border-top: 1px solid #E2E8F0;
                           margin-bottom: 20px;">


                <!-- Applicant information -->
                <h3 style="color: #17365D; margin-bottom: 12px;">
                    Applicant Information
                </h3>

                <p style="margin-bottom: 8px;">
                    <strong>Name:</strong>
                    <?= htmlspecialchars(
                        $application['applicant_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p style="margin-bottom: 8px;">
                    <strong>Email:</strong>
                    <?= htmlspecialchars(
                        $application['applicant_email'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p style="margin-bottom: 8px;">
                    <strong>Phone:</strong>
                    <?= htmlspecialchars(
                        $application['applicant_phone'] ?: 'Not provided',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <!-- Skills -->
                <div style="margin-top: 20px;">

                    <h3 style="color: #17365D; margin-bottom: 8px;">
                        Skills
                    </h3>

                    <p style="color: #64748B; white-space: pre-line;">
                        <?= htmlspecialchars(
                            $application['applicant_skills'] ?: 'Not provided',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <!-- Education -->
                <div style="margin-top: 20px;">

                    <h3 style="color: #17365D; margin-bottom: 8px;">
                        Education
                    </h3>

                    <p style="color: #64748B; white-space: pre-line;">
                        <?= htmlspecialchars(
                            $application['applicant_education'] ?: 'Not provided',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <!-- Experience -->
                <div style="margin-top: 20px;">

                    <h3 style="color: #17365D; margin-bottom: 8px;">
                        Experience
                    </h3>

                    <p style="color: #64748B; white-space: pre-line;">
                        <?= htmlspecialchars(
                            $application['applicant_experience'] ?: 'Not provided',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <!-- Cover letter -->
                <div style="margin-top: 20px;">

                    <h3 style="color: #17365D; margin-bottom: 8px;">
                        Cover Letter
                    </h3>

                    <p style="color: #64748B; white-space: pre-line;">
                        <?= htmlspecialchars(
                            $application['cover_letter'] ?: 'No cover letter provided.',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </div>


                <!-- Current application status -->
                <div style="margin-top: 20px;">

                    <h3 style="color: #17365D; margin-bottom: 8px;">
                        Current Status
                    </h3>

                    <span class="status-<?= strtolower(
                        $application['status']
                    ) ?>">
                        <?= htmlspecialchars(
                            $application['status'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </div>


                <!-- Update application status -->
                <div style="margin-top: 24px;
                            padding-top: 20px;
                            border-top: 1px solid #E2E8F0;">

                    <h3 style="color: #17365D; margin-bottom: 12px;">
                        Manage Application
                    </h3>

                    <form
                        method="POST"
                        action="/hirenest/company/applications.php"
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

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= (int) $application['application_id'] ?>"
                        >

                        <div class="form-group">

                            <label for="status-<?= (int) $application['application_id'] ?>">
                                Change Application Status
                            </label>

                            <select
                                id="status-<?= (int) $application['application_id'] ?>"
                                name="status"
                                class="form-control"
                                required
                            >

                                <?php foreach ($allowedStatuses as $status): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= $application['status'] === $status
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <button type="submit" class="btn btn-primary">
                            Update Status
                        </button>

                    </form>

                </div>


                
<div style="margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;">

    <h3 style="color: #17365D; margin-bottom: 12px;">
        Applicant Resume
    </h3>

    <p style="color: #64748B; margin-bottom: 16px;">
        Resume file:
        <?= htmlspecialchars(
            basename($application['resume_path']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <a
        href="/hirenest/company/download-resume.php?id=<?= (int) $application['application_id'] ?>"
        class="btn btn-primary"
    >
        Download Resume
    </a>

</div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
