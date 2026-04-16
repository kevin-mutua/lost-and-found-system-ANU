<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Item ID required']);
    exit;
}

try {
    // Get item details with reporter info
    $stmt = $pdo->prepare("
        SELECT i.*, u.name as reported_by_name
        FROM items i
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    // Get claim and security officer info if item is collected
    $claim_info = null;
    $user_has_claimed = false;
    
    if ($item['status'] === 'collected' || $item['status'] === 'claimed' || $item['status'] === 'verified') {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   u1.name as claimer_name,
                   u2.name as approved_by_name
            FROM claims c
            LEFT JOIN users u1 ON c.user_id = u1.id
            LEFT JOIN users u2 ON c.approved_by = u2.id
            WHERE c.item_id = ? AND c.status IN ('approved', 'verified')
            LIMIT 1
        ");
        $stmt->execute([$item_id]);
        $claim_info = $stmt->fetch();
    }
    
    // Check if current user has claimed this item
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM claims 
            WHERE item_id = ? AND user_id = ? AND status NOT IN ('rejected')
        ");
        $stmt->execute([$item_id, $_SESSION['user_id']]);
        $result = $stmt->fetch();
        $user_has_claimed = $result['count'] > 0;
    }
    
    // Count all claims for this item (for admin/security)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $claims_result = $stmt->fetch();
    $claims_count = $claims_result['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'item' => $item,
        'claim_info' => $claim_info,
        'user_has_claimed' => $user_has_claimed,
        'claims_count' => $claims_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
