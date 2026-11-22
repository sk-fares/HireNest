
<?php

// Configure secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Database connection
require_once __DIR__ . '/config/database.php';

// Role-specific dashboard links
$dashboardLinks = [
    'applicant' => '/hirenest/applicant/dashboard.php',
    'company'   => '/hirenest/company/dashboard.php',
    'admin'     => '/hirenest/admin/dashboard.php'
];

// Redirect already logged-in users to their dashboard
if (isset($_SESSION['user_id'], $_SESSION['user_role'])) {

    $currentRole = $_SESSION['user_role'];

    if (isset($dashboardLinks[$currentRole])) {
        header('Location: ' . $dashboardLinks[$currentRole]);
        exit;
    }
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';

$email = '';
$selectedRole = 'applicant';

$allowedRoles = [
    'applicant',
    'company',
    'admin'
];

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? '';

    $submittedToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {

        $errorMessage = 'Invalid form submission. Please refresh the page.';

    } elseif (!in_array($selectedRole, $allowedRoles, true)) {

        $errorMessage = 'Please select a valid account type.';

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !is_string($password) ||
        $password === ''
    ) {

        $errorMessage = 'Please enter a valid email and password.';

    } else {

        try {

            // Find account by email
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, role, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            $user = $stmt->fetch();

            // Check password AND selected account role
            if (
                $user &&
                password_verify($password, $user['password']) &&
                $user['role'] === $selectedRole &&
                (int) $user['is_active'] === 1
            ) {

                // Regenerate session ID after successful login
                session_regenerate_id(true);

                // Store account information in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Generate a fresh CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Redirect according to account role
                header('Location: ' . $dashboardLinks[$user['role']]);
                exit;

            } else {

                $errorMessage = 'Incorrect email, password, or account type. Please try again.';

            }

        } catch (PDOException $e) {

            error_log('HireNest login error: ' . $e->getMessage());

            $errorMessage = 'Unable to log in right now. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<main class="container">

    <section style="max-width: 480px;
                    margin: 50px auto;">

        <div class="card">

            <div style="text-align: center;
                        margin-bottom: 28px;">

                <h1 class="page-title">
                    Welcome Back
                </h1>

                <p class="page-subtitle" style="margin-bottom: 0;">
                    Log in to your HireNest account.
                </p>

            </div>


            <?php if ($errorMessage !== ''): ?>

                <div class="alert alert-error" role="alert">

                    <?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <form method="POST" action="/hirenest/login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <!-- Account type selection -->
                <div class="form-group">

                    <label for="role">
                        Select Account Type
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="form-control"
                        required
                    >

                        <option
                            value="applicant"
                            <?= $selectedRole === 'applicant' ? 'selected' : '' ?>
                        >
                            Applicant
                        </option>

                        <option
                            value="company"
                            <?= $selectedRole === 'company' ? 'selected' : '' ?>
                        >
                            Company
                        </option>

                        <option
                            value="admin"
                            <?= $selectedRole === 'admin' ? 'selected' : '' ?>
                        >
                            Admin
                        </option>

                    </select>

                </div>


                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>


                <!-- Password -->
                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width: 100%;"
                >
                    Login
                </button>

            </form>


            <p style="text-align: center;
                      margin-top: 22px;
                      color: #64748B;">

                Don't have an account?

                <a href="/hirenest/register.php">
                    Register here
                </a>

            </p>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
