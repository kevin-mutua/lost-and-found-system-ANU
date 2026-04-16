<?php
session_start();

// Prevent browser caching for this dynamic page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

requireLogin();

// Force admin users to go to admin dashboard
if (($_SESSION['user_role'] ?? null) === 'admin') {
    header('Location: /lost_and_found/admin/dashboard.php');
    exit();
}

// Get user stats
try {
    // Count items
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_items = $stmt->fetch()['total'];

    // Count claims
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM claims WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_claims = $stmt->fetch()['total'];

    // Get recent items - filtered by role
    // Students see only their own reported items
    // Security officers see all items in the system
    $userRole = $_SESSION['user_role'] ?? 'student';
    
    if ($userRole === 'security') {
        // Security officers see all items
        $stmt = $pdo->prepare("SELECT i.*, COUNT(c.id) as claim_count FROM items i LEFT JOIN claims c ON i.id = c.item_id GROUP BY i.id ORDER BY i.created_at DESC LIMIT 5");
        $stmt->execute([]);
    } else {
        // Students see: items they reported + items they have claimed/collected
        $stmt = $pdo->prepare("
            SELECT DISTINCT i.*, COUNT(c_all.id) as claim_count FROM items i
            LEFT JOIN claims c ON i.id = c.item_id AND c.user_id = ? AND c.status IN ('approved', 'verified')
            LEFT JOIN claims c_all ON i.id = c_all.item_id
            WHERE i.user_id = ? OR c.id IS NOT NULL
            GROUP BY i.id
            ORDER BY i.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    }
    $recent_items = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
}

// Get recent notifications for security officers
$recentNotifications = [];
$unreadNotifyCount = 0;
if (($_SESSION['user_role'] ?? null) === 'security') {
    require_once 'includes/notifications.php';
    $recentNotifications = getRecentNotifications($_SESSION['user_id'], 5);
    $unreadNotifyCount = getUnreadNotificationCount($_SESSION['user_id']);
}

require_once 'includes/header.php';
?>

<div class="container-fluid" style="max-width: 1150px; margin: 0 auto;">
    <!-- Welcome Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="welcome-card mt-4 shadow-lg">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                        <p class="welcome-text">Easily manage lost and found items, review claims, and stay connected with ANU security.</p>
                        <div class="welcome-actions mt-4">
                            <a href="<?php echo BASE_URL; ?>/report.php" class="btn btn-primary btn-lg me-3">
                                <i class="bi bi-plus-circle me-2"></i>Report Item
                            </a>
                            <a href="<?php echo BASE_URL; ?>/search.php" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-search me-2"></i>Browse Items
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 text-center mt-4 mt-lg-0">
                        <?php if ($userRole === 'student'): ?>
                        <div class="stats-overview">
                            <div class="stats-circle">
                                <div class="stats-number"><?php echo $total_items; ?></div>
                                <div class="stats-label">Items</div>
                            </div>
                            <div class="stats-circle">
                                <div class="stats-number"><?php echo $total_claims; ?></div>
                                <div class="stats-label">Claims</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards (Students Only) -->
    <?php if ($userRole === 'student'): ?>
    <div class="row mb-5">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_items; ?></h3>
                    <p class="stat-label">Total Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_claims; ?></h3>
                    <p class="stat-label">Total Claims</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo getRoleName(); ?></h3>
                    <p class="stat-label">Your Role</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo formatDate($_SESSION['created_at']); ?></h3>
                    <p class="stat-label">Member Since</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Notifications for Security Officers -->
    <?php if ($userRole === 'security' && ($unreadNotifyCount > 0 || count($recentNotifications) > 0)): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card recent-items-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell-fill" style="color: #ed1c24;"></i> Recent Notifications
                            <?php if ($unreadNotifyCount > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2"><?php echo $unreadNotifyCount; ?> Unread</span>
                            <?php endif; ?>
                        </h5>
                        <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($recentNotifications) > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recentNotifications as $notif): ?>
                                <a href="<?php echo BASE_URL; ?>/notifications.php?mark_read=1&id=<?php echo $notif['id']; ?>" class="notification-link" style="text-decoration: none; display: block; border-bottom: 1px solid #e0e0e0; transition: all 0.3s;">
                                    <div style="padding: 1rem; <?php echo !$notif['is_read'] ? 'background: #f8f9ff;' : ''; ?>">
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

    <!-- Recent Items -->
    <div class="row">
        <div class="col-12">
            <div class="card recent-items-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-clock-history me-2"></i>
                        <?php 
                            $itemsLabel = ($userRole === 'security') ? 'System Items' : 'My Reported Items';
                            echo $itemsLabel;
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['image_path']): ?>
                                                        <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $item['image_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                             class="me-3" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                                                    <?php else: ?>
                                                        <div class="item-icon bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-box text-primary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo htmlspecialchars($item['id']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?> rounded-pill">
                                                <?php echo formatType($item['type']); ?>
                                            </span></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td>
                                                <span class="badge status-<?php echo $item['status']; ?> rounded-pill" 
                                                    title="<?php 
                                                        if ($item['type'] === 'lost' && $item['status'] === 'reported') {
                                                            echo 'Waiting for a matching found item to be reported';
                                                        } elseif ($item['type'] === 'found' && $item['status'] === 'open') {
                                                            echo 'Available for students to claim';
                                                        }
                                                    ?>">
                                                    <?php 
                                                    $status_text = formatStatus($item['status']);
                                                    // Only show claim count for open statuses, not for final statuses like 'collected'
                                                    $showClaimCount = !in_array($item['status'], ['collected', 'recovered', 'verified', 'approved']);
                                                    if ($showClaimCount && isset($item['claim_count']) && $item['claim_count'] > 0) {
                                                        echo $status_text . ' (' . $item['claim_count'] . ')';
                                                    } else {
                                                        echo $status_text;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($item['created_at']); ?></td>
                                            <td>
                                                <?php if ($item['status'] === 'recovered' || $item['status'] === 'collected'): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Item status finalized">
                                                        <i class="bi bi-check-circle"></i> Done
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info view-status-btn" 
                                                        data-item-id="<?php echo $item['id']; ?>"
                                                        data-item-type="<?php echo $item['type']; ?>"
                                                        data-search-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                        data-search-category="<?php echo htmlspecialchars($item['category']); ?>"
                                                        data-search-location="<?php echo htmlspecialchars($item['location']); ?>">View Matches</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-box-seam display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No items yet</h5>
                                <p class="text-muted mb-4">Start by reporting an item or checking for found items.</p>
                                <a href="<?php echo BASE_URL; ?>/report.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Report Your First Item
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="itemDetails">
                <!-- Item details will be populated here -->
            </div>
        </div>
    </div>
</div>

<!-- Claim Modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-labelledby="claimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimModalLabel">Claim Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="claimForm" action="actions/claim_item.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="claimItemId" name="item_id">
                    <div class="mb-3">
                        <label for="evidence" class="form-label">Upload Evidence (ID, Receipt, etc.)</label>
                        <input type="file" class="form-control" id="evidence" name="evidence" accept="image/*" required>
                        <div id="evidencePreview" class="mt-3" style="display: none;">
                            <small class="text-muted d-block mb-2">Preview:</small>
                            <img id="evidencePreviewImg" src="" alt="Evidence preview" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="claim_description" class="form-label">Additional Information</label>
                        <textarea class="form-control" id="claim_description" name="claim_description" rows="3" placeholder="Describe why this item belongs to you..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Global variables for modals -->
<script>
let itemModal, claimModal, claimForm;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle View Status button for lost/found items
    const statusButtons = document.querySelectorAll('.view-status-btn');
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const itemType = this.getAttribute('data-item-type');
            const title = this.getAttribute('data-search-title');
            const category = this.getAttribute('data-search-category');
            const location = this.getAttribute('data-search-location');
            
            const params = new URLSearchParams();
            params.append('item_id', itemId);
            params.append('check_status', '1');
            params.append('check_item_type', itemType);
            // Optional: also add search params for fallback browsing
            if (title) params.append('search', title);
            if (category) params.append('category', category);
            if (location) params.append('location', location);
            
            window.location.href = '<?php echo BASE_URL; ?>/search.php?' + params.toString();
        });
    });

    // Handle View button for found items
    const itemButtons = document.querySelectorAll('.view-item-btn');
    itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
    claimModal = new bootstrap.Modal(document.getElementById('claimModal'));
    const itemDetails = document.getElementById('itemDetails');

    itemButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-item-id');
            const title = this.getAttribute('data-title');
            const type = this.getAttribute('data-type');
            const category = this.getAttribute('data-category');
            const location = this.getAttribute('data-location');
            const description = this.getAttribute('data-description');
            const status = this.getAttribute('data-status');
            const reportedBy = this.getAttribute('data-reported-by');
            const date = this.getAttribute('data-date');
            const image = this.getAttribute('data-image');

            // Fetch full item details including claim status
            fetch('<?php echo BASE_URL; ?>/actions/get_item_details.php?item_id=' + itemId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const userRole = '<?php echo $_SESSION['user_role'] ?? 'guest'; ?>';

                        itemDetails.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    ${image ? `<img src="${image}" class="img-fluid rounded" alt="Item Image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22300%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2220%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23999%22%3EImage not available%3C/text%3E%3C/svg%3E';">` : '<div class="bg-light p-5 text-center rounded"><i class="bi bi-image fs-1"></i><p class="mt-2 text-muted">No image available</p></div>'}
                                </div>
                                <div class="col-md-6">
                                    <h4>${title}</h4>
                                    <p><strong>Type:</strong> ${type.charAt(0).toUpperCase() + type.slice(1)}</p>
                                    <p><strong>Category:</strong> ${category}</p>
                                    <p><strong>Location:</strong> ${location}</p>
                                    <p><strong>Description:</strong> ${description}</p>
                                    <p><strong>Status:</strong> ${status}</p>
                                    <p><strong>Reported By:</strong> ${reportedBy}</p>
                                    <p><strong>Date:</strong> ${date}</p>
                                    ${type === 'found' && userRole === 'student' && data.user_has_claimed ? `<button class="btn btn-secondary" disabled><i class="bi bi-check-circle me-1"></i>Claimed</button>` : (type === 'found' && userRole === 'student' && status !== 'collected' && data.item.user_id !== <?php echo $_SESSION['user_id'] ?? 0; ?> ? `<button class="btn btn-primary" onclick="openClaimModal('${itemId}')"><i class="bi bi-plus-circle me-1"></i>Claim Item</button>` : '')}
                                </div>
                            </div>
                        `;

                        itemModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    itemDetails.innerHTML = '<p class="text-danger">Error loading item details.</p>';
                    itemModal.show();
                });
        });
    });


    // Handle evidence file preview
    const evidenceInput = document.getElementById('evidence');
    if (evidenceInput) {
        evidenceInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('evidencePreviewImg').src = e.target.result;
                    document.getElementById('evidencePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (file) {
                alert('Please select a valid image file.');
                evidenceInput.value = '';
                document.getElementById('evidencePreview').style.display = 'none';
            }
        });
    }

    // Handle claim form submission
    claimForm = document.getElementById('claimForm');
    if (claimForm) {
        claimForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Claim form submitted');
            
            // Validate file is selected
            const evidenceInput = document.getElementById('evidence');
            if (!evidenceInput || !evidenceInput.files || evidenceInput.files.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File Required',
                    text: 'Please upload evidence (photo, ID, receipt, etc.) to support your claim.'
                });
                return;
            }
            
            // Disable submit button during submission
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            const baseUrl = '<?php echo BASE_URL; ?>';
            const submitUrl = baseUrl + '/actions/claim_item.php';
            
            console.log('Submitting to:', submitUrl);
            console.log('Form data keys:', Array.from(formData.keys()));
            
            // Log the item_id being submitted
            const itemIdValue = document.getElementById('claimItemId').value;
            console.log('Submitting with item_id:', itemIdValue);
            console.log('evidence file:', evidenceInput.files[0]?.name);
            
            // Create a timeout promise
            const timeoutPromise = new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Request timeout - server took too long to respond')), 30000)
            );
            
            Promise.race([
                fetch(submitUrl, {
                    method: 'POST',
                    body: formData
                }),
                timeoutPromise
            ])
            .then(response => {
                console.log('Response received, status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed response:', data);
                    
                    if (data.success) {
                        console.log('SUCCESS: Claim submitted. Reloading page...');
                        // Simple, reliable success - just reload
                        alert('✓ Claim submitted successfully! Reloading...');
                        claimModal.hide();
                        itemModal.hide();
                        setTimeout(() => location.reload(), 500);
                    } else {
                        console.error('Server returned failure:', data.message);
                        alert('❌ Claim Failed: ' + (data.message || 'An unknown error occurred'));
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                } catch (jsonError) {
                    console.error('JSON parse error:', jsonError);
                    console.error('Response was:', text);
                    alert('❌ Server Error: Invalid response. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('❌ Connection Error: ' + (error.message || 'Unable to connect to server. Please try again.'));
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    } else {
        console.error('Claim form not found');
    }

    window.openClaimModal = function(itemId) {
        console.log('Opening claim modal for item:', itemId);
        
        // Reset the form completely
        const claimFormElement = document.getElementById('claimForm');
        if (claimFormElement) {
            claimFormElement.reset();
        }
        
        // Clear file preview
        const evidencePreview = document.getElementById('evidencePreview');
        if (evidencePreview) {
            evidencePreview.style.display = 'none';
        }
        
        // Set the item ID - use value assignment to ensure it sticks
        const itemIdInput = document.getElementById('claimItemId');
        if (itemIdInput) {
            itemIdInput.value = itemId;
            console.log('Set claimItemId to:', itemIdInput.value);
        } else {
            console.error('Could not find claimItemId input element');
        }
        
        // Hide item modal and show claim modal
        itemModal.hide();
        claimModal.show();
    };
});
</script>

<?php
require_once 'includes/footer.php';
?>