<?php
session_start();

// Don't cache this page since content changes
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

// Check if user is logged in, if not show alert and redirect
if (!isLoggedIn()) {
    $login_redirect = true;
} else {
    $login_redirect = false;
}

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.openClaimModal = function(itemId) {
    document.getElementById('evidence').value = '';
    document.getElementById('claim_description').value = '';
    
    const evidencePreview = document.getElementById('evidencePreview');
    if (evidencePreview) evidencePreview.style.display = 'none';
    
    document.getElementById('claimItemId').value = itemId;
    
    const claimModalEl = document.getElementById('claimModal');
    if (claimModalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(claimModalEl);
        if (modal) modal.show();
        
        attachEvidencePreviewListener();
    }
};

function attachEvidencePreviewListener() {
    const evidenceInput = document.getElementById('evidence');
    if (!evidenceInput) return;
    
    evidenceInput.removeEventListener('change', handleEvidenceChange);
    evidenceInput.addEventListener('change', handleEvidenceChange);
}

function handleEvidenceChange() {
    const file = this.files[0];
    
    if (!file) return;
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewImg = document.getElementById('evidencePreviewImg');
            const previewDiv = document.getElementById('evidencePreview');
            
            if (previewImg && previewDiv) {
                previewImg.src = e.target.result;
                previewDiv.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(file);
    } else {
        alert('Please select a valid image file.');
        this.value = '';
        const previewDiv = document.getElementById('evidencePreview');
        if (previewDiv) previewDiv.style.display = 'none';
    }
}

