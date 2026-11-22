
<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid request. Please go back and try again.');
    }

    $jobId = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$jobId || !in_array($action, ['close', 'reopen'], true)) {
        $message = 'Invalid job action.';
    } else {
        $newStatus = $action === 'close' ? 'closed' : 'active';

        $stmt = $pdo->prepare(
            "UPDATE jobs SET status = ? WHERE id = ?"
        );
        $stmt->execute([$newStatus, $jobId]);

        $message = $stmt->rowCount() > 0
            ? 'Job status updated successfully.'
            : 'No job status was changed.';
    }
}

$stmt = $pdo->query(
    "SELECT jobs.id, jobs.title, jobs.location, jobs.job_type,
            jobs.status, jobs.created_at, companies.company_name
     FROM jobs
     INNER JOIN companies ON jobs.company_id = companies.id
     ORDER BY jobs.created_at DESC"
);
$jobs = $stmt->fetchAll();

$pageTitle = 'Manage Jobs';
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Manage Jobs</h1>
    <p>View all jobs posted by companies and manage their status.</p>

    <?php if ($message): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>All Job Listings</h2>

        <?php if (empty($jobs)): ?>
            <p>No jobs have been posted yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Job Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?= (int) $job['id'] ?></td>
                                <td><?= htmlspecialchars($job['title']) ?></td>
                                <td><?= htmlspecialchars($job['company_name']) ?></td>
                                <td><?= htmlspecialchars($job['location'] ?? 'Not specified') ?></td>
                                <td><?= htmlspecialchars($job['job_type']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($job['status'])) ?></td>
                                <td>
                                    <form method="POST" action="jobs.php">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="job_id"
                                               value="<?= (int) $job['id'] ?>">

                                        <?php if ($job['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="close">
                                            <button type="submit" class="btn btn-danger">
                                                Close Job
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="reopen">
                                            <button type="submit" class="btn">
                                                Reopen Job
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p><a href="dashboard.php">Back to Admin Dashboard</a></p>
</div>

<?php require_once '../includes/footer.php'; ?>
