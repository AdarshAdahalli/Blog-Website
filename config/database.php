<?php
/**
 * Database Connection Configuration (PDO)
 * BlogSphere — Blog Management & Publishing System
 */

require_once __DIR__ . '/constants.php';

$db_host = 'localhost';
$db_name = 'blog_platform';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // In production, log error instead of echoing trace
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database Connection Failure. Please check server configuration.");
}

function getDBConnection() {
    global $pdo;
    return $pdo;
}
