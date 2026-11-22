
<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT
        applications.id,
        applications.status,
        applications.applied_at,
        jobs.title AS job_title,
        companies.company_name,
        users.name AS applicant_name,
        users.email AS applicant_email
     FROM applications
     INNER JOIN jobs ON applications.job_id = jobs.id
     INNER JOIN companies ON jobs.company_id = companies.id
     INNER JOIN users ON applications.applicant_id = users.id
     ORDER BY applications.applied_at DESC"
);

$applications = $stmt->fetchAll();

$pageTitle = 'Manage Applications';
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Manage Applications</h1>
    <p>View applications submitted by applicants for jobs posted on HireNest.</p>

    <div class="card">
        <h2>All Applications</h2>

        <?php if (empty($applications)): ?>
            <p>No applications have been submitted yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Email</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Applied On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td><?= (int) $application['id'] ?></td>
                                <td><?= htmlspecialchars($application['applicant_name']) ?></td>
                                <td><?= htmlspecialchars($application['applicant_email']) ?></td>
                                <td><?= htmlspecialchars($application['job_title']) ?></td>
                                <td><?= htmlspecialchars($application['company_name']) ?></td>
                                <td><?= htmlspecialchars($application['status']) ?></td>
                                <td>
                                    <?= htmlspecialchars(date(
                                        'd M Y',
                                        strtotime($application['applied_at'])
                                    )) ?>
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
