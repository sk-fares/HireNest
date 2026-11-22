
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

// Get job statistics.
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_jobs,
        SUM(status = 'active') AS active_jobs,
        SUM(status = 'closed') AS closed_jobs
    FROM jobs
    WHERE company_id = ?
");

$stmt->execute([$companyId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the five most recent jobs.
$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        location,
        job_type,
        status,
        created_at
    FROM jobs
    WHERE company_id = ?
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");

$stmt->execute([$companyId]);
$recentJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Company Dashboard - HireNest";

include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <p style="color: #2563eb; font-size: 13px; font-weight: 600;">
        COMPANY PANEL
    </p>

    <h1>
        Welcome,
        <?= htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8') ?>!
    </h1>

    <p>Manage your job postings and connect with potential candidates.</p>

    <!-- Job Statistics -->

    <div style="
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin: 25px 0;
    ">

        <div class="card">
            <p>Total Jobs</p>
            <h2><?= (int) ($stats['total_jobs'] ?? 0) ?></h2>
        </div>

        <div class="card">
            <p>Active Jobs</p>
            <h2 style="color: #16a34a;">
                <?= (int) ($stats['active_jobs'] ?? 0) ?>
            </h2>
        </div>

        <div class="card">
            <p>Closed Jobs</p>
            <h2 style="color: #dc2626;">
                <?= (int) ($stats['closed_jobs'] ?? 0) ?>
            </h2>
        </div>

    </div>

    <!-- Quick Actions -->

    <div class="card" style="margin-bottom: 25px;">

        <h2>Quick Actions</h2>

        <p>Manage your opportunities from here.</p>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">

            <a href="/hirenest/company/post-job.php"
               class="btn btn-primary">
                + Post a New Job
            </a>

            <a href="/hirenest/company/manage-jobs.php"
               class="btn btn-outline">
                Manage Jobs
            </a>

            <a href="/hirenest/company/applications.php"
               class="btn btn-outline">
                Manage Applications
            </a>

            <a href="/hirenest/jobs.php"
               class="btn btn-outline">
                Browse Job Listings
            </a>

        </div>

    </div>

    <!-- Recent Job Postings -->

    <div class="card">

        <h2>Your Recent Job Postings</h2>

        <?php if (empty($recentJobs)): ?>

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
                            <th>Type</th>
                            <th>Status</th>
                            <th>Posted On</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentJobs as $job): ?>

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
                                    <?= date('d M Y', strtotime($job['created_at'])) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

<style>
@media (max-width: 768px) {
    main > div:nth-of-type(1) {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
