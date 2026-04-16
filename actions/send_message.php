<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
$claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$attachment_name = null;
$attachment_path = null;
$attachment_type = null;

// If claim_id is provided, look up the claimant's user_id
if ($claim_id > 0) {
    $stmt = $pdo->prepare("SELECT user_id FROM claims WHERE id = ?");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();
    if ($claim) {
        $recipient_id = $claim['user_id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Claim not found']);
        exit;
    }
}

if (!$recipient_id || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Handle file attachment if provided
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/messages/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = $_FILES['attachment']['name'];
        $file_type = $_FILES['attachment']['type'];
        
        // Generate unique filename
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            $attachment_path = 'uploads/messages/' . $unique_name;
            $attachment_name = $file_name;
            $attachment_type = $file_type;
        }
    }

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, recipient_id, body, subject, claim_id, attachment_path, attachment_name, attachment_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $subject = substr($message, 0, 50); // Use first 50 chars as subject for backwards compatibility

    $stmt->execute([
        $_SESSION['user_id'],
        $recipient_id,
        $message,
        $subject,
        $claim_id > 0 ? $claim_id : null,
        $attachment_path,
        $attachment_name,
        $attachment_type
    ]);

    // Log activity
    require_once '../includes/functions.php';
    logActivity($pdo, 'message_sent', 'Sent message to user ID ' . $recipient_id, 'message', 0);

    echo json_encode(['success' => true, 'message' => 'Message sent']);
    exit();

} catch (Exception $e) {
    error_log("Send message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
