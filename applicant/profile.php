
<?php

session_start();

// Only logged-in applicants can access this page
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'applicant'
) {
    header('Location: /hirenest/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';

$phone = '';
$skills = '';
$education = '';
$experience = '';

// Show success message only once
if (isset($_SESSION['profile_success'])) {
    $successMessage = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

// Fetch applicant profile
try {

    $stmt = $pdo->prepare("
        SELECT phone, skills, education, experience
        FROM applicant_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    if ($profile) {
        $phone = $profile['phone'] ?? '';
        $skills = $profile['skills'] ?? '';
        $education = $profile['education'] ?? '';
        $experience = $profile['experience'] ?? '';
    } else {
        $errors[] = 'Applicant profile not found. Please contact the administrator.';
    }

} catch (PDOException $e) {

    error_log('HireNest profile fetch error: ' . $e->getMessage());

    $profile = null;
    $errors[] = 'Unable to load your profile right now.';
}


// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile) {

    $phone = trim($_POST['phone'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');

    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Invalid form submission. Please refresh the page.';
    }

    // Validate phone number
    if (
        $phone !== '' &&
        !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)
    ) {
        $errors[] = 'Please enter a valid phone number.';
    }

    // Update database if validation passes
    if (empty($errors)) {

        try {

            $updateStmt = $pdo->prepare("
                UPDATE applicant_profiles
                SET phone = ?,
                    skills = ?,
                    education = ?,
                    experience = ?
                WHERE user_id = ?
            ");

            $updateStmt->execute([
                $phone !== '' ? $phone : null,
                $skills !== '' ? $skills : null,
                $education !== '' ? $education : null,
                $experience !== '' ? $experience : null,
                $_SESSION['user_id']
            ]);

            // Generate a new security token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            
            
            
            
            // Store success message temporarily
            $_SESSION['profile_success'] = 'Your profile has been updated successfully!';

            // Redirect to a fresh GET request              
            header('Location: /hirenest/applicant/profile.php');
            exit;





        } catch (PDOException $e) {

            error_log('HireNest profile update error: ' . $e->getMessage());

            $errors[] = 'Unable to update your profile. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<main class="container">

    <section style="margin-bottom: 28px;">

        <a href="/hirenest/applicant/dashboard.php"
           class="btn btn-outline">
            &larr; Back to Dashboard
        </a>

        <h1 class="page-title" style="margin-top: 24px;">
            My Applicant Profile
        </h1>

        <p class="page-subtitle">
            Add your information to help companies understand your background.
        </p>

    </section>


    <section class="card">

        <?php if (!empty($errors)): ?>

            <div class="alert alert-error" role="alert">

                <ul style="margin-left: 20px;">

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($successMessage !== ''): ?>

            <div class="alert alert-success" role="status">
                <?= htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>


        <?php if ($profile): ?>

            <form method="POST" action="/hirenest/applicant/profile.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <div class="form-group">

                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control"
                        placeholder="e.g. 9876543210"
                        value="<?= htmlspecialchars(
                            $phone,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="skills">Skills</label>

                    <textarea
                        id="skills"
                        name="skills"
                        class="form-control"
                        rows="4"
                        placeholder="e.g. PHP, MySQL, HTML, CSS, JavaScript"
                    ><?= htmlspecialchars(
                        $skills,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <div class="form-group">

                    <label for="education">Education</label>

                    <textarea
                        id="education"
                        name="education"
                        class="form-control"
                        rows="4"
                        placeholder="Mention your degree, college, and academic details..."
                    ><?= htmlspecialchars(
                        $education,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <div class="form-group">

                    <label for="experience">Experience</label>

                    <textarea
                        id="experience"
                        name="experience"
                        class="form-control"
                        rows="5"
                        placeholder="Mention internships, projects, work experience, or relevant activities..."
                    ><?= htmlspecialchars(
                        $experience,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <button type="submit" class="btn btn-primary">
                    Save Profile
                </button>

            </form>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
