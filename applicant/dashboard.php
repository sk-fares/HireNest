
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
$userName = $_SESSION['user_name'] ?? 'Applicant';

// Get application statistics.
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Applied') AS applied,
        SUM(status = 'Shortlisted') AS shortlisted,
        SUM(status = 'Rejected') AS rejected
    FROM applications
    WHERE applicant_id = ?
");

$stmt->execute([$applicantId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the five most recent applications.
$stmt = $pdo->prepare("
    SELECT
        a.status,
        a.applied_at,
        j.title AS job_title,
        j.location,
        c.company_name
    FROM applications a
    INNER JOIN jobs j ON a.job_id = j.id
    INNER JOIN companies c ON j.company_id = c.id
    WHERE a.applicant_id = ?
    ORDER BY a.applied_at DESC, a.id DESC
    LIMIT 5
");

$stmt->execute([$applicantId]);
$recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Applicant Dashboard - HireNest";

include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <p style="color: #2563eb; font-weight: 600; font-size: 13px;">
        APPLICANT PANEL
    </p>

    <h1>
        Welcome, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>!
    </h1>

    <p>Find opportunities and keep track of your job applications.</p>

    <!-- Application Statistics -->

    <div class="dashboard-stats" style="
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px;
        margin: 25px 0;
    ">

        <div class="card">
            <p>Total Applications</p>
            <h2><?= (int) ($stats['total'] ?? 0) ?></h2>
        </div>

        <div class="card">
            <p>Applied</p>
            <h2 style="color: #2563eb;">
                <?= (int) ($stats['applied'] ?? 0) ?>
            </h2>
        </div>

        <div class="card">
            <p>Shortlisted</p>
            <h2 style="color: #16a34a;">
                <?= (int) ($stats['shortlisted'] ?? 0) ?>
            </h2>
        </div>

        <div class="card">
            <p>Rejected</p>
            <h2 style="color: #dc2626;">
                <?= (int) ($stats['rejected'] ?? 0) ?>
            </h2>
        </div>

    </div>

    <!-- Quick Actions -->

    <div class="card" style="margin-bottom: 25px;">

        <h2>Quick Actions</h2>

        <p>Manage your job search from here.</p>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">

            <a href="/hirenest/jobs.php"
               class="btn btn-primary">
                Browse Jobs
            </a>

            <a href="/hirenest/applicant/my-applications.php"
               class="btn btn-outline">
                My Applications
            </a>

            <a href="/hirenest/applicant/profile.php"
               class="btn btn-outline">
                Edit Profile
            </a>

        </div>

    </div>

    <!-- Recent Applications -->

    <div class="card">

        <h2>Your Recent Applications</h2>

        <?php if (empty($recentApplications)): ?>

            <p>You haven't applied for any jobs yet.</p>

            <a href="/hirenest/jobs.php"
               class="btn btn-primary">
                Find Jobs
            </a>

        <?php else: ?>

            <div style="overflow-x: auto;">

                <table style="width: 100%; border-collapse: collapse;">

                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Applied On</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentApplications as $application): ?>

                            <?php
                                $statusColor = match ($application['status']) {
                                    'Shortlisted' => '#16a34a',
                                    'Rejected' => '#dc2626',
                                    default => '#2563eb'
                                };
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($application['job_title'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($application['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($application['location'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($application['applied_at'])) ?>
                                </td>

                                <td>
                                    <span style="
                                        color: <?= $statusColor ?>;
                                        font-weight: 600;
                                    ">
                                        <?= htmlspecialchars($application['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div style="margin-top: 20px;">

                <a href="/hirenest/applicant/my-applications.php"
                   class="btn btn-outline">
                    View All Applications
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

<style>
@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 420px) {
    .dashboard-stats {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
