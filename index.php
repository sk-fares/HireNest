
<?php
$pageTitle = "Home | HireNest";
require_once __DIR__ . '/includes/header.php';
?>

<main>

    <!-- Hero Section -->
    <section class="container">
        <div class="card" style="padding: 40px; text-align: center;">

            <p style="color: #3B82F6; font-weight: 700;">
                YOUR CAREER JOURNEY STARTS HERE
            </p>

            <h1 class="page-title"
                style="margin-top: 16px; font-size: clamp(30px, 5vw, 46px);">
                Find a job that moves<br>
                your career forward.
            </h1>

            <p class="page-subtitle"
               style="max-width: 600px; margin: 16px auto 28px;">
                Discover opportunities, connect with companies,
                and take the next step in your professional journey
                with HireNest.
            </p>

            <a href="/hirenest/jobs.php" class="btn btn-primary">
                Explore Jobs
            </a>

            <a href="/hirenest/register.php"
               class="btn btn-outline"
               style="margin-left: 8px;">
                Create an Account
            </a>

        </div>
    </section>


    <!-- Job Search Section -->
    <section class="container" style="padding-top: 0;">

        <h2 class="page-title" style="font-size: 25px;">
            Find your next opportunity
        </h2>

        <p class="page-subtitle">
            Search for jobs by title or location.
        </p>

        <div class="card">

            <form action="/hirenest/jobs.php" method="GET">

                <div class="form-group">
                    <label for="keyword">Job title or keyword</label>

                    <input
                        type="text"
                        id="keyword"
                        name="keyword"
                        class="form-control"
                        placeholder="e.g. PHP Developer, Web Designer"
                    >
                </div>

                <div class="form-group">
                    <label for="location">Preferred location</label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        class="form-control"
                        placeholder="e.g. Kolkata, Remote"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Search Jobs
                </button>

            </form>

        </div>

    </section>


    <!-- Applicant and Company Sections -->
    <section class="container" style="padding-top: 0;">

        <h2 class="page-title" style="font-size: 25px;">
            Opportunities for everyone
        </h2>

        <p class="page-subtitle">
            Whether you are looking for work or hiring talent,
            HireNest helps you take the next step.
        </p>

        <div style="display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                    gap: 20px;">

            <!-- Applicant Card -->
            <div class="card">

                <h3 style="color: #17365D; margin-bottom: 12px;">
                    For Job Seekers
                </h3>

                <p style="color: #64748B; margin-bottom: 20px;">
                    Explore career opportunities, apply for jobs,
                    and keep track of your application status
                    in one place.
                </p>

                <a href="/hirenest/register.php"
                   class="btn btn-outline">
                    Join as an Applicant
                </a>

            </div>


            <!-- Company Card -->
            <div class="card">

                <h3 style="color: #17365D; margin-bottom: 12px;">
                    For Companies
                </h3>

                <p style="color: #64748B; margin-bottom: 20px;">
                    Publish job openings, review applications,
                    and find candidates who match your
                    company's requirements.
                </p>

                <a href="/hirenest/register.php"
                   class="btn btn-primary">
                    Join as a Company
                </a>

            </div>

        </div>

    </section>


    <!-- How It Works -->
    <section class="container" style="padding-top: 0;">

        <h2 class="page-title" style="font-size: 25px;">
            How HireNest works
        </h2>

        <div style="display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;">

            <div class="card">
                <h3 style="margin-bottom: 10px;">01. Create an account</h3>
                <p style="color: #64748B;">
                    Register as an applicant or a company to get started.
                </p>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 10px;">02. Explore opportunities</h3>
                <p style="color: #64748B;">
                    Discover jobs or publish vacancies for candidates.
                </p>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 10px;">03. Track progress</h3>
                <p style="color: #64748B;">
                    Applicants can track their applications as
                    companies review them.
                </p>
            </div>

        </div>

    </section>

</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
