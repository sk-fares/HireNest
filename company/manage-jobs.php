
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'company'
) {
    header("Location: /hirenest/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

// Fetch company information.
$stmt = $pdo->prepare("
    SELECT id, company_name
    FROM companies
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    exit("Company profile not found.");
}

$companyId = (int) $company['id'];

// Generate CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle closing and reopening jobs.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Invalid request. Please refresh the page and try again.");
    }

    $jobId = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$jobId || !in_array($action, ['close', 'reopen'], true)) {

        $_SESSION['job_message'] = [
            'type' => 'error',
            'text' => 'Invalid job action.'
        ];

    } else {

        $newStatus = ($action === 'close') ? 'closed' : 'active';

        $stmt = $pdo->prepare("
            UPDATE jobs
            SET status = ?
            WHERE id = ? AND company_id = ?
        ");

        $stmt->execute([$newStatus, $jobId, $companyId]);

        if ($stmt->rowCount() > 0) {

            $_SESSION['job_message'] = [
                'type' => 'success',
                'text' => 'Job status updated successfully.'
            ];

        } else {

            $_SESSION['job_message'] = [
                'type' => 'error',
                'text' => 'No changes were made. The job may already have this status.'
            ];

        }
    }

    header("Location: /hirenest/company/manage-jobs.php");
    exit;
}

// Fetch all jobs belonging to this company.
$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        location,
        job_type,
        salary,
        status,
        created_at
    FROM jobs
    WHERE company_id = ?
    ORDER BY created_at DESC, id DESC
");

$stmt->execute([$companyId]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Manage Jobs - HireNest";

include __DIR__ . '/../includes/header.php';

$message = $_SESSION['job_message'] ?? null;
unset($_SESSION['job_message']);
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <p style="color: #2563eb; font-size: 13px; font-weight: 600;">
        COMPANY PANEL
    </p>

    <h1>Manage Your Jobs</h1>

    <p>View, edit, close and reopen your job postings.</p>

    <div style="margin: 25px 0; display: flex; gap: 12px; flex-wrap: wrap;">

        <a href="/hirenest/company/dashboard.php"
           class="btn btn-outline">
            Back to Dashboard
        </a>

        <a href="/hirenest/company/post-job.php"
           class="btn btn-primary">
            + Post a New Job
        </a>

    </div>

    <?php if ($message): ?>

        <div class="alert <?= $message['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <div class="card">

        <h2>Your Job Postings (<?= count($jobs) ?>)</h2>

        <?php if (empty($jobs)): ?>

            <p>You haven't posted any jobs yet.</p>

            <a href="/hirenest/company/post-job.php"
               class="btn btn-primary">
                Post Your First Job
            </a>

        <?php else: ?>

            <div style="overflow-x: auto;">

                <table style="width: 100%; border-collapse: collapse;">

                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Location</th>
                            <th>Job Type</th>
                            <th>Salary</th>
                            <th>Posted On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($jobs as $job): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['location'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['job_type'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($job['salary'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($job['created_at'])) ?>
                                </td>

                                <td>

                                    <?php if ($job['status'] === 'active'): ?>

                                        <span style="color: #16a34a; font-weight: 600;">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span style="color: #dc2626; font-weight: 600;">
                                            Closed
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">

                                        <a
                                            href="/hirenest/company/edit-job.php?id=<?= (int) $job['id'] ?>"
                                            class="btn btn-outline"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="/hirenest/company/manage-jobs.php"
                                            onsubmit="return confirm('Are you sure you want to change this job status?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="job_id"
                                                value="<?= (int) $job['id'] ?>"
                                            >

                                            <?php if ($job['status'] === 'active'): ?>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="close"
                                                    class="btn btn-outline"
                                                >
                                                    Close Job
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="reopen"
                                                    class="btn btn-primary"
                                                >
                                                    Reopen Job
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
