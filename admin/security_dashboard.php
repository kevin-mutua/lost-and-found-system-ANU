<?php
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

// Only allow security officers
if (($_SESSION['user_role'] ?? null) !== 'security') {
    $_SESSION['error'] = 'Access denied. Security officer role required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once '../includes/header.php';
?>

<div class="container-fluid" style="max-width: 1150px; margin: 0 auto;">
    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h2><i class="bi bi-shield-check"></i> Security Officer Dashboard</h2>
            <p class="text-muted">Verify items, manage found reports, and approve item matches</p>
        </div>
    </div>

    <div class="row">
        <!-- Pending Items for Verification -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%); color: black; padding: 1.5rem;">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Pending Verification</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT i.*, u.name as reporter_name
                            FROM items i
                            LEFT JOIN users u ON i.user_id = u.id
                            WHERE i.status IN ('reported', 'matched')
                            ORDER BY i.created_at DESC
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $pending_items = $stmt->fetchAll();
                        
                        if (empty($pending_items)) {
                            echo '<p class="text-muted">No items pending verification</p>';
                        } else {
                            foreach ($pending_items as $item) {
                                echo '<div class="border-bottom pb-3 mb-3">
                                    <h6 class="mb-1">' . htmlspecialchars($item['title']) . '</h6>
                                    <small class="text-muted">Type: ' . ucfirst($item['type']) . ' | Status: <span class="badge bg-warning">' . ucfirst($item['status']) . '</span></small><br>
                                    <small class="text-muted">Reported by: ' . htmlspecialchars($item['reporter_name'] ?? 'Unknown') . '</small><br>
                                    <small class="text-muted">Location: ' . htmlspecialchars($item['location'] ?? 'Not specified') . '</small><br>
                                    <a href="' . BASE_URL . '/search.php" class="btn btn-sm btn-outline-primary mt-2">View & Verify</a>
                                </div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error loading items</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Items Found by This Officer -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #000000 0%, #ed1c24 100%); color: white; padding: 1.5rem;">
                    <h5 class="mb-0"><i class="bi bi-bag-check"></i> My Found Items</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT * FROM items
                            WHERE user_id = ? AND type = 'found'
                            ORDER BY created_at DESC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $found_items = $stmt->fetchAll();
                        
                        if (empty($found_items)) {
                            echo '<p class="text-muted">You haven\'t reported any found items yet. <a href="' . BASE_URL . '/report.php">Report a found item</a></p>';
                        } else {
                            foreach ($found_items as $item) {
                                echo '<div class="border-bottom pb-3 mb-3">
                                    <h6 class="mb-1">' . htmlspecialchars($item['title']) . '</h6>
                                    <small class="text-muted">Status: <span class="badge bg-info">' . ucfirst($item['status']) . '</span></small><br>
                                    <small class="text-muted">Location Found: ' . htmlspecialchars($item['location'] ?? 'Not specified') . '</small><br>
                                    <small class="text-muted">Date: ' . date('M d, Y', strtotime($item['created_at'])) . '</small><br>
                                </div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error loading items</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> <strong>Your Role:</strong> As a security officer, you can verify item reports, approve matches between lost and found items, and report found items to the system.
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
