
<?php

// HireNest - Database Connection

$host = 'localhost';
$dbname = 'hirenest_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Enable exceptions for database errors
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Return database results as associative arrays
    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Use real prepared statements where supported
    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {
    // Log technical details privately
    error_log('HireNest database connection failed: ' . $e->getMessage());

    // Show a generic message to visitors
    http_response_code(500);
    exit('Unable to connect to the database. Please try again later.');
}
