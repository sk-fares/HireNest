
<?php

require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("SELECT DATABASE() AS database_name");
    $result = $stmt->fetch();

    echo "<h1>HireNest Database Test</h1>";
    echo "<p>Database connection successful!</p>";
    echo "<p>Connected database: "
        . htmlspecialchars($result['database_name'], ENT_QUOTES, 'UTF-8')
        . "</p>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Available Tables</h2>";
    echo "<ul>";

    foreach ($tables as $table) {
        echo "<li>"
            . htmlspecialchars($table, ENT_QUOTES, 'UTF-8')
            . "</li>";
    }

    echo "</ul>";

} catch (PDOException $e) {
    error_log('HireNest test query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('The connection opened, but the test query failed. Check the PHP error log.');
}
