<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/notifications.php';

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get_count';

try {
    switch ($action) {
        case 'get_count':
            // Get unread notification count
            $count = getUnreadNotificationCount($_SESSION['user_id']);
            echo json_encode(['count' => $count, 'success' => true]);
            break;
            
        case 'get_recent':
            // Get recent notifications (limit 5)
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $notifications = getRecentNotifications($_SESSION['user_id'], $limit);
            echo json_encode(['notifications' => $notifications, 'success' => true]);
            break;
            
        case 'mark_read':
            // Mark a notification as read
            if (!isset($_POST['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing notification ID', 'success' => false]);
                exit();
            }
            $result = markNotificationAsRead($_POST['id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $result = markAllNotificationsAsRead($_SESSION['user_id']);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action', 'success' => false]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'success' => false]);
}
