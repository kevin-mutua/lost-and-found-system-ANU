<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

requireSecurity();

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$item_id) {
    echo json_encode(['claims' => []]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as claimer_name, u.email as claimer_email,
               approver.name as approver_name
        FROM claims c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN users approver ON c.approved_by = approver.id
        WHERE c.item_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$item_id]);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['claims' => $claims]);
} catch(PDOException $e) {
    echo json_encode(['claims' => [], 'error' => $e->getMessage()]);
}
?>
