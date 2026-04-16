<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

requireSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$item_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Validate status value
$valid_statuses = ['reported', 'matched', 'verified', 'collected', 'open', 'claimed', 'approved'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
    $stmt->execute([$status, $item_id]);
    
    echo json_encode(['success' => true, 'message' => 'Item status updated successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
