<?php
// This file has been deprecated - redirect to dashboard
session_start();
define('BASE_URL', '/lost_and_found');

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit();
?>>
        <div class="row mb-4">
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
                                        <a href="' . BASE_URL . '/search.php?check_status=1&check_item_type=found&item_id=' . $item['id'] . '" class="btn btn-sm btn-outline-primary mt-2">Check for Matches</a>
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

    <?php require_once 'includes/footer.php'; 
    exit();
}

// Regular student - show their items and claims
require_once 'includes/header.php';
?>
<div class="container-lg py-5" style="max-width: 1200px;">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-list-check"></i> My Items & Claims</h2>
            <p class="text-muted">View your reported lost items and active claims</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #ed1c24 0%, #fac923 100%); color: black; padding: 1.5rem;">
                    <h5 class="mb-0"><i class="bi bi-search"></i> My Lost Items</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT i.*, COUNT(c.id) as claim_count
                            FROM items i
                            LEFT JOIN claims c ON i.id = c.item_id
                            WHERE i.user_id = ? AND i.type = 'lost'
                            GROUP BY i.id
                            ORDER BY i.created_at DESC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $lost_items = $stmt->fetchAll();
                        
                        if (empty($lost_items)) {
                            echo '<p class="text-muted">You haven\'t reported any lost items yet. <a href="' . BASE_URL . '/report.php">Report a lost item</a></p>';
                        } else {
                            foreach ($lost_items as $item) {
                                echo '<div class="border-bottom pb-3 mb-3">
                                    <h6 class="mb-1">' . htmlspecialchars($item['title']) . '</h6>
                                    <small class="text-muted">Status: <span class="badge bg-info">' . ucfirst($item['status']) . '</span></small><br>
                                    <small class="text-muted">Location: ' . htmlspecialchars($item['location'] ?? 'Not specified') . '</small><br>
                                    <small class="text-muted">Claims: <strong>' . $item['claim_count'] . '</strong></small><br>
                                    <a href="' . BASE_URL . '/search.php?check_status=1&check_item_type=lost&item_id=' . $item['id'] . '" class="btn btn-sm btn-outline-primary mt-2">Check for Matches</a>
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

        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #000000 0%, #ed1c24 100%); color: white; padding: 1.5rem;">
                    <h5 class="mb-0"><i class="bi bi-hand-thumbs-up"></i> My Claims</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT c.*, i.title as item_title, i.image_path
                            FROM claims c
                            LEFT JOIN items i ON c.item_id = i.id
                            WHERE c.user_id = ?
                            ORDER BY c.created_at DESC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $claims = $stmt->fetchAll();
                        
                        if (empty($claims)) {
                            echo '<p class="text-muted">You haven\'t claimed any items yet. <a href="' . BASE_URL . '/search.php">Browse found items</a></p>';
                        } else {
                            foreach ($claims as $claim) {
                                $status_color = match($claim['status']) {
                                    'pending' => 'warning',
                                    'verified' => 'info',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary'
                                };
                                echo '<div class="border-bottom pb-3 mb-3">
                                    <h6 class="mb-1">' . htmlspecialchars($claim['item_title'] ?? 'Unknown Item') . '</h6>
                                    <small class="text-muted">Status: <span class="badge bg-' . $status_color . '">' . ucfirst($claim['status']) . '</span></small><br>
                                    <small class="text-muted">Claimed: ' . date('M d, Y', strtotime($claim['created_at'])) . '</small><br>
                                </div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error loading claims</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> <strong>Tip:</strong> Regularly check the <a href="<?php echo BASE_URL; ?>/search.php">search page</a> for items matching your lost reports.
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

requireSecurity();

// Load all items
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT i.id, i.*, u.name as reported_by, 
               c.id as claim_id, c.status as claim_status, cu.name as claimer_name,
               li.id as linked_item_id, li.match_status as link_status
        FROM items i 
        LEFT JOIN users u ON i.user_id = u.id 
        LEFT JOIN claims c ON i.id = c.item_id AND c.status IN ('pending', 'verified', 'approved')
        LEFT JOIN users cu ON c.user_id = cu.id
        GROUP BY i.id
        ORDER BY i.created_at DESC
    ");
    $stmt->execute();
    $all_items = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mt-4 mb-4">Security Panel - All Items</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item Title</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Reported By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_items) > 0): ?>
                                    <?php foreach ($all_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['image_path']): ?>
                                                        <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $item['image_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                             class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo $item['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($item['type']); ?>
                                            </span></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td>
                                                <span class="badge status-<?php echo $item['status']; ?>">
                                                    <?php echo formatStatus($item['status']); ?>
                                                </span>
                                                <?php if ($item['claimer_name']): ?>
                                                    <br><small class="text-success fw-bold mt-1 d-block">Claimed by: <?php echo htmlspecialchars($item['claimer_name']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($item['linked_item_id']): ?>
                                                    <br><small class="text-info mt-1 d-block"><i class="bi bi-link"></i> Linked item</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['reported_by'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($item['created_at']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-item-btn" 
                                                    data-item-id="<?php echo $item['id']; ?>"
                                                    data-item-status="<?php echo $item['status']; ?>"
                                                    data-item-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                    data-item-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                    data-item-location="<?php echo htmlspecialchars($item['location']); ?>"
                                                    data-item-category="<?php echo htmlspecialchars($item['category']); ?>"
                                                    data-item-image="<?php echo htmlspecialchars(BASE_URL . '/' . ($item['image_path'] ?? '')); ?>"
                                                    data-reported-by="<?php echo htmlspecialchars($item['reported_by'] ?? 'N/A'); ?>"
                                                    data-claimer-name="<?php echo htmlspecialchars($item['claimer_name'] ?? ''); ?>"
                                                    data-linked-item="<?php echo $item['linked_item_id'] ? 'yes' : 'no'; ?>">View</button>
                                                <button class="btn btn-sm btn-warning review-claims-btn" data-item-id="<?php echo $item['id']; ?>">Review Claims</button>
                                                <button class="btn btn-sm btn-success link-items-btn" data-item-id="<?php echo $item['id']; ?>" data-item-type="<?php echo $item['type']; ?>" data-item-category="<?php echo htmlspecialchars($item['category']); ?>">Link Items</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <i class="bi bi-box-seam"></i>
                                                <h5>No items found</h5>
                                                <p class="mb-0">No items have been reported yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="itemTitle">Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="itemDetailsContent"></div>
        </div>
    </div>
</div>

<!-- Claims Review Modal -->
<div class="modal fade" id="claimsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Claims for This Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="claimsContent"></div>
        </div>
    </div>
</div>

<!-- Link Items Modal -->
<div class="modal fade" id="linkItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Link Matching Items</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="linkItemsContent"></div>
                <div id="linkItemsLoading" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Define all handler functions first (at global scope)
function approveClaim(claimId, itemId, claimerId) {
    const instructions = prompt('Enter pickup instructions to send to the claimer:');
    if (instructions === null) return;
    fetch('<?php echo BASE_URL; ?>/actions/approve_claim.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'claim_id=' + claimId + '&item_id=' + itemId + '&claimer_id=' + claimerId + '&instructions=' + encodeURIComponent(instructions)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.success ? 'Claim approved!' : 'Error: ' + data.message);
        if (data.success) location.reload();
    });
}

function rejectClaim(claimId) {
    if (confirm('Reject this claim?')) {
        fetch('<?php echo BASE_URL; ?>/actions/reject_claim.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'claim_id=' + claimId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
}

function changeItemStatus(itemId, newStatus) {
    if (confirm('Change status to: ' + newStatus + '?')) {
        fetch('<?php echo BASE_URL; ?>/actions/change_item_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'item_id=' + itemId + '&status=' + newStatus
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // View Item button
    document.querySelectorAll('.view-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const title = this.getAttribute('data-item-title');
            const description = this.getAttribute('data-item-description');
            const location = this.getAttribute('data-item-location');
            const category = this.getAttribute('data-item-category');
            const image = this.getAttribute('data-item-image');
            const reportedBy = this.getAttribute('data-reported-by');
            const currentStatus = this.getAttribute('data-item-status');
            const claimerName = this.getAttribute('data-claimer-name');
            const isLinked = this.getAttribute('data-linked-item') === 'yes';
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        ${image && image.includes('/uploads/') ? `<img src="${image}" class="img-fluid rounded mb-3" alt="Item">` : '<div class="bg-light p-5 rounded text-center mb-3"><i class="bi bi-image fs-1 text-muted"></i></div>'}
                    </div>
                    <div class="col-md-6">
                        <h4>${title}</h4>
                        <p><strong>Category:</strong> ${category}</p>
                        <p><strong>Location:</strong> ${location}</p>
                        <p><strong>Description:</strong> ${description}</p>
                        <p><strong>Reported By:</strong> ${reportedBy}</p>
                        <p><strong>Item ID:</strong> ${itemId}</p>
                        <p><strong>Current Status:</strong> <span class="badge status-${currentStatus}">${currentStatus}</span></p>
                        ${claimerName ? `<p style="color: #27ae60; font-weight: bold;"><i class="bi bi-person-check"></i> Claimed by: ${claimerName}</p>` : ''}
                        ${isLinked ? `<p style="color: #3498db;"><i class="bi bi-link"></i> This item is linked with another item</p>` : ''}
                        
                        ${currentStatus === 'approved' ? `
                            <div class="mt-3">
                                <label class="form-label"><strong>Change Status to Collected:</strong></label>
                                <button class="btn btn-sm btn-success change-status-btn" data-item-id="${itemId}" data-new-status="collected">
                                    Mark as Collected
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.getElementById('itemTitle').textContent = title;
            document.getElementById('itemDetailsContent').innerHTML = content;
            
            // Add event listener for change status button
            const changeBtn = document.querySelector('.change-status-btn');
            if (changeBtn) {
                changeBtn.addEventListener('click', function() {
                    changeItemStatus(this.getAttribute('data-item-id'), this.getAttribute('data-new-status'));
                });
            }
            
            new bootstrap.Modal(document.getElementById('itemDetailsModal')).show();
        });
    });

    // Handle Link Items button
    document.querySelectorAll('.link-items-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const itemType = this.getAttribute('data-item-type');
            const itemCategory = this.getAttribute('data-item-category');
            
            // Show loading state
            document.getElementById('linkItemsContent').innerHTML = '';
            document.getElementById('linkItemsLoading').style.display = 'block';
            
            // Fetch matching items by category
            fetch('<?php echo BASE_URL; ?>/actions/get_matching_items.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'item_id=' + itemId + '&item_type=' + itemType + '&category=' + encodeURIComponent(itemCategory)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('linkItemsLoading').style.display = 'none';
                
                let content = '';
                if (data.matches.length === 0) {
                    content = '<p class="text-muted">No matching items found in this category.</p>';
                } else {
                    content = '<p class="mb-3">Select an item to link with:</p>';
                    data.matches.forEach(match => {
                        content += `
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            ${match.image_path ? `<img src="${match.image_path}" class="img-fluid rounded" alt="Item">` : '<div class="bg-light p-5 rounded text-center"><i class="bi bi-image fs-1 text-muted"></i></div>'}
                                        </div>
                                        <div class="col-md-6">
                                            <h6>${match.title}</h6>
                                            <p class="mb-1"><strong>Type:</strong> <span class="badge bg-${match.type === 'lost' ? 'danger' : 'success'}">${match.type}</span></p>
                                            <p class="mb-1"><strong>Location:</strong> ${match.location}</p>
                                            <p class="mb-1"><strong>Description:</strong> ${match.description}</p>
                                            <p class="mb-0"><small class="text-muted">ID: ${match.id}</small></p>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <button class="btn btn-sm btn-primary select-match-btn" 
                                                data-match-id="${match.id}"
                                                data-item-id="${itemId}">
                                                Link This Item
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                document.getElementById('linkItemsContent').innerHTML = content;
                
                // Add event listeners to match selection buttons
                document.querySelectorAll('.select-match-btn').forEach(selectBtn => {
                    selectBtn.addEventListener('click', function() {
                        linkItems(this.getAttribute('data-item-id'), this.getAttribute('data-match-id'));
                    });
                });
            })
            .catch(err => {
                document.getElementById('linkItemsLoading').style.display = 'none';
                document.getElementById('linkItemsContent').innerHTML = '<p class="text-danger">Error loading matching items</p>';
            });
            
            new bootstrap.Modal(document.getElementById('linkItemsModal')).show();
        });
    });

    // Handle Review Claims button
    document.querySelectorAll('.review-claims-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            
            // Fetch claims via AJAX
            fetch('<?php echo BASE_URL; ?>/actions/get_item_claims.php?item_id=' + itemId)
                .then(res => res.json())
                .then(data => {
                    let content = '';
                    
                    if (data.claims.length === 0) {
                        content = '<p class="text-muted">No claims for this item yet.</p>';
                    } else {
                        data.claims.forEach(claim => {
                            content += `
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">${claim.claimer_name}</h6>
                                                <small class="text-muted">${claim.claimer_email}</small>
                                                ${claim.status === 'approved' && claim.approver_name ? `<br><small style="color: #28a745;"><i class="bi bi-check-circle"></i> Approved by: <strong>${claim.approver_name}</strong></small>` : ''}
                                            </div>
                                            <span class="badge bg-${claim.status === 'pending' ? 'warning' : claim.status === 'approved' ? 'success' : 'danger'}">${claim.status}</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Claim Date:</strong> ${new Date(claim.created_at).toLocaleDateString()}</p>
                                        ${(() => {
                                            const proofText = claim.proof_description || '';
                                            const parts = proofText.split(' | Evidence: ');
                                            const description = parts[0];
                                            const evidencePath = parts[1];
                                            
                                            let html = `<p><strong>Evidence Description:</strong> ${description}</p>`;
                                            if (evidencePath) {
                                                html += `<div class="mb-3">
                                                    <strong>Evidence Image:</strong>
                                                    <div class="mt-2">
                                                        <img src="<?php echo BASE_URL; ?>/${evidencePath}" class="img-fluid rounded" alt="Evidence" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                                    </div>
                                                </div>`;
                                            }
                                            return html;
                                        })()}
                                        ${claim.status === 'pending' ? `
                                            <button class="btn btn-sm btn-success approve-claim-btn" data-claim-id="${claim.id}" data-item-id="${itemId}" data-claimer-id="${claim.user_id}">Approve & Send Message</button>
                                            <button class="btn btn-sm btn-danger reject-claim-btn" data-claim-id="${claim.id}">Reject</button>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    document.getElementById('claimsContent').innerHTML = content;
                    
                    // Add event listeners to new buttons
                    document.querySelectorAll('.approve-claim-btn').forEach(appBtn => {
                        appBtn.addEventListener('click', function() {
                            approveClaim(this.getAttribute('data-claim-id'), this.getAttribute('data-item-id'), this.getAttribute('data-claimer-id'));
                        });
                    });
                    
                    document.querySelectorAll('.reject-claim-btn').forEach(rejBtn => {
                        rejBtn.addEventListener('click', function() {
                            rejectClaim(this.getAttribute('data-claim-id'));
                        });
                    });
                    
                    new bootstrap.Modal(document.getElementById('claimsModal')).show();
                })
                .catch(err => {
                    document.getElementById('claimsContent').innerHTML = '<p class="text-danger">Error loading claims</p>';
                });
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>