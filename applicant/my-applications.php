
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'applicant'
) {
    header("Location: /hirenest/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$applicantId = (int) $_SESSION['user_id'];

// Generate a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle application withdrawal.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Invalid request. Please refresh the page and try again.");
    }

    $applicationId = filter_input(
        INPUT_POST,
        'application_id',
        FILTER_VALIDATE_INT
    );

    if (!$applicationId) {

        $_SESSION['application_message'] = [
            'type' => 'error',
            'text' => 'Invalid application.'
        ];

    } else {

        // Only allow the logged-in applicant to withdraw
        // their own application while its status is Applied.
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = 'Withdrawn',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
              AND applicant_id = ?
              AND status = 'Applied'
        ");

        $stmt->execute([$applicationId, $applicantId]);

        if ($stmt->rowCount() > 0) {

            $_SESSION['application_message'] = [
                'type' => 'success',
                'text' => 'Your application has been withdrawn successfully.'
            ];

        } else {

            $_SESSION['application_message'] = [
                'type' => 'error',
                'text' => 'This application cannot be withdrawn. It may already have been processed.'
            ];

        }
    }

    header("Location: /hirenest/applicant/my-applications.php");
    exit;
}

// Fetch all applications belonging to this applicant.
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.status,
        a.applied_at,
        j.id AS job_id,
        j.title AS job_title,
        j.location,
        j.job_type,
        j.status AS job_status,
        c.company_name
    FROM applications a
    INNER JOIN jobs j ON a.job_id = j.id
    INNER JOIN companies c ON j.company_id = c.id
    WHERE a.applicant_id = ?
    ORDER BY a.applied_at DESC, a.id DESC
");

$stmt->execute([$applicantId]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "My Applications - HireNest";

include __DIR__ . '/../includes/header.php';

$message = $_SESSION['application_message'] ?? null;
unset($_SESSION['application_message']);
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <h1>My Applications</h1>

    <p>Track and manage your submitted job applications.</p>

    <div style="margin: 25px 0; display: flex; gap: 12px; flex-wrap: wrap;">

        <a href="/hirenest/applicant/dashboard.php"
           class="btn btn-outline">
            Back to Dashboard
        </a>

        <a href="/hirenest/jobs.php"
           class="btn btn-primary">
            Browse More Jobs
        </a>

    </div>

    <?php if ($message): ?>

        <div class="alert <?= $message['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <?php if (empty($applications)): ?>

        <div class="card">

            <h2>No Applications Yet</h2>

            <p>
                You haven't applied for any jobs yet.
                Browse available opportunities and submit
                your first application.
            </p>

            <a href="/hirenest/jobs.php"
               class="btn btn-primary">
                Find Jobs
            </a>

        </div>

    <?php else: ?>

        <div class="card">

            <h2>
                Total Applications: <?= count($applications) ?>
            </h2>

            <div style="overflow-x: auto;">

                <table style="width: 100%; border-collapse: collapse;">

                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Job Type</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($applications as $application): ?>

                            <?php
                                $status = $application['status'];

                                $statusColor = match ($status) {
                                    'Shortlisted' => '#16a34a',
                                    'Rejected' => '#dc2626',
                                    'Withdrawn' => '#64748b',
                                    default => '#2563eb'
                                };
                            ?>

                            <tr>

                                <td>
                                    <?php if ($application['job_status'] === 'active'): ?>

                                        <a href="/hirenest/job-details.php?id=<?= (int) $application['job_id'] ?>">
                                            <?= htmlspecialchars($application['job_title'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>

                                    <?php else: ?>

                                        <?= htmlspecialchars($application['job_title'], ENT_QUOTES, 'UTF-8') ?>
                                        <small>(Closed)</small>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($application['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($application['location'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($application['job_type'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd M Y',
                                        strtotime($application['applied_at'])
                                    ) ?>
                                </td>

                                <td>
                                    <span style="
                                        color: <?= $statusColor ?>;
                                        font-weight: 600;
                                    ">
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>

                                    <?php if ($status === 'Applied'): ?>

                                        <form
                                            method="POST"
                                            action="/hirenest/applicant/my-applications.php"
                                            onsubmit="return confirm('Are you sure you want to withdraw this application?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int) $application['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-outline"
                                                style="color: #dc2626; border-color: #dc2626;"
                                            >
                                                Withdraw
                                            </button>

                                        </form>

                                    <?php elseif ($status === 'Withdrawn'): ?>

                                        <span style="color: #64748b;">
                                            Withdrawn
                                        </span>

                                    <?php else: ?>

                                        <span style="color: #64748b;">
                                            Not Available
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
