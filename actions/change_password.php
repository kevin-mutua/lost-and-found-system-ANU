<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception('All fields are required');
    }

    // Validate new password format (8+ chars, letters and numbers only)
    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    if (!preg_match('/^[a-zA-Z0-9]+$/', $new_password)) {
        throw new Exception('Password can only contain letters and numbers');
    }

    if (!preg_match('/[a-zA-Z]/', $new_password)) {
        throw new Exception('Password must contain at least one letter');
    }

    if (!preg_match('/[0-9]/', $new_password)) {
        throw new Exception('Password must contain at least one number');
    }

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        throw new Exception('New passwords do not match');
    }

    // Get current user password from database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password in database
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    // Destroy session for auto-logout
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
