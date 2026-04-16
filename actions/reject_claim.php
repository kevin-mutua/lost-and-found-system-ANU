<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;

if (!$claim_id) {
    echo json_encode(['success' => false, 'message' => 'Missing claim ID']);
    exit();
}

try {
    // Get claim details first
    $stmt = $pdo->prepare("SELECT * FROM claims WHERE id = ?");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();
    
    if (!$claim) {
        echo json_encode(['success' => false, 'message' => 'Claim not found']);
        exit();
    }
    
    // Update claim status to rejected
    $stmt = $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$claim_id]);
    
    // Send rejection message to claimer
    $subject = 'Your claim has been rejected';
    $message_body = "Unfortunately, your claim for this item has been rejected after review by our security team.\n\n" .
                    "If you believe this was a mistake, please contact us at reports@anu.ac.ke\n\n" .
                    "Thank you for using ANU Lost and Found!";
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, recipient_id, subject, body, claim_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $claim['user_id'], $subject, $message_body, $claim_id]);
    
    // Log activity
    logActivity($pdo, 'claim_rejected', "Claim #$claim_id rejected by security officer", 'claim', $claim_id, 'pending', 'rejected');
    
    echo json_encode(['success' => true, 'message' => 'Claim rejected']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