function submitClaimDirect() {
    const evidenceInput = document.getElementById('evidence');
    if (!evidenceInput || !evidenceInput.files || evidenceInput.files.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'File Required',
            text: 'Please upload evidence (photo, ID, receipt, etc.) to support your claim.'
        });
        return;
    }
    
    const submitBtn = event.target;
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const formData = new FormData();
    formData.append('item_id', document.getElementById('claimItemId').value);
    formData.append('claim_description', document.getElementById('claim_description').value);
    formData.append('evidence', evidenceInput.files[0]);
    
    const baseUrl = '<?php echo BASE_URL; ?>';
    const submitUrl = baseUrl + '/actions/claim_item.php';
    
    fetch(submitUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Claim Submitted',
                    text: 'Your claim has been submitted successfully! You will receive a notification when the item is approved.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ed1c24'
                }).then(() => {
                    claimModal.hide();
                    itemModal.hide();
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to submit claim', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (jsonError) {
            console.error('JSON Error:', jsonError);
            console.error('Response:', text);
            Swal.fire('Error', 'Server error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        Swal.fire('Error', error.message || 'Connection error', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function attachClaimFormListener() {
    const claimFormElement = document.getElementById('claimForm');
    if (!claimFormElement) return;
    
    claimFormElement.removeEventListener('submit', handleClaimSubmit);
    claimFormElement.addEventListener('submit', handleClaimSubmit);
}

function handleClaimSubmit(e) {
    e.preventDefault();
    
    const evidenceInput = document.getElementById('evidence');
    if (!evidenceInput || !evidenceInput.files || evidenceInput.files.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'File Required',
            text: 'Please upload evidence (photo, ID, receipt, etc.) to support your claim.'
        });
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const formData = new FormData(this);
    const baseUrl = '<?php echo BASE_URL; ?>';
    const submitUrl = baseUrl + '/actions/claim_item.php';
    
    const timeoutPromise = new Promise((_, reject) => 
        setTimeout(() => reject(new Error('Request timeout')), 30000)
    );
    
    Promise.race([
        fetch(submitUrl, {
            method: 'POST',
            body: formData
        }),
        timeoutPromise
    ])
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Claim Submitted',
                    text: 'Your claim has been submitted successfully! You will receive a notification when the item is approved.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ed1c24'
                }).then(() => {
                    claimModal.hide();
                    itemModal.hide();
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to submit claim', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (jsonError) {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        Swal.fire('Error', error.message || 'Connection error', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}
</script>

<?php
$category = sanitize($_GET['category'] ?? '');
$location = sanitize($_GET['location'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$check_status = isset($_GET['check_status']) && $_GET['check_status'] === '1';
$check_item_type = sanitize($_GET['check_item_type'] ?? ''); // 'lost' or 'found'
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;

// Get current user ID for claim button visibility
$currentUserId = $_SESSION['user_id'] ?? 0;

$error = '';
$success = '';

if (!empty($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (!empty($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

try {
    // Get total count of all items in database
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM items");
    $stmt->execute();
    $totalCount = $stmt->fetch()['total'] ?? 0;
    
    $items = [];
    
    // If checking matches for a specific item, query item_matches table
    if ($check_status && $item_id) {
        // Look for matches for this item
        
        // Get the matched item IDs from item_matches table
        $stmt = $pdo->prepare("
            SELECT im.*, 
                   CASE 
                       WHEN im.lost_item_id = ? THEN im.found_item_id
                       ELSE im.lost_item_id
                   END as matched_item_id
            FROM item_matches im
            WHERE (im.lost_item_id = ? OR im.found_item_id = ?)
            ORDER BY im.match_score DESC
        ");
        $stmt->execute([$item_id, $item_id, $item_id]);
        $matches = $stmt->fetchAll();
        

        
        if (!empty($matches)) {
            error_log("SEARCH: Match details: " . json_encode($matches));
            
            // Fetch the actual item details for each match
            $matched_item_ids = array_map(function($m) { return $m['matched_item_id']; }, $matches);
            error_log("SEARCH: Matched item IDs to fetch: " . json_encode($matched_item_ids));
            
            $placeholders = str_repeat('?,', count($matched_item_ids) - 1) . '?';
            
            $sql = "SELECT i.*, u.name as reported_by, COUNT(c.id) as claim_count 
                   FROM items i 
                   LEFT JOIN users u ON i.user_id = u.id 
                   LEFT JOIN claims c ON i.id = c.item_id
                   WHERE i.id IN ($placeholders)
                   GROUP BY i.id
                   ORDER BY i.created_at DESC";
            
            error_log("SEARCH: Executing SQL: $sql with IDs: " . implode(',', $matched_item_ids));
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($matched_item_ids);
            $items = $stmt->fetchAll();
            error_log("SEARCH: Fetched " . count($items) . " item records");
        } else {
            error_log("SEARCH: No matches found in item_matches table for item_id=$item_id");
            $items = [];
        }
    }
    // If check_status is set but NO item_id, try to find the item first using search terms
    else if ($check_status && !$item_id && ($search || $category || $location)) {
        error_log("SEARCH: check_status WITHOUT item_id - trying to find item first using search terms");
        
        // Find ANY item based on search criteria (not just user's items)
        $user_item_sql = "SELECT id FROM items WHERE type = ? AND status IN ('open', 'reported')";
        $user_params = [$check_item_type];
        
        if ($search) {
            $user_item_sql .= " AND (LOWER(title) LIKE ? OR LOWER(description) LIKE ?)";
            $user_params[] = "%{$search}%";
            $user_params[] = "%{$search}%";
        }
        if ($category) {
            $user_item_sql .= " AND category = ?";
            $user_params[] = $category;
        }
        if ($location) {
            $user_item_sql .= " AND location LIKE ?";
            $user_params[] = "%{$location}%";
        }
        
        $user_item_sql .= " LIMIT 1";
        
        error_log("SEARCH: Looking for item by search terms: $user_item_sql with params: " . json_encode($user_params));
        
        $stmt = $pdo->prepare($user_item_sql);
        $stmt->execute($user_params);
        $user_item = $stmt->fetch();
        
        if ($user_item) {
            error_log("SEARCH: Found item id=" . $user_item['id']);
            $found_item_id = $user_item['id'];
            
            // Now get matches for this item
            $stmt = $pdo->prepare("
                SELECT im.*, 
                       CASE 
                           WHEN im.lost_item_id = ? THEN im.found_item_id
                           ELSE im.lost_item_id
                       END as matched_item_id
                FROM item_matches im
                WHERE (im.lost_item_id = ? OR im.found_item_id = ?)
                ORDER BY im.match_score DESC
            ");
            $stmt->execute([$found_item_id, $found_item_id, $found_item_id]);
            $matches = $stmt->fetchAll();
            
            error_log("SEARCH: Found " . count($matches) . " matches from item_id=$found_item_id");
            
            if (!empty($matches)) {
                // Fetch the actual item details for each match
                $matched_item_ids = array_map(function($m) { return $m['matched_item_id']; }, $matches);
                $placeholders = str_repeat('?,', count($matched_item_ids) - 1) . '?';
                
                $sql = "SELECT i.*, u.name as reported_by, COUNT(c.id) as claim_count 
                       FROM items i 
                       LEFT JOIN users u ON i.user_id = u.id 
                       LEFT JOIN claims c ON i.id = c.item_id
                       WHERE i.id IN ($placeholders)
                       GROUP BY i.id
                       ORDER BY i.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($matched_item_ids);
                $items = $stmt->fetchAll();
                error_log("SEARCH: Got " . count($items) . " matched item records");
            } else {
                $items = [];
            }
        } else {
            error_log("SEARCH: Could not find item with those search terms");
            $items = [];
        }
    } else {
        // Regular search - not checking matches for specific item
        $sql = "SELECT i.*, u.name as reported_by, COUNT(c.id) as claim_count 
                 FROM items i 
                 LEFT JOIN users u ON i.user_id = u.id 
                 LEFT JOIN claims c ON i.id = c.item_id
                 WHERE 1=1";
        if (hasRole('student')) {
            $sql .= " AND (i.status = 'open' OR i.status = 'reported')";
        }

        $params = [];
        
        // Filter by item type if provided
        if ($check_item_type) {
            $sql .= " AND i.type = ?";
            $params[] = $check_item_type;
        }

        // Show opposite type as potential matches (no item_id specified, just browsing)
        if ($check_status && $check_item_type && !$item_id) {
            $opposite_type = ($check_item_type === 'lost') ? 'found' : 'lost';
            $sql .= " AND i.type = ?";
            $params[] = $opposite_type;
        }

        if ($category) {
            $sql .= " AND i.category = ?";
            $params[] = $category;
        }

        if ($location) {
            $sql .= " AND i.location LIKE ?";
            $params[] = "%{$location}%";
        }

        if ($search) {
            $sql .= " AND (LOWER(i.title) LIKE ? OR LOWER(i.description) LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
    }
    
    // Show success message if checking lost item status and results found
    if ($check_status && count($items) > 0) {
        $first_item = $items[0];
        
        // Check if item is recovered or collected
        if ($first_item['status'] === 'recovered' || $first_item['status'] === 'collected') {
            if ($first_item['type'] === 'lost' && $first_item['status'] === 'recovered') {
                // Lost item was recovered - get the name of who found/reported it
                $finder_stmt = $pdo->prepare("
                    SELECT u.name FROM items i
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.id = ?
                ");
                $finder_stmt->execute([$first_item['id']]);
                $finder = $finder_stmt->fetch();
                $finder_name = $finder ? htmlspecialchars($finder['name']) : 'someone';
                
                $success = '🎉 Congratulations! Your lost item has been successfully recovered thanks to ' . $finder_name . '. We\'re glad we could help reunite you with your belongings! Thank you for using ANU Lost and Found!';
            } else if ($first_item['type'] === 'found' && $first_item['status'] === 'collected') {
                // Found item was collected - get who claimed it
                $claim_stmt = $pdo->prepare("
                    SELECT u.name FROM claims c
                    LEFT JOIN users u ON c.user_id = u.id
                    WHERE c.item_id = ? AND c.status = 'approved'
                    LIMIT 1
                ");
                $claim_stmt->execute([$first_item['id']]);
                $claim = $claim_stmt->fetch();
                $collector_name = $claim ? htmlspecialchars($claim['name']) : 'someone';
                
                $success = '✓ Great news! Your found item was collected by ' . $collector_name . '. Thank you for helping reunite items with the ANU community!';
            }
        } else {
            // Item not yet recovered/collected, check for matches
            $collected_count = 0;
            foreach ($items as $item) {
                if ($item['status'] === 'collected' || $item['status'] === 'recovered') {
                    $collected_count++;
                }
            }
            
            if ($collected_count === count($items)) {
                $success = '⏹ We found ' . count($items) . ' item(s) matching your description, but they have already been ' . ($check_item_type === 'lost' ? 'recovered' : 'claimed') . '. Please check back later or contact the Lost and Found office.';
            } else if ($collected_count > 0) {
                $success = '✓ Possible match found! We discovered ' . count($items) . ' item(s). Some have already been ' . ($check_item_type === 'lost' ? 'recovered' : 'claimed') . ', but others may still need attention.';
            } else if ($check_status) {
                if ($check_item_type === 'found') {
                    $success = '✓ Match found! We discovered ' . count($items) . ' lost item(s) where someone reported losing something like this. You could help reunite them with their belongings!';
                } else {
                    $success = '✓ Match found! We discovered ' . count($items) . ' item(s) that could match your lost item. Review them below and claim if it\'s yours!';
                }
            }
        }
    }
} catch(PDOException $e) {
    $items = [];
    $error = "Error loading items: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mt-4 mb-4">Search Items <span style="color: #ed1c24; font-size: 0.9em; font-weight: 600;">(<?php echo $totalCount; ?>)</span></h2>
            <p style="color: #666; font-size: 15px; margin-bottom: 24px; font-weight: 500; text-align: center;">
                <i class="bi bi-info-circle" style="color: #fac923; margin-right: 6px;"></i>
                Search for your lost items below. Our team is working hard to return lost items to their rightful owners!
            </p>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success && ($_SESSION['user_role'] ?? 'student') === 'student'): ?>
                <div class="alert alert-success" role="alert" style="text-align: center;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-12 mb-lg-4 mb-3" style="width: auto; flex: 0 0 auto;">
            <div class="filter-section">
                <h3 class="mb-3">
                    <i class="bi bi-funnel"></i> Filters
                    <button class="btn btn-sm btn-outline-secondary d-lg-none float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filterForm">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </h3>
                <form method="GET" action="" id="filterForm" class="collapse show collapse-lg">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select form-select-sm" id="category" name="category">
                            <option value="">All Categories</option>
                            <option value="Personal Items" <?php echo $category === 'Personal Items' ? 'selected' : ''; ?>>Personal Items</option>
                            <option value="Electronics" <?php echo $category === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                            <option value="Documents" <?php echo $category === 'Documents' ? 'selected' : ''; ?>>Documents</option>
                            <option value="Clothing" <?php echo $category === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                            <option value="Books" <?php echo $category === 'Books' ? 'selected' : ''; ?>>Books</option>
                            <option value="Others" <?php echo $category === 'Others' ? 'selected' : ''; ?>>Others</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <select class="form-select form-select-sm" id="location" name="location" onchange="handleLocationSelect(this.value, 'search')">
                            <option value="">Select Location...</option>
                            <option value="Helstrom" <?php echo $location === 'Helstrom' ? 'selected' : ''; ?>>Helstrom</option>
                            <option value="Old Chapel" <?php echo $location === 'Old Chapel' ? 'selected' : ''; ?>>Old Chapel</option>
                            <option value="Computer Lab" <?php echo $location === 'Computer Lab' ? 'selected' : ''; ?>>Computer Lab</option>
                            <option value="Moore Hall" <?php echo $location === 'Moore Hall' ? 'selected' : ''; ?>>Moore Hall</option>
                            <option value="Cafeteria" <?php echo $location === 'Cafeteria' ? 'selected' : ''; ?>>Cafeteria</option>
                            <option value="Dining Cafe" <?php echo $location === 'Dining Cafe' ? 'selected' : ''; ?>>Dining Cafe</option>
                            <option value="Jernighan Chapel" <?php echo $location === 'Jernighan Chapel' ? 'selected' : ''; ?>>Jernighan Chapel</option>
                            <option value="Harmons Building" <?php echo $location === 'Harmons Building' ? 'selected' : ''; ?>>Harmons Building</option>
                            <option value="Day Scholar Lounge" <?php echo $location === 'Day Scholar Lounge' ? 'selected' : ''; ?>>Day Scholar Lounge</option>
                            <option value="Entertainment Hall" <?php echo $location === 'Entertainment Hall' ? 'selected' : ''; ?>>Entertainment Hall</option>
                            <option value="Parking Lot" <?php echo $location === 'Parking Lot' ? 'selected' : ''; ?>>Parking Lot</option>
                            <option value="Sife Canteen" <?php echo $location === 'Sife Canteen' ? 'selected' : ''; ?>>Sife Canteen</option>
                            <option value="Gym" <?php echo $location === 'Gym' ? 'selected' : ''; ?>>Gym</option>
                            <option value="Pool & TV Room" <?php echo $location === 'Pool & TV Room' ? 'selected' : ''; ?>>Pool & TV Room</option>
                            <option value="Rugby Pitch" <?php echo $location === 'Rugby Pitch' ? 'selected' : ''; ?>>Rugby Pitch</option>
                            <option value="Basketball Court" <?php echo $location === 'Basketball Court' ? 'selected' : ''; ?>>Basketball Court</option>
                            <option value="Lecturer Quarters" <?php echo $location === 'Lecturer Quarters' ? 'selected' : ''; ?>>Lecturer Quarters</option>
                            <option value="Staff Quarters" <?php echo $location === 'Staff Quarters' ? 'selected' : ''; ?>>Staff Quarters</option>
                            <option value="Student Quarters" <?php echo $location === 'Student Quarters' ? 'selected' : ''; ?>>Student Quarters</option>
                            <option value="other">Other (specify)</option>
                        </select>
                        <input type="text" class="form-control form-control-sm mt-2" id="locationOther" name="locationOther" 
                               placeholder="Enter specific location" style="display:none;" value="<?php echo htmlspecialchars($location); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Available</option>
                            <option value="matched" <?php echo $status === 'matched' ? 'selected' : ''; ?>>Matched</option>
                            <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            <option value="collected" <?php echo $status === 'collected' ? 'selected' : ''; ?>>Collected</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search" 
                               placeholder="Search by title or description" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-9 col-12" style="width: auto; flex: 1 1 auto;">
            <!-- Debug info -->
            <?php error_log("SEARCH DISPLAY: check_status=$check_status, item_count=" . count($items) . ", check_item_type=$check_item_type"); ?>
            
            <!-- No matches alert -->
            <?php if ($check_status && count($items) === 0): ?>
                <div class="card border-warning mb-4 shadow-sm" style="border-left: 5px solid #ed1c24;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="bi bi-search" style="font-size: 2rem; color: #fac923;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <?php if ($check_item_type === 'found'): ?>
                                    <h5 style="color: #ed1c24; margin-bottom: 0.5rem;">
                                        <i class="bi bi-exclamation-circle-fill me-2"></i>No Lost Item Match Yet
                                    </h5>
                                    <p class="mb-3 text-muted">
                                        No one has reported losing an item like this yet. Keep this found item safe - someone may report it as lost soon!
                                    </p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?php echo BASE_URL; ?>/search.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Search
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/report.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-plus-circle me-1"></i>Report Another Item
                                        </a>
                                        <a href="mailto:security@anu.edu.au" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-envelope me-1"></i>Contact Security
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <h5 style="color: #ed1c24; margin-bottom: 0.5rem;">
                                        <i class="bi bi-exclamation-circle-fill me-2"></i>No Matches Yet
                                    </h5>
                                    <p class="mb-3 text-muted">
                                        We haven't found any matching items for your report yet. New items are reported daily, so check back soon!
                                    </p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?php echo BASE_URL; ?>/search.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Search
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/report.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-plus-circle me-1"></i>Report Another Item
                                        </a>
                                        <a href="mailto:security@anu.edu.au" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-envelope me-1"></i>Contact Security
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Claim</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $item['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                     class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <div class="bg-light rounded" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                        <?php echo ucfirst($item['type']); ?>
                                    </span></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
<td><span class="badge status-<?php echo $item['status']; ?>">
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
</span></td>
                                    <td><?php if ($item['type'] === 'found' && $item['user_id'] !== $currentUserId && !hasRole('admin') && !hasRole('security')): ?><button class="btn btn-sm btn-success" onclick="openClaimModal(<?php echo $item['id']; ?>)"><i class="bi bi-plus-circle me-1"></i>Claim</button><?php endif; ?></td>
<td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
<td><button class="btn btn-sm btn-primary view-item-btn" 
    type="button"
    onclick="viewItemDetails(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>', '<?php echo $item['type']; ?>')"
    >View</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-search"></i>
                                        <h5>No items found</h5>
                                        <?php if ($check_status && hasRole('student')): ?>
                                            <p class="mb-2">Your lost item hasn't been matched yet.</p>
                                            <p class="mb-0 text-muted small">Don't worry! As soon as someone reports a found item that matches your description, it will appear here. Keep checking back, or we can send you a notification.</p>
                                        <?php else: ?>
                                            <p class="mb-0">Try adjusting your filters or search terms.</p>
                                        <?php endif; ?>
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


<!-- Claim Item Modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-labelledby="claimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimModalLabel">Claim Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="claimItemId" value="">
                <div class="mb-3">
                    <label for="evidence" class="form-label">Upload Evidence (ID, Receipt, etc.)</label>
                    <input type="file" class="form-control" id="evidence" accept="image/*" required>
                    <div id="evidencePreview" class="mt-3" style="display: none;">
                        <small class="text-muted d-block mb-2">Preview:</small>
                        <img id="evidencePreviewImg" src="" alt="Evidence preview" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="claim_description" class="form-label">Additional Information</label>
                    <textarea class="form-control" id="claim_description" rows="3" placeholder="Describe why this item belongs to you..."></textarea>
                </div>
                <button type="button" class="btn btn-primary" onclick="submitClaimDirect()">Submit Claim</button>
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

<!-- Claim Details Modal (for admin/security) -->
<div class="modal fade" id="claimDetailsModal" tabindex="-1" aria-labelledby="claimDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimDetailsLabel">Claim Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="claimDetailsContent">
                    <!-- Claim details will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal - Reliable Bootstrap Modal for message input -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="messageModalLabel">Send Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="messageContent" class="form-label"><strong id="messageLabel">Message</strong></label>
                <textarea id="messageContent" class="form-control" rows="6" placeholder="Type your message here..." style="min-height: 150px; font-size: 14px; resize: vertical;"></textarea>
                <small class="text-muted d-block mt-2">Character count: <span id="charCount">0</span>/1000</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendMessageBtn" onclick="submitMessage()">Send Message</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Confirmation Modal -->
<div class="modal fade" id="rejectConfirmModal" tabindex="-1" aria-labelledby="rejectConfirmLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectConfirmLabel"><i class="bi bi-exclamation-circle"></i> Reject Claim</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to reject this claim?</strong></p>
                <p class="text-muted">This action will notify the student that their claim has been rejected.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn" onclick="confirmReject()"><i class="bi bi-x-circle"></i> Yes, Reject</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>

<?php if ($login_redirect): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'warning',
            title: 'Login Required',
            html: 'You must be logged in with your <strong>ANU credentials</strong> to find lost and found items in the campus',
            confirmButtonText: 'Go to Login',
            confirmButtonColor: '#ed1c24',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo BASE_URL; ?>/auth/login.php';
            }
        });
    });
</script>
<?php endif; ?>

<script>
// Modal objects
let claimModal, claimDetailsModal, itemModal;
let currentUserId;

document.addEventListener('DOMContentLoaded', function() {
    // Set up modal instances
    const claimModalEl = document.getElementById('claimModal');
    const itemModalEl = document.getElementById('itemModal');
    const claimDetailsModalEl = document.getElementById('claimDetailsModal');
    
    if (claimModalEl) claimModal = new bootstrap.Modal(claimModalEl);
    if (itemModalEl) itemModal = new bootstrap.Modal(itemModalEl);
    if (claimDetailsModalEl) claimDetailsModal = new bootstrap.Modal(claimDetailsModalEl);
    
    currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;

    // Auto-trigger item modal if view_item parameter is passed (from notifications)
    const urlParams = new URLSearchParams(window.location.search);
    const autoViewItemId = urlParams.get('view_item');

    if (autoViewItemId) {
        setTimeout(() => {
            viewItemDetails(autoViewItemId, '', '');
            window.history.replaceState({}, document.title, window.location.pathname + '?search=');
        }, 100);
    }
});
    
    
// Show claim details in modal
function viewClaimDetails(claimId) {
        fetch('<?php echo BASE_URL; ?>/actions/get_claim_details.php?claim_id=' + claimId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const claim = data.claim;
                    const claimDate = new Date(claim.created_at).toLocaleDateString();
                    
                    // Parse proof_description to extract reason and evidence path
                    const descParts = claim.proof_description.split(' | Evidence: ');
                    const reason = descParts[0] || 'No reason provided';
                    const evidencePath = descParts[1] || null;
                    
                    let detailsHTML = `
                        <div class="claim-details-container">
                            <h5 class="mb-3">Claim Details</h5>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Claimant Information</h6>
                                    <p><strong>Name:</strong> ${claim.claimer_name}</p>
                                    <p><strong>Email:</strong> ${claim.claimer_email}</p>
                                    <p><strong>Date Claimed:</strong> ${claimDate}</p>
                                    <p><strong>Status:</strong> <span class="badge ${claim.status === 'pending' ? 'bg-warning' : claim.status === 'approved' ? 'bg-success' : 'bg-danger'}">${claim.status.toUpperCase()}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Reason for Claim</h6>
                                    <p class="text-muted">${reason}</p>
                                </div>
                            </div>
                            
                            ${evidencePath ? `
                                <div class="mb-4">
                                    <h6>Evidence Provided</h6>
                                    <img src="<?php echo BASE_URL; ?>/${evidencePath}" alt="Evidence" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                </div>
                            ` : ''}
                            
                            <div class="mt-4 pt-3 border-top">
                                <h6>Actions</h6>
                                ${claim.status === 'pending' ? `
                                    <button class="btn btn-success btn-sm me-2" onclick="approveClaim(${claimId})"><i class="bi bi-check-circle me-1"></i>Approve</button>
                                    <button class="btn btn-danger btn-sm me-2" onclick="rejectClaim(${claimId})"><i class="bi bi-x-circle me-1"></i>Reject</button>
                                    <button class="btn btn-primary btn-sm" onclick="sendClaimMessage(${claimId}, '${claim.claimer_name}')"><i class="bi bi-chat me-1"></i>Send Message</button>
                                ` : `
                                    <button class="btn btn-primary btn-sm" onclick="sendClaimMessage(${claimId}, '${claim.claimer_name}')"><i class="bi bi-chat me-1"></i>Send Message</button>
                                `}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('claimDetailsContent').innerHTML = detailsHTML;
                    claimDetailsModal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to load claim details', 'error');
            });
}
    
// Approve needs a message first
function approveClaim(claimId) {
    alert('Collection Message Required\n\nYou MUST send a collection/pickup message to the student before approving!');
    
    window.currentApproveClaimId = claimId;
    window.isApprovalMessage = true;
    
    document.getElementById('messageLabel').textContent = 'Collection/Pickup Instructions';
    document.getElementById('messageContent').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('sendMessageBtn').textContent = 'Send & Approve';
    document.getElementById('sendMessageBtn').className = 'btn btn-success';
    
    let messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    messageModal.show();
    
    setTimeout(() => {
        document.getElementById('messageContent').focus();
    }, 500);
    }
    
    function rejectClaim(claimId) {
        window.currentRejectClaimId = claimId;
        let rejectConfirmModal = new bootstrap.Modal(document.getElementById('rejectConfirmModal'));
        rejectConfirmModal.show();
    }
    
    function confirmReject() {
        const claimId = window.currentRejectClaimId;
        const confirmBtn = document.getElementById('confirmRejectBtn');
        
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Rejecting...';
        
        fetch('<?php echo BASE_URL; ?>/actions/reject_claim.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'claim_id=' + claimId
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Claim Rejected',
                        text: 'The claim has been rejected successfully.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ed1c24'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to reject claim', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes, Reject';
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', text);
                Swal.fire('Error', 'Server error: Invalid response format. Check console for details.', 'error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Yes, Reject';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            Swal.fire('Error', error.message, 'error');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Yes, Reject';
        });
    }
    
    // Open message box
    function sendClaimMessage(claimId, claimerName) {
        window.currentClaimId = claimId;
        window.isApprovalMessage = false;
        
        document.getElementById('messageLabel').textContent = 'Send Message to ' + claimerName;
        document.getElementById('messageContent').value = '';
        document.getElementById('charCount').textContent = '0';
        document.getElementById('sendMessageBtn').textContent = 'Send Message';
        document.getElementById('sendMessageBtn').className = 'btn btn-primary';
        
        let messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        messageModal.show();
        
        setTimeout(() => {
            document.getElementById('messageContent').focus();
        }, 500);
    }
    
    // Send message
    window.submitMessage = function() {
        const messageText = document.getElementById('messageContent').value.trim();
        if (!messageText) {
            alert('Please enter a message');
            return;
        }
        
        const claimId = window.currentApproveClaimId || window.currentClaimId;
        const itemId = window.currentItemId;
        const messageTarget = window.messageTarget || 'claim';
        const isApproval = window.isApprovalMessage;
        
        document.getElementById('sendMessageBtn').disabled = true;
        document.getElementById('sendMessageBtn').textContent = 'Sending...';
        
        let body = 'message=' + encodeURIComponent(messageText);
        
        if (messageTarget === 'item' && itemId) {
            // For item messages, fetch item details to get reporter's user_id
            const baseUrl = '<?php echo BASE_URL; ?>';
            fetch(baseUrl + '/actions/get_item_details.php?item_id=' + itemId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.item) {
                        body += '&recipient_id=' + data.item.user_id;
                        sendMessageRequest(body, isApproval);
                    } else {
                        alert('Error: Could not find item reporter');
                        document.getElementById('sendMessageBtn').disabled = false;
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    document.getElementById('sendMessageBtn').disabled = false;
                });
        } else {
            body += 'claim_id=' + claimId + '&is_collection_message=' + (isApproval ? '1' : '0');
            sendMessageRequest(body, isApproval);
        }
    };

    function sendMessageRequest(body, isApproval) {
        fetch('<?php echo BASE_URL; ?>/actions/send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isApproval) {
                    // Approve the claim after sending message
                    const claimId = window.currentApproveClaimId;
                    fetch('<?php echo BASE_URL; ?>/actions/approve_claim.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'claim_id=' + claimId
                    })
                    .then(response => {
                        return response.text();
                    })
                    .then(text => {
                        try {
                            const approveData = JSON.parse(text);
                            if (approveData.success) {
                                alert('✓ Message sent and claim approved!');
                                location.reload();
                            } else {
                                alert('❌ Approval error: ' + (approveData.message || 'Failed to approve'));
                                location.reload();
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response was:', text);
                            alert('Message sent! Error approving claim: ' + e.message + '\nResponse: ' + text.substring(0, 100));
                            location.reload();
                        }
                    })
                    .catch(error => {
                        alert('Message sent! Error approving claim: ' + error.message);
                        location.reload();
                    });
                } else {
                    alert('✓ Message sent successfully!');
                    const messageModal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
                    if (messageModal) messageModal.hide();
                    // Auto-reload after short delay
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                alert('❌ Error: ' + (data.message || 'Failed to send message'));
                document.getElementById('sendMessageBtn').disabled = false;
                document.getElementById('sendMessageBtn').textContent = isApproval ? 'Send & Approve' : 'Send Message';
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
            document.getElementById('sendMessageBtn').disabled = false;
            document.getElementById('sendMessageBtn').textContent = isApproval ? 'Send & Approve' : 'Send Message';
        });
    };

// Count message chars
    const messageContent = document.getElementById('messageContent');
    if (messageContent) {
        messageContent.addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });
    }
    
    // Handle search form submission
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const locationSelect = document.getElementById('location');
            const locationOther = document.getElementById('locationOther');
            
            if (locationOther && locationOther.style.display !== 'none' && locationOther.value) {
                locationSelect.value = locationOther.value;
            }
        });
    }
});

// Show item details
window.viewItemDetails = function(itemId, title, type) {
    if (!itemId) {
        alert('Invalid item ID');
        return;
    }

    const baseUrl = '<?php echo BASE_URL; ?>';

    // Fetch item details
    fetch(baseUrl + '/actions/get_item_details.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error: ' + (data.message || 'Failed to load item'));
                return;
            }

            const item = data.item;
            const currentUser = currentUserId;
            const isCurrentUserItem = item.user_id === currentUser;
            const userRole = '<?php echo $_SESSION['user_role'] ?? 'student'; ?>';
            
            // Check if there's a match record for this item to get match percentage
            fetch(baseUrl + '/actions/get_matching_items.php?item_id=' + itemId)
                .then(r => r.json())
                .then(matchData => {
                    let matchScore = 0;
                    if (matchData && matchData.matches && matchData.matches.length > 0) {
                        matchScore = parseInt(matchData.matches[0].match_score) || 0;
                    }
                    
                    displayItemModal(item, currentUser, isCurrentUserItem, userRole, data.claims_count || 0, matchScore);
                })
                .catch(err => {
                    console.log('Match fetch failed, showing item without match:', err);
                    displayItemModal(item, currentUser, isCurrentUserItem, userRole, data.claims_count || 0, 0);
                });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading item details');
        });
};

function displayItemModal(item, currentUser, isCurrentUserItem, userRole, claimsCount, matchScore) {
    const baseUrl = '<?php echo BASE_URL; ?>';
    

    
    // Add action buttons
    let topButtons = '<div class="mb-4 pb-3 border-bottom">';
    
    if (!isCurrentUserItem && (userRole === 'student' || userRole === 'security' || userRole === 'admin')) {
        topButtons += `<button class="btn btn-outline-primary me-2" onclick="sendItemMessage(${item.id}, '${item.reported_by_name || 'Reporter'}')"><i class="bi bi-chat me-1"></i>Message</button>`;
    }
    
    if ((userRole === 'admin' || userRole === 'security') && claimsCount > 0) {
        topButtons += `<button class="btn btn-info me-2" onclick="viewItemClaims(${item.id})"><i class="bi bi-eye me-1"></i>${claimsCount} Claim${claimsCount !== 1 ? 's' : ''}</button>`;
    }
    
    if (topButtons === '<div class="mb-4 pb-3 border-bottom">') {
        topButtons = ''; // Don't show empty button bar
    } else {
        topButtons += '</div>';
    }
    
    // Build item details display
    let html = `<div class="row">
        <div class="col-md-6">
            ${item.image_path ? `<img src="${baseUrl}/${item.image_path}" alt="${item.title}" class="img-fluid rounded" style="max-height: 400px; object-fit: cover; width: 100%; border: 1px solid #dee2e6;">` : `<div class="bg-light p-5 text-center rounded"><i class="bi bi-image fs-1 text-muted"></i><p class="text-muted mt-2">No image available</p></div>`}
            
            ${item.type === 'found' && item.user_id !== currentUser ? `<div class="mt-3">
                <button class="btn btn-primary w-100" onclick="openClaimModal(${item.id})"><i class="bi bi-plus-circle me-1"></i>Claim This Item</button>
            </div>` : ''}
        </div>
        <div class="col-md-6">
            <h6 class="text-muted mb-2">${item.type === 'lost' ? '🔍 LOST ITEM' : '📦 FOUND ITEM'}</h6>
            <h3 class="mb-3">${item.title}</h3>
            
            <div class="details-section mb-4">
                <p class="mb-2"><strong>Category:</strong> ${item.category}</p>
                <p class="mb-2"><strong>Description:</strong> ${item.description}</p>
                <p class="mb-2"><strong>Location:</strong> ${item.location}</p>
                <p class="mb-2"><strong>Status:</strong> <span class="badge ${getBadgeClass(item.status)}">${item.status.toUpperCase()}</span></p>
                <p class="mb-2"><strong>Reported:</strong> ${item.reported_by_name || 'Unknown'}</p>
                <p class="text-muted"><small>${new Date(item.created_at).toLocaleDateString()}</small></p>
            </div>
            
            ${matchScore > 0 ? `<div class="alert alert-info mb-3">
                <strong>Match Score: ${matchScore}%</strong>
                <br><small>This item matches your search criteria</small>
            </div>` : ''}
        </div>
    </div>`;
    
    // Show modal
    document.getElementById('itemDetails').innerHTML = topButtons + html;
    document.getElementById('itemModalLabel').textContent = item.type === 'lost' ? 'Lost Item Details' : 'Found Item Details';
    itemModal.show();
}

function getBadgeClass(status) {
    switch(status) {
        case 'open': return 'bg-success';
        case 'reported': return 'bg-info';
        case 'claimed': return 'bg-warning';
        case 'verified': return 'bg-success';
        case 'collected': return 'bg-primary';
        default: return 'bg-secondary';
    }
}

// Message item reporter
window.sendItemMessage = function(itemId, itemTitle) {
    window.currentItemId = itemId;
    window.messageTarget = 'item';
    
    document.getElementById('messageLabel').textContent = 'Message About Item: ' + itemTitle;
    document.getElementById('messageContent').value = '';
    document.getElementById('charCount').textContent = '0';
    
    let messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    messageModal.show();
    
    setTimeout(() => {
        document.getElementById('messageContent').focus();
    }, 500);
};

// Show item claims
window.viewItemClaims = function(itemId) {
    const baseUrl = '<?php echo BASE_URL; ?>';
    fetch(baseUrl + '/actions/get_item_claims.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.claims || data.claims.length === 0) {
                Swal.fire('Info', 'No claims for this item', 'info');
                return;
            }

            let claimsHtml = '<div style="max-height: 500px; overflow-y: auto;">';
            data.claims.forEach(claim => {
                claimsHtml += `
                    <div class="card mb-2" onclick="viewClaimDetails(${claim.id})" style="cursor: pointer;">
                        <div class="card-body">
                            <h6 class="card-title">${claim.claimer_name}</h6>
                            <p class="card-text small mb-1">${claim.claimer_email}</p>
                            <p class="small text-muted mb-0">Status: <span class="badge bg-secondary">${claim.status.toUpperCase()}</span></p>
                        </div>
                    </div>
                `;
            });
            claimsHtml += '</div>';

            document.getElementById('itemDetails').innerHTML = claimsHtml;
            itemModal.show();
        })
        .catch(error => console.error('Error:', error));
};
</script>