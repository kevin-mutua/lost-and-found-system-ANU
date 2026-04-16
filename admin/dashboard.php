<?php
// Start output buffering immediately
ob_start();
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

if (($_SESSION['user_role'] ?? null) !== 'admin') {
    $_SESSION['error'] = 'Admin access required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Get statistics BEFORE including header
$itemStats = getItemStatistics($pdo);
$claimStats = getClaimStatistics($pdo);
$frequentlyLost = getFrequentlyLostItems($pdo, 5);
$linkedItems = getLinkedItems($pdo, 5);

// Get recent notifications for admin
require_once '../includes/notifications.php';
$recentNotifications = getRecentNotifications($_SESSION['user_id'], 5);
$unreadNotifyCount = getUnreadNotificationCount($_SESSION['user_id']);

require_once '../includes/header.php';
?>
<div class="container-lg admin-dashboard py-5" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; max-width: 1100px;">
    
    <!-- ANU Branding Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: #fdfda8; border-left: 5px solid #ed1c24; border-radius: 20px; overflow: hidden;">
                <div class="card-body p-5">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <img src="<?php echo BASE_URL; ?>/assets/images/lostfound.png" alt="ANU" style="height: 150px;">
                        </div>
                        <div class="col-md-10">
                            <h1 class="mb-2" style="font-size: 2.5rem; font-weight: 700; color: #ed1c24;"><i class="bi bi-speedometer2"></i> Lost & Found Management System</h1>
                            <p class="mb-0 lead" style="color: #120203; font-weight: 600;">Africa Nazarene University - Administrator Dashboard</p>
                            <p class="small mt-2" style="color: #120203;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="row mb-5">
        <div class="col-12">
            <h5 class="mb-3" style="color: #ed1c24; font-weight: 700;">Quick Actions</h5>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/claims.php" class="btn w-100 py-4 rounded-lg shadow-sm" style="background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); color: white; border: none; font-weight: 600;">
                <i class="bi bi-file-earmark-check fs-4 d-block mb-2"></i>
                Review Claims
                <small class="d-block text-white-50 mt-1"><?php echo $claimStats['pending_claims'] ?? 0; ?> Pending</small>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/messaging.php" class="btn w-100 py-4 rounded-lg shadow-sm" style="background: linear-gradient(135deg, #000000 0%, #ed1c24 100%); color: white; border: none; font-weight: 600;">
                <i class="bi bi-chat-dots fs-4 d-block mb-2"></i>
                Message
                <small class="d-block text-white-50 mt-1">Send messages</small>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn w-100 py-4 rounded-lg shadow-sm" style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); color: white; border: none; font-weight: 600;">
                <i class="bi bi-people fs-4 d-block mb-2"></i>
                Manage Users
                <small class="d-block text-white-50 mt-1"><?php 
                    $userCount = $pdo->query('SELECT COUNT(*) as count FROM users')->fetch()['count']; 
                    echo $userCount ?? 0; 
                ?> Total</small>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn w-100 py-4 rounded-lg shadow-sm" style="background: linear-gradient(135deg, #fac923 0%, #000000 100%); color: black; border: none; font-weight: 600;">
                <i class="bi bi-file-earmark-pdf fs-4 d-block mb-2"></i>
                Generate Reports
                <small class="d-block" style="color: rgba(0,0,0,0.6); mt-1">Export CSV/PDF</small>
            </a>
        </div>
    </div>

    <!-- Recent Notifications Section -->
    <?php if ($unreadNotifyCount > 0 || count($recentNotifications) > 0): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: white; border-left: 5px solid #ed1c24;">
                <div class="card-header" style="background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); color: white; padding: 1.25rem; border-bottom: 2px solid #fac923;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-bell-fill"></i> Recent Notifications 
                            <?php if ($unreadNotifyCount > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2"><?php echo $unreadNotifyCount; ?> Unread</span>
                            <?php endif; ?>
                        </h6>
                        <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-sm btn-light">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($recentNotifications) > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recentNotifications as $notif): ?>
                                <a href="<?php echo BASE_URL; ?>/notifications.php?mark_read=1&id=<?php echo $notif['id']; ?>" class="notification-link" style="text-decoration: none; display: block; border-bottom: 1px solid #e0e0e0; transition: all 0.3s;">
                                    <div style="padding: 1rem; <?php echo !$notif['is_read'] ? 'background: #f8f9ff;' : ''; ?> hover: { background: #f5f5f5; }">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1" style="color: #000; font-weight: 600;">
                                                    <?php echo htmlspecialchars($notif['title'] ?? 'New Update'); ?>
                                                </h6>
                                                <p class="mb-2 small" style="color: #555;">
                                                    <?php echo htmlspecialchars(substr($notif['message'] ?? '', 0, 100)) . (strlen($notif['message'] ?? '') > 100 ? '...' : ''); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i><?php 
                                                    $time = strtotime($notif['created_at']);
                                                    $diff = time() - $time;
                                                    if ($diff < 60) echo 'Just now';
                                                    elseif ($diff < 3600) echo floor($diff / 60) . ' mins ago';
                                                    elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                                    else echo floor($diff / 86400) . ' days ago';
                                                    ?>
                                                </small>
                                            </div>
                                            <div class="ms-2">
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="badge bg-primary rounded-pill">New</span>
                                                <?php else: ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <i class="bi bi-bell-slash" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="mb-0 text-muted mt-3">No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics (Compact) -->
    <div class="row mb-5">
        <div class="col-12">
            <h5 class="mb-3" style="color: #ed1c24; font-weight: 700;">System Overview</h5>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #ed1c24;">
                <div class="card-body p-3">
                    <h4 style="color: #ed1c24; font-weight: 700;"><?php echo $itemStats['total_items'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Total Items</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #fac923;">
                <div class="card-body p-3">
                    <h4 style="color: #fac923; font-weight: 700;"><?php echo $itemStats['items_found'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Found Items</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #000000;">
                <div class="card-body p-3">
                    <h4 style="color: #000000; font-weight: 700;"><?php echo $itemStats['items_lost'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Lost Items</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #ed1c24;">
                <div class="card-body p-3">
                    <h4 style="color: #ed1c24; font-weight: 700;"><?php echo $claimStats['total_claims'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Total Claims</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #fac923;">
                <div class="card-body p-3">
                    <h4 style="color: #fac923; font-weight: 700;"><?php echo $claimStats['pending_claims'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #000000;">
                <div class="card-body p-3">
                    <h4 style="color: #000000; font-weight: 700;"><?php echo $itemStats['recovery_rate']; ?>%</h4>
                    <p class="mb-0 small text-muted">Recovery Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-5">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg" style="background: #fdfda8; border-left: 5px solid #ed1c24;">
                <div class="card-header border-bottom-0" style="background: transparent; padding: 1.5rem;">
                    <h6 class="mb-0" style="color: #ed1c24; font-weight: 700;"><i class="bi bi-pie-chart"></i> Items Distribution</h6>
                </div>
                <div class="card-body p-4">
                    <canvas id="itemsChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg" style="background: #fdfda8; border-left: 5px solid #fac923;">
                <div class="card-header border-bottom-0" style="background: transparent; padding: 1.5rem;">
                    <h6 class="mb-0" style="color: #120203; font-weight: 700;"><i class="bi bi-bar-chart"></i> Frequently Lost Categories</h6>
                </div>
                <div class="card-body p-4">
                    <canvas id="categoriesChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Sections -->
    <div class="row">
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Items Distribution
const itemsCtx = document.getElementById('itemsChart').getContext('2d');
new Chart(itemsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Lost', 'Found'],
        datasets: [{
            data: [<?php echo $itemStats['items_lost'] ?? 0; ?>, <?php echo $itemStats['items_found'] ?? 0; ?>],
            backgroundColor: ['#fa709a', '#43e97b'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
});

// Categories Chart
const catCtx = document.getElementById('categoriesChart').getContext('2d');
const frequentlyLostData = {
    labels: [<?php 
        if (!empty($frequentlyLost)) {
            foreach ($frequentlyLost as $item) {
                echo "'" . htmlspecialchars($item['category'] ?? 'Unknown') . "', ";
            }
        }
    ?>],
    data: [<?php 
        if (!empty($frequentlyLost)) {
            foreach ($frequentlyLost as $item) {
                echo (int)($item['count'] ?? 0) . ", ";
            }
        }
    ?>]
};

new Chart(catCtx, {
    type: 'bar',
    data: {
        labels: frequentlyLostData.labels.length > 0 ? frequentlyLostData.labels : ['No data'],
        datasets: [{
            label: 'Items Lost',
            data: frequentlyLostData.data.length > 0 ? frequentlyLostData.data : [0],
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe'],
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', titleColor: '#fff', bodyColor: '#fff', padding: 12, borderRadius: 6 }
        },
        scales: {
            x: { beginAtZero: true, grid: { drawBorder: false, color: 'rgba(0,0,0,0.05)' } },
            y: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
