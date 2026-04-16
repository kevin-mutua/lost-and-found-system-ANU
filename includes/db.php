<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'anu_lost_found');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL for root-relative links
if (!defined('BASE_URL')) {
    define('BASE_URL', '/lost_and_found');
}

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
