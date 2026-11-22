
<?php

// Start session if it has not already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id'], $_SESSION['user_role']);

$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? '';

$dashboardLinks = [
    'applicant' => '/hirenest/applicant/dashboard.php',
    'company'   => '/hirenest/company/dashboard.php',
    'admin'     => '/hirenest/admin/dashboard.php'
];

$dashboardUrl = $dashboardLinks[$userRole] ?? '/hirenest/index.php';

$roleLabels = [
    'applicant' => 'Applicant Account',
    'company'   => 'Company Account',
    'admin'     => 'Administrator'
];

$roleLabel = $roleLabels[$userRole] ?? 'Account';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HireNest - Find Your Dream Job</title>

    <link rel="stylesheet" href="/hirenest/assets/css/style.css">

</head>

<body>

<header>

    
<nav class="navbar container">

    <a href="/hirenest/index.php" class="logo">
        Hire<span style="color: #3B82F6;">Nest</span>
    </a>

    <div class="nav-links">

        <a href="/hirenest/index.php">Home</a>

        <a href="/hirenest/jobs.php">Find Jobs</a>

        <?php if ($isLoggedIn): ?>

            <div class="nav-account">

                <div class="nav-user-info">
                    <strong>
                        <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="btn btn-outline">
                    Dashboard
                </a>

                <a href="/hirenest/logout.php" class="btn btn-primary">
                    Logout
                </a>

            </div>

        <?php else: ?>

            <a href="/hirenest/login.php">Login</a>

            <a href="/hirenest/register.php" class="btn btn-primary">
                Register
            </a>

        <?php endif; ?>

    </div>

</nav>


</header>
