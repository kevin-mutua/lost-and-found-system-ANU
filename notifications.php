<?php
session_start();

// Prevent browser caching for this dynamic page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/notifications.php';

requireLogin();

// Mark notification as read and optionally redirect to action_url
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notification_id = (int)$_GET['id'];
    
    // Get the notification to retrieve action_url
    try {
        $stmt = $pdo->prepare("SELECT action_url, is_read FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $_SESSION['user_id']]);
        $notif = $stmt->fetch();
        
        // Mark as read only if not already read
        if ($notif && !$notif['is_read']) {
            markNotificationAsRead($notification_id);
        }
        
        // If there's an action_url, redirect to it. Include a parameter to indicate we're coming from notification
        if ($notif && !empty($notif['action_url'])) {
            // Add a parameter to indicate this came from a notification
            $separator = (strpos($notif['action_url'], '?') !== false) ? '&' : '?';
            $redirect_url = $notif['action_url'] . $separator . 'from_notification=1';
            header('Location: ' . $redirect_url);
        } else {
            header('Location: ' . BASE_URL . '/notifications.php');
        }
    } catch(PDOException $e) {
        header('Location: ' . BASE_URL . '/notifications.php');
    }
    exit();
}

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    markAllNotificationsAsRead($_SESSION['user_id']);
    $_GET['page'] = $_GET['page'] ?? 1;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get notifications
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM notifications WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error loading notifications: " . $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 950px; margin: 50px auto;">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-bell me-2"></i>Notifications</h1>
                <?php if (getUnreadNotificationCount($_SESSION['user_id']) > 0): ?>
                    <form method="POST" class="mb-0">
                        <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check-all me-1"></i>Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (count($notifications) > 0): ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notif): 
                        // Build click handler - if no action_url, just mark as read
                        $click_url = '?mark_read=1&id=' . $notif['id'];
                    ?>
                        <a href="<?php echo $click_url; ?>" class="notification-item-link" style="text-decoration: none; display: block; cursor: pointer;">
                            <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" style="
                                background: <?php echo !$notif['is_read'] ? '#f8f9ff' : '#ffffff'; ?>;
                                border-left: 4px solid <?php echo getNotiicationColor($notif['type'] ?? 'system'); ?>;
                                padding: 15px;
                                margin-bottom: 10px;
                                border-radius: 8px;
                                transition: all 0.3s;
                                cursor: pointer;
                                hover: {
                                    background: <?php echo !$notif['is_read'] ? '#e8eaff' : '#f5f5f5'; ?>;
                                    transform: translateX(5px);
                                }
                            ">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="mb-1" style="color: #000;">
                                                <?php echo getNotificationIcon($notif['type'] ?? 'system'); ?>
                                                <?php echo htmlspecialchars($notif['title'] ?? 'New Update'); ?>
                                            </h5>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge bg-primary rounded-pill">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-2" style="color: #555; font-size: 0.95rem;">
                                            <?php echo htmlspecialchars($notif['message'] ?? 'No details available'); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?php echo formatNotificationTime($notif['created_at']); ?>
                                        </small>
                                    </div>
                                    <div class="ms-3" style="flex-shrink: 0;">
                                        <?php if (!empty($notif['action_url'])): ?>
                                            <i class="bi bi-arrow-right" style="color: #0d6efd; font-size: 1.2rem;"></i>
                                        <?php else: ?>
                                            <i class="bi bi-check-circle" style="color: #28a745; font-size: 1.2rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Pagination" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-5" role="alert">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #0d6efd;"></i>
                    <h5 class="mt-3">No Notifications Yet</h5>
                    <p class="mb-0">You're all caught up! Check back later for updates on your lost and found items.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to get notification color
function getNotiicationColor($type) {
    switch($type) {
        case 'match':
            return '#28a745'; // Green
        case 'claim':
            return '#ffc107'; // Amber
        case 'verification':
            return '#007bff'; // Blue
        case 'recovery':
            return '#20c997'; // Teal
        case 'link':
            return '#6f42c1'; // Purple
        case 'item_report':
            return '#e83e8c'; // Pink/Magenta
        default:
            return '#6c757d'; // Gray
    }
}

// Helper function to get notification icon
function getNotificationIcon($type) {
    switch($type) {
        case 'match':
            return '<i class="bi bi-check-circle" style="color: #28a745;"></i>';
        case 'claim':
            return '<i class="bi bi-exclamation-circle" style="color: #ffc107;"></i>';
        case 'verification':
            return '<i class="bi bi-shield-check" style="color: #007bff;"></i>';
        case 'recovery':
            return '<i class="bi bi-gift" style="color: #20c997;"></i>';
        case 'link':
            return '<i class="bi bi-link-45deg" style="color: #6f42c1;"></i>';
        case 'item_report':
            return '<i class="bi bi-exclamation-triangle" style="color: #e83e8c;"></i>';
        default:
            return '<i class="bi bi-info-circle" style="color: #6c757d;"></i>';
    }
}

// Helper function to format notification time
function formatNotificationTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . " days ago";
    } else {
        return date('M j, Y', $time);
    }
}

require_once 'includes/footer.php';
?>
