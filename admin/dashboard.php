
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    header("Location: /hirenest/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$adminName = $_SESSION['user_name'] ?? 'Administrator';

// Get platform statistics.
$totalUsers = (int) $pdo->query("
    SELECT COUNT(*) FROM users
")->fetchColumn();

$totalApplicants = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'applicant'
")->fetchColumn();

$totalCompanies = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'company'
")->fetchColumn();

$totalJobs = (int) $pdo->query("
    SELECT COUNT(*) FROM jobs
")->fetchColumn();

$activeJobs = (int) $pdo->query("
    SELECT COUNT(*) FROM jobs
    WHERE status = 'active'
")->fetchColumn();

$totalApplications = (int) $pdo->query("
    SELECT COUNT(*) FROM applications
")->fetchColumn();

// Fetch recent registered users.
$stmt = $pdo->query("
    SELECT id, name, email, role, is_active, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Admin Dashboard - HireNest";

include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <p style="color: #2563eb; font-size: 13px; font-weight: 600;">
        ADMINISTRATION PANEL
    </p>

    <h1>
        Welcome, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>!
    </h1>

    <p>
        Monitor and manage the HireNest job portal from one place.
    </p>

    <!-- Platform Statistics -->

    <div style="
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin: 25px 0;
    ">

        <div class="card">
            <p>Total Users</p>
            <h2><?= $totalUsers ?></h2>
        </div>

        <div class="card">
            <p>Applicants</p>
            <h2><?= $totalApplicants ?></h2>
        </div>

        <div class="card">
            <p>Companies</p>
            <h2><?= $totalCompanies ?></h2>
        </div>

        <div class="card">
            <p>Total Jobs</p>
            <h2><?= $totalJobs ?></h2>
        </div>

        <div class="card">
            <p>Active Jobs</p>
            <h2 style="color: #16a34a;">
                <?= $activeJobs ?>
            </h2>
        </div>

        <div class="card">
            <p>Total Applications</p>
            <h2><?= $totalApplications ?></h2>
        </div>

    </div>

    <!-- Admin Quick Actions -->

    <div class="card" style="margin-bottom: 25px;">

        <h2>Admin Quick Actions</h2>

        <p>Manage users, job postings and applications.</p>

        <div style="
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        ">

            <a href="/hirenest/admin/users.php"
               class="btn btn-primary">
                Manage Users
            </a>

            <a href="/hirenest/admin/jobs.php"
               class="btn btn-outline">
                Manage Jobs
            </a>

            <a href="/hirenest/admin/applications.php"
               class="btn btn-outline">
                View Applications
            </a>

        </div>

    </div>

    <!-- Recent Users -->

    <div class="card">

        <h2>Recently Registered Users</h2>

        <?php if (empty($recentUsers)): ?>

            <p>No users registered yet.</p>

        <?php else: ?>

            <div style="overflow-x: auto;">

                <table style="width: 100%; border-collapse: collapse;">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Account Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentUsers as $user): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>

                                    <?php if ((int) $user['is_active'] === 1): ?>

                                        <span style="color: #16a34a; font-weight: 600;">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span style="color: #dc2626; font-weight: 600;">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div style="margin-top: 20px;">
                <a href="/hirenest/admin/users.php"
                   class="btn btn-outline">
                    View All Users
                </a>
            </div>

        <?php endif; ?>

    </div>

</main>

<style>
@media (max-width: 768px) {
    main > div:first-of-type {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 420px) {
    main > div:first-of-type {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
