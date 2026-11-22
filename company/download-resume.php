
<?php

session_start();

// Only logged-in companies can download resumes
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header('Location: /hirenest/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get application ID
$applicationId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$applicationId || $applicationId < 1) {
    http_response_code(400);
    exit('Invalid application ID.');
}

try {

    // Find the company
    $companyStmt = $pdo->prepare("
        SELECT id
        FROM companies
        WHERE user_id = ?
        LIMIT 1
    ");

    $companyStmt->execute([$_SESSION['user_id']]);
    $company = $companyStmt->fetch();

    if (!$company) {
        http_response_code(403);
        exit('Company profile not found.');
    }

    // Fetch resume only if the job belongs to this company
    $resumeStmt = $pdo->prepare("
        SELECT a.resume_path, u.name
        FROM applications a
        INNER JOIN jobs j ON a.job_id = j.id
        INNER JOIN users u ON a.applicant_id = u.id
        WHERE a.id = ?
        AND j.company_id = ?
        LIMIT 1
    ");

    $resumeStmt->execute([
        $applicationId,
        $company['id']
    ]);

    $application = $resumeStmt->fetch();

    if (!$application) {
        http_response_code(404);
        exit('Application not found or access denied.');
    }

    // Resolve the protected resume directory
    $resumeDirectory = realpath(
        __DIR__ . '/../storage/resumes'
    );

    $filename = basename($application['resume_path']);

    $filePath = $resumeDirectory
        ? realpath($resumeDirectory . DIRECTORY_SEPARATOR . $filename)
        : false;

    // Ensure the file is inside the resume directory
    if (
        !$resumeDirectory ||
        !$filePath ||
        dirname($filePath) !== $resumeDirectory ||
        !is_file($filePath)
    ) {
        http_response_code(404);
        exit('Resume file not found.');
    }

    // Allow only PDF, DOC and DOCX
    $extension = strtolower(
        pathinfo($filename, PATHINFO_EXTENSION)
    );

    $allowedMimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!isset($allowedMimeTypes[$extension])) {
        http_response_code(403);
        exit('Unsupported resume format.');
    }

    // Detect actual file MIME type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $filePath);
    finfo_close($fileInfo);

    if ($mimeType !== $allowedMimeTypes[$extension]) {
        http_response_code(403);
        exit('Invalid resume file.');
    }

    // Prepare download response
    $downloadName = 'Resume_' . preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $application['name']
    ) . '.' . $extension;

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header(
        'Content-Disposition: attachment; filename="' .
        $downloadName . '"'
    );
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');

    readfile($filePath);
    exit;

} catch (PDOException $e) {

    error_log('HireNest resume download error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to download resume right now.');
}
