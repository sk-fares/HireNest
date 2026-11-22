
<?php

require_once __DIR__ . '/config/database.php';

$errors = [];
$success = '';

$name = '';
$email = '';
$role = 'applicant';
$companyName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and validate form input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'applicant';
    $companyName = trim($_POST['company_name'] ?? '');

    // Validate name
    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Please enter a name (maximum 100 characters).';
    }

    // Validate email
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    // Only allow public registration for these two roles
    if (!in_array($role, ['applicant', 'company'], true)) {
        $errors[] = 'Please select a valid account type.';
    }

    // Company-specific validation
    if ($role === 'company') {
        if ($companyName === '' || strlen($companyName) > 150) {
            $errors[] = 'Please enter a company name (maximum 150 characters).';
        }
    }

    // Save the account if validation succeeds
    if (empty($errors)) {

        try {
            $pdo->beginTransaction();

            // Check whether this email is already registered
            $check = $pdo->prepare(
                'SELECT id FROM users WHERE email = ?'
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $pdo->rollBack();
                $errors[] = 'This email is already registered.';
            } else {

                // Hash the password before storing it
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Create the user account
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role)
                     VALUES (?, ?, ?, ?)'
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hashedPassword,
                    $role
                ]);

                $userId = (int) $pdo->lastInsertId();

                // Create the matching profile
                if ($role === 'applicant') {

                    $profile = $pdo->prepare(
                        'INSERT INTO applicant_profiles (user_id)
                         VALUES (?)'
                    );

                    $profile->execute([$userId]);

                } else {

                    $profile = $pdo->prepare(
                        'INSERT INTO companies (user_id, company_name)
                         VALUES (?, ?)'
                    );

                    $profile->execute([
                        $userId,
                        $companyName
                    ]);
                }

                $pdo->commit();

                $success = 'Registration successful! You can now log in.';

                // Clear submitted form values
                $name = '';
                $email = '';
                $role = 'applicant';
                $companyName = '';
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Keep database details out of the public page
            error_log('HireNest registration error: ' . $e->getMessage());

            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<main class="container">

    <div style="max-width: 560px; margin: 0 auto;">

        <h1 class="page-title">Create your HireNest account</h1>

        <p class="page-subtitle">
            Join HireNest to discover opportunities or hire talent.
        </p>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>

                <p style="margin-top: 8px;">
                    <a href="/hirenest/login.php">Go to Login</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <ul style="padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">

            <form method="POST" action="/hirenest/register.php">

                <div class="form-group">
                    <label for="name">Full name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        maxlength="150"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="role">I want to register as</label>

                    <select
                        id="role"
                        name="role"
                        class="form-control"
                        required
                    >
                        <option
                            value="applicant"
                            <?= $role === 'applicant' ? 'selected' : '' ?>
                        >
                            Applicant / Job Seeker
                        </option>

                        <option
                            value="company"
                            <?= $role === 'company' ? 'selected' : '' ?>
                        >
                            Company / Employer
                        </option>
                    </select>
                </div>

                <div
                    class="form-group"
                    id="company-field"
                    <?= $role === 'company' ? '' : 'hidden' ?>
                >
                    <label for="company_name">Company name</label>

                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        class="form-control"
                        maxlength="150"
                        value="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="organization"
                        <?= $role === 'company' ? 'required' : '' ?>
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <small style="color: #64748B;">
                        Use at least 8 characters.
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width: 100%;">
                    Create Account
                </button>

            </form>

            <p style="margin-top: 20px; text-align: center; color: #64748B;">
                Already have an account?
                <a href="/hirenest/login.php">Log in</a>
            </p>

        </div>

    </div>

</main>

<script>
    const roleSelect = document.getElementById('role');
    const companyField = document.getElementById('company-field');
    const companyNameInput = document.getElementById('company_name');

    function updateCompanyField() {
        const isCompany = roleSelect.value === 'company';

        companyField.hidden = !isCompany;
        companyNameInput.required = isCompany;
    }

    roleSelect.addEventListener('change', updateCompanyField);
    updateCompanyField();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
