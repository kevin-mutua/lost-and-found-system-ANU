<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

// Security check WITHOUT including files - allow both security and admin
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

// Allow both security and admin to approve
$role = $_SESSION['user_role'] ?? '';
if ($role !== 'security' && $role !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized role: ' . $role]));
}

// Database connection WITHOUT includes - inline
define('DB_HOST', 'localhost');
define('DB_NAME', 'anu_lost_found');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

header('Content-Type: application/json; charset=utf-8');

// Request validation  
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
if (!$claim_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing claim ID']));
}

try {
    // Get claim and item data
    $stmt = $pdo->prepare("
        SELECT c.*, i.id as item_id, i.title
        FROM claims c
        JOIN items i ON c.item_id = i.id
        WHERE c.id = ?
    ");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();
    
    if (!$claim) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Claim not found']));
    }
    
    $item_id = $claim['item_id'];
    $claimer_id = $claim['user_id'];
    
    // Update claim status to approved
    $stmt = $pdo->prepare("UPDATE claims SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $claim_id]);
    
    // Update item status to collected
    $stmt = $pdo->prepare("UPDATE items SET status = 'collected' WHERE id = ?");
    $stmt->execute([$item_id]);
    
    // Success response
    echo json_encode(['success' => true, 'message' => 'Claim approved', 'claim_id' => $claim_id]);
    
} catch(PDOException $e) {
    error_log("Approve claim error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
