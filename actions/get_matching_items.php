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
$item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

if (!$item_id || !$item_type || !$category) {
    echo json_encode(['success' => false, 'matches' => []]);
    exit();
}

try {
    // Get items of opposite type from same category (excluding the current item)
    // If current is 'lost', find 'found' items
    // If current is 'found', find 'lost' items
    
    $opposite_type = ($item_type === 'lost') ? 'found' : 'lost';
    
    $stmt = $pdo->prepare("
        SELECT i.id, i.title, i.type, i.category, i.description, i.location, i.image_path, i.status
        FROM items i
        WHERE i.type = ? 
        AND i.category = ?
        AND i.id != ?
        AND i.status IN ('open', 'claimed')
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$opposite_type, $category, $item_id]);
    $matches = $stmt->fetchAll();
    
    // Format the image paths to include BASE_URL
    foreach ($matches as &$match) {
        if ($match['image_path']) {
            $match['image_path'] = BASE_URL . '/' . $match['image_path'];
        }
    }
    
    echo json_encode(['success' => true, 'matches' => $matches]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'matches' => []]);
}
?>
