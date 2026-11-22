
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

// Get the company ID belonging to the logged-in user.
$stmt = $pdo->prepare("
    SELECT id
    FROM companies
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    exit("Company profile not found.");
}

$companyId = (int) $company['id'];

// Get the job ID from the URL.
$jobId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$jobId) {
    header("Location: /hirenest/company/manage-jobs.php");
    exit;
}

// Fetch the job only if it belongs to this company.
$stmt = $pdo->prepare("
    SELECT *
    FROM jobs
    WHERE id = ? AND company_id = ?
");

$stmt->execute([$jobId, $companyId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    http_response_code(404);
    exit("Job not found or you do not have permission to edit it.");
}

// Create CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Invalid request. Please refresh the page and try again.");
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $jobType = trim($_POST['job_type'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    $allowedJobTypes = [
        'Full-time',
        'Part-time',
        'Internship',
        'Contract'
    ];

    if ($title === '') {
        $errors[] = "Job title is required.";
    }

    if ($description === '') {
        $errors[] = "Job description is required.";
    }

    if (!in_array($jobType, $allowedJobTypes, true)) {
        $errors[] = "Please select a valid job type.";
    }

    if (strlen($title) > 255) {
        $errors[] = "Job title cannot exceed 255 characters.";
    }

    if ($salary !== '' && strlen($salary) > 100) {
        $errors[] = "Salary information is too long.";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            UPDATE jobs
            SET
                title = ?,
                description = ?,
                requirements = ?,
                location = ?,
                job_type = ?,
                salary = ?
            WHERE id = ? AND company_id = ?
        ");

        $stmt->execute([
            $title,
            $description,
            $requirements !== '' ? $requirements : null,
            $location !== '' ? $location : null,
            $jobType,
            $salary !== '' ? $salary : null,
            $jobId,
            $companyId
        ]);

        $_SESSION['job_message'] = [
            'type' => 'success',
            'text' => 'Job details updated successfully.'
        ];

        header("Location: /hirenest/company/manage-jobs.php");
        exit;
    }

    // Keep submitted values in the form if validation fails.
    $job['title'] = $title;
    $job['description'] = $description;
    $job['requirements'] = $requirements;
    $job['location'] = $location;
    $job['job_type'] = $jobType;
    $job['salary'] = $salary;
}

$pageTitle = "Edit Job - HireNest";

include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <p style="color: #2563eb; font-size: 13px; font-weight: 600;">
        COMPANY PANEL
    </p>

    <h1>Edit Job Posting</h1>

    <p>Update the information for your job opportunity.</p>

    <div style="margin: 25px 0;">
        <a href="/hirenest/company/manage-jobs.php"
           class="btn btn-outline">
            Back to Manage Jobs
        </a>
    </div>

    <div class="card">

        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <form method="POST"
              action="/hirenest/company/edit-job.php?id=<?= (int) $jobId ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="form-group">
                <label for="title">Job Title *</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                    maxlength="255"
                >
            </div>

            <div class="form-group">
                <label for="description">Job Description *</label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    required
                ><?= htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label for="requirements">Requirements</label>

                <textarea
                    id="requirements"
                    name="requirements"
                    rows="5"
                ><?= htmlspecialchars($job['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label for="location">Job Location</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    value="<?= htmlspecialchars($job['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <div class="form-group">
                <label for="job_type">Job Type *</label>

                <select id="job_type" name="job_type" required>

                    <option value="">Select Job Type</option>

                    <?php
                    $jobTypes = [
                        'Full-time',
                        'Part-time',
                        'Internship',
                        'Contract'
                    ];
                    ?>

                    <?php foreach ($jobTypes as $type): ?>

                        <option
                            value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $job['job_type'] === $type ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="salary">Salary</label>

                <input
                    type="text"
                    id="salary"
                    name="salary"
                    value="<?= htmlspecialchars($job['salary'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Example: ₹15,000 - ₹20,000 per month"
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Save Changes
            </button>

            <a href="/hirenest/company/manage-jobs.php"
               class="btn btn-outline">
                Cancel
            </a>

        </form>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
