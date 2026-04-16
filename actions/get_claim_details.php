<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$claim_id = isset($_GET['claim_id']) ? (int)$_GET['claim_id'] : 0;

if (!$claim_id) {
    echo json_encode(['success' => false, 'error' => 'Claim ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name as claimer_name, u.email as claimer_email,
               i.title as item_title,
               approver.name as approver_name
        FROM claims c
        JOIN users u ON c.user_id = u.id
        JOIN items i ON c.item_id = i.id
        LEFT JOIN users approver ON c.approved_by = approver.id
        WHERE c.id = ?
    ");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();
    
    if (!$claim) {
        echo json_encode(['success' => false, 'error' => 'Claim not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'claim' => $claim
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
