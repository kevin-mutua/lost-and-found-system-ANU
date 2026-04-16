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

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    ob_end_clean();
    
    $exportType = $_GET['type'] ?? 'items';
    $filename = '';
    $title = '';
    $data = [];
    
    if ($exportType === 'items') {
        $stmt = $pdo->prepare("SELECT id, title, type, category, status, created_at FROM items ORDER BY created_at DESC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = 'items_report_' . date('Y-m-d') . '.pdf';
        $title = 'Items Report';
    } elseif ($exportType === 'claims') {
        $stmt = $pdo->prepare("
            SELECT c.id, i.title, u.name, c.status, c.created_at 
            FROM claims c 
            LEFT JOIN items i ON c.item_id = i.id 
            LEFT JOIN users u ON c.user_id = u.id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = 'claims_report_' . date('Y-m-d') . '.pdf';
        $title = 'Claims Report';
    }
    
    exportToPDF($data, $filename, $title);
}

// Handle CSV export BEFORE including header (before any output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Clear ALL buffered output
    ob_end_clean();
    
    $exportType = $_GET['type'] ?? 'items';
    
    if ($exportType === 'items') {
        $stmt = $pdo->prepare("SELECT id, title, type, category, status, created_at FROM items ORDER BY created_at DESC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        exportToCSV($data, 'items_report_' . date('Y-m-d') . '.csv');
    } elseif ($exportType === 'claims') {
        $stmt = $pdo->prepare("
            SELECT c.id, i.title, u.name, c.status, c.created_at 
            FROM claims c 
            LEFT JOIN items i ON c.item_id = i.id 
            LEFT JOIN users u ON c.user_id = u.id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        exportToCSV($data, 'claims_report_' . date('Y-m-d') . '.csv');
    }
}

// If not exporting, clear buffer and continue normally
ob_end_clean();

$itemStats = getItemStatistics($pdo);
$claimStats = getClaimStatistics($pdo);

// NOW include header
require_once '../includes/header.php';
?>
<div class="container-lg py-4" style="background-color: #f8f9fa; min-height: 100vh; max-width: 1100px;">
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2><i class="bi bi-file-earmark-pdf"></i> Reports & Export</h2>
                    <p class="text-muted">Generate and export system reports in CSV or PDF format</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Report Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #ed1c24;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Items</p>
                    <h3 class="mb-0" style="color: #ed1c24; font-weight: bold;"><?php echo $itemStats['total_items'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #fac923;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Claims</p>
                    <h3 class="mb-0" style="color: #fac923; font-weight: bold;"><?php echo $claimStats['total_claims'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #000000;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Recovery Rate</p>
                    <h3 class="mb-0" style="color: #000000; font-weight: bold;"><?php echo $itemStats['recovery_rate']; ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="row mb-4">
        <!-- Items Report -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div style="font-size: 3rem; color: #ed1c24; margin-bottom: 15px;">
                        <i class="bi bi-inboxes"></i>
                    </div>
                    <h5 class="card-title">Items Report</h5>
                    <p class="card-text text-muted small mb-3">Export all reported lost and found items</p>
                    <div class="d-flex gap-2">
                        <a href="?export=csv&type=items" class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-file-csv"></i> CSV
                        </a>
                        <a href="?export=pdf&type=items" class="btn btn-sm btn-outline-danger flex-grow-1">
                            <i class="bi bi-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Claims Report -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div style="font-size: 3rem; color: #fac923; margin-bottom: 15px;">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <h5 class="card-title">Claims Report</h5>
                    <p class="card-text text-muted small mb-3">Export all claims with approval status</p>
                    <div class="d-flex gap-2">
                        <a href="?export=csv&type=claims" class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-file-csv"></i> CSV
                        </a>
                        <a href="?export=pdf&type=claims" class="btn btn-sm btn-outline-danger flex-grow-1">
                            <i class="bi bi-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Summary Report -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom p-4">
            <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> System Summary Report</h5>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="mb-3">Item Statistics</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>Total Items:</strong> 
                            <span class="badge bg-primary"><?php echo $itemStats['total_items'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Lost Items:</strong> 
                            <span class="badge bg-secondary"><?php echo $itemStats['items_lost'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Found Items:</strong> 
                            <span class="badge bg-secondary"><?php echo $itemStats['items_found'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Recovery Rate:</strong> 
                            <span class="badge bg-success"><?php echo $itemStats['recovery_rate']; ?>%</span>
                        </li>
                        <li class="mb-2">
                            <strong>Recovered Items:</strong> 
                            <span class="badge bg-success"><?php echo $itemStats['recovered_items'] ?? 0; ?></span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Claims Statistics</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>Total Claims:</strong> 
                            <span class="badge bg-info"><?php echo $claimStats['total_claims'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Pending Claims:</strong> 
                            <span class="badge bg-warning"><?php echo $claimStats['pending_claims'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Approved Claims:</strong> 
                            <span class="badge bg-success"><?php echo $claimStats['claims_approved'] ?? 0; ?></span>
                        </li>
                        <li class="mb-2">
                            <strong>Rejected Claims:</strong> 
                            <span class="badge bg-danger"><?php echo $claimStats['claims_rejected'] ?? 0; ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <hr>

            <div class="alert alert-info border-0">
                <h6 class="mb-2"><i class="bi bi-info-circle"></i> Report Information</h6>
                <p class="mb-0 small">Reports can be exported in CSV format for use in spreadsheet applications. PDF export is available with TCPDF library installation. Generated reports include complete data from <strong><?php echo date('F d, Y'); ?></strong>.</p>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    transition: all 0.3s ease;
    border-radius: 12px !important;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1) !important;
}

.card {
    border-radius: 12px !important;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php require_once '../includes/footer.php'; ?>
