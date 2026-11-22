
<?php

require_once __DIR__ . '/config/database.php';

// Get the job ID from the URL
$jobId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$job = null;
$errorMessage = '';

if (!$jobId || $jobId < 1) {
    $errorMessage = 'Invalid job ID. Please select a job from the listings.';
} else {
    try {

        // Fetch the job and its company details
        $sql = "
            SELECT
                j.id,
                j.title,
                j.description,
                j.requirements,
                j.location,
                j.job_type,
                j.salary,
                j.created_at,
                c.company_name,
                c.description AS company_description,
                c.website,
                c.location AS company_location
            FROM jobs j
            INNER JOIN companies c ON j.company_id = c.id
            WHERE j.id = ? AND j.status = 'active'
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jobId]);

        $job = $stmt->fetch();

        if (!$job) {
            $errorMessage = 'This job does not exist or is no longer available.';
        }

    } catch (PDOException $e) {

        error_log('HireNest job details error: ' . $e->getMessage());

        $errorMessage = 'Unable to load this job right now. Please try again later.';
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<main class="container">

    <p style="margin-bottom: 24px;">
        <a href="/hirenest/jobs.php" class="btn btn-outline">
            &larr; Back to Jobs
        </a>
    </p>

    <?php if ($errorMessage !== ''): ?>

        <section class="card">

            <h1 class="page-title">Job Details</h1>

            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <a href="/hirenest/jobs.php" class="btn btn-primary">
                Browse Available Jobs
            </a>

        </section>

    <?php else: ?>

        <section class="card" style="margin-bottom: 24px;">

            <p style="color: #3B82F6;
                      font-weight: 700;
                      font-size: 14px;
                      margin-bottom: 8px;">
                <?= htmlspecialchars(
                    $job['company_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <h1 class="page-title">
                <?= htmlspecialchars(
                    $job['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p class="page-subtitle">
                <?= htmlspecialchars(
                    $job['location'] ?: 'Location not specified',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
                &bull;
                <?= htmlspecialchars(
                    $job['job_type'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p style="margin-bottom: 12px;">
                <strong>Salary:</strong>

                <?php if ($job['salary'] !== null): ?>
                    ₹<?= number_format((float) $job['salary'], 2) ?>
                <?php else: ?>
                    Not specified
                <?php endif; ?>
            </p>

            <p style="color: #64748B; font-size: 14px;">
                Posted on
                <?= htmlspecialchars(
                    date('d M Y', strtotime($job['created_at'])),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </section>


        <section class="card" style="margin-bottom: 24px;">

            <h2 style="color: #17365D; margin-bottom: 16px;">
                Job Description
            </h2>

            <div style="line-height: 1.8; white-space: pre-line;">
                <?= htmlspecialchars(
                    $job['description'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        </section>


        <section class="card" style="margin-bottom: 24px;">

            <h2 style="color: #17365D; margin-bottom: 16px;">
                Requirements
            </h2>

            <div style="line-height: 1.8; white-space: pre-line;">
                <?php if (!empty($job['requirements'])): ?>

                    <?= htmlspecialchars(
                        $job['requirements'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                <?php else: ?>

                    No specific requirements have been provided.

                <?php endif; ?>
            </div>

        </section>


        <section class="card" style="margin-bottom: 24px;">

            <h2 style="color: #17365D; margin-bottom: 16px;">
                About the Company
            </h2>

            <h3 style="margin-bottom: 12px;">
                <?= htmlspecialchars(
                    $job['company_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h3>

            <div style="line-height: 1.8; white-space: pre-line;">
                <?php if (!empty($job['company_description'])): ?>

                    <?= htmlspecialchars(
                        $job['company_description'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                <?php else: ?>

                    Company information has not been provided yet.

                <?php endif; ?>
            </div>

            <?php if (!empty($job['website'])): ?>

                <p style="margin-top: 16px;">
                    <strong>Website:</strong>

                    <a
                        href="<?= htmlspecialchars(
                            $job['website'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= htmlspecialchars(
                            $job['website'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </a>
                </p>

            <?php endif; ?>

            <?php if (!empty($job['company_location'])): ?>

                <p style="margin-top: 8px;">
                    <strong>Company Location:</strong>
                    <?= htmlspecialchars(
                        $job['company_location'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            <?php endif; ?>

        </section>


        <section class="card" style="text-align: center;">

            <h2 style="color: #17365D; margin-bottom: 12px;">
                Interested in this opportunity?
            </h2>

            <p style="color: #64748B; margin-bottom: 20px;">
                The job application feature will be connected in a later step.
            </p>

           <a
                href="/hirenest/applicant/apply.php?id=<?= (int) $job['id'] ?>"
                class="btn btn-primary"
            >
                Apply for Job
            </a>

        </section>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
