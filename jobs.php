
<?php

require_once __DIR__ . '/config/database.php';

// Read search filters from the URL
$keyword = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');

$jobs = [];
$errorMessage = '';

// Fetch active jobs and their company information
try {

    $sql = "
        SELECT
            j.id,
            j.title,
            j.description,
            j.location,
            j.job_type,
            j.salary,
            j.created_at,
            c.company_name
        FROM jobs j
        INNER JOIN companies c ON j.company_id = c.id
        WHERE j.status = 'active'
    ";

    $params = [];

    // Filter by job title or description
    if ($keyword !== '') {
        $sql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
        $searchTerm = '%' . $keyword . '%';

        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Filter by location
    if ($location !== '') {
        $sql .= " AND j.location LIKE ?";
        $params[] = '%' . $location . '%';
    }

    $sql .= " ORDER BY j.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $jobs = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log('HireNest job listing error: ' . $e->getMessage());

    $errorMessage = 'Unable to load jobs right now. Please try again later.';
}

require_once __DIR__ . '/includes/header.php';

?>

<main class="container">

    <!-- Page heading -->
    <section style="margin-bottom: 28px;">

        <p style="color: #3B82F6; font-weight: 700; font-size: 14px;">
            EXPLORE OPPORTUNITIES
        </p>

        <h1 class="page-title" style="margin-top: 8px;">
            Find your next job
        </h1>

        <p class="page-subtitle" style="margin-bottom: 0;">
            Discover opportunities that match your skills and interests.
        </p>

    </section>


    <!-- Search form -->
    <section class="card" style="margin-bottom: 32px;">

        <form method="GET" action="/hirenest/jobs.php">

            <div class="form-group">
                <label for="keyword">Job title or keyword</label>

                <input
                    type="text"
                    id="keyword"
                    name="keyword"
                    class="form-control"
                    placeholder="e.g. PHP Developer"
                    value="<?= htmlspecialchars(
                        $keyword,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="form-group">
                <label for="location">Location</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    class="form-control"
                    placeholder="e.g. Kolkata or Remote"
                    value="<?= htmlspecialchars(
                        $location,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Search Jobs
            </button>

            <a href="/hirenest/jobs.php"
               class="btn btn-outline"
               style="margin-left: 8px;">
                Clear Filters
            </a>

        </form>

    </section>


    <!-- Job results -->
    <section>

        <h2 class="page-title" style="font-size: 24px;">
            Available Jobs
        </h2>

        <p class="page-subtitle">
            Showing <?= count($jobs) ?> job(s)
            <?= count($jobs) === 50 ? ' — first 50 results' : '' ?>
        </p>

        <?php if ($errorMessage !== ''): ?>

            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php elseif (empty($jobs)): ?>

            <div class="card" style="text-align: center;">

                <h3 style="color: #17365D; margin-bottom: 8px;">
                    No jobs found
                </h3>

                <p style="color: #64748B;">
                    There are currently no matching active jobs.
                    Try another keyword or location, or check again later.
                </p>

            </div>

        <?php else: ?>

            <div style="display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                        gap: 20px;">

                <?php foreach ($jobs as $job): ?>

                    <article class="card">

                        <p style="color: #3B82F6;
                                  font-size: 13px;
                                  font-weight: 700;
                                  margin-bottom: 8px;">
                            <?= htmlspecialchars(
                                $job['company_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <h3 style="color: #17365D;
                                   font-size: 20px;
                                   margin-bottom: 12px;">
                            <?= htmlspecialchars(
                                $job['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p style="color: #64748B; margin-bottom: 12px;">
                            <?= htmlspecialchars(
                                mb_strimwidth(
                                    strip_tags($job['description']),
                                    0,
                                    150,
                                    '...'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p style="font-size: 14px; margin-bottom: 6px;">
                            <strong>Location:</strong>
                            <?= htmlspecialchars(
                                $job['location'] ?: 'Not specified',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p style="font-size: 14px; margin-bottom: 6px;">
                            <strong>Job Type:</strong>
                            <?= htmlspecialchars(
                                $job['job_type'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p style="font-size: 14px; margin-bottom: 16px;">
                            <strong>Salary:</strong>

                            <?php if ($job['salary'] !== null): ?>
                                ₹<?= number_format(
                                    (float) $job['salary'],
                                    2
                                ) ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </p>

                        <p style="font-size: 12px;
                                  color: #64748B;
                                  margin-bottom: 16px;">
                            Posted on
                            <?= htmlspecialchars(
                                date(
                                    'd M Y',
                                    strtotime($job['created_at'])
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <a
                            href="/hirenest/job-details.php?id=<?= (int) $job['id'] ?>"
                            class="btn btn-primary"
                        >
                            View Details
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
