<?php
// Start output buffering immediately
ob_start();
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

if (!in_array($_SESSION['user_role'] ?? null, ['admin', 'security'])) {
    $_SESSION['error'] = 'Access required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Get all claims with associated data
$stmt = $pdo->prepare("
    SELECT c.*, 
           i.title as item_title, i.category, i.image_path,
           u.name as claimer_name, u.email as claimer_email, u.phone,
           reporter.name as reporter_name,
           approver.name as approver_name
    FROM claims c
    LEFT JOIN items i ON c.item_id = i.id
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN users reporter ON i.user_id = reporter.id
    LEFT JOIN users approver ON c.approved_by = approver.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$claims = $stmt->fetchAll();

require_once '../includes/header.php';
?><div class="container-lg py-4" style="background-color: #f8f9fa; min-height: 100vh; max-width: 1100px;">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2><i class="bi bi-file-earmark-check"></i> Claims Management</h2>
                    <p class="text-muted">Review and manage all item claims</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Claims Summary -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #ffc107;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Pending Claims</p>
                    <h3 class="mb-0" style="color: #ffc107; font-weight: bold;">
                        <?php echo count(array_filter($claims, fn($c) => $c['status'] === 'pending')); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #28a745;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Approved Claims</p>
                    <h3 class="mb-0" style="color: #28a745; font-weight: bold;">
                        <?php echo count(array_filter($claims, fn($c) => $c['status'] === 'approved')); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #dc3545;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Rejected Claims</p>
                    <h3 class="mb-0" style="color: #dc3545; font-weight: bold;">
                        <?php echo count(array_filter($claims, fn($c) => $c['status'] === 'rejected')); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card shadow-sm border-0" style="border-left: 5px solid #667eea;">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Claims</p>
                    <h3 class="mb-0" style="color: #667eea; font-weight: bold;">
                        <?php echo count($claims); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Claims Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom p-4">
            <h5 class="card-title mb-0">All Claims</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <tr>
                            <th class="p-3">Item</th>
                            <th class="p-3">Claimer</th>
                            <th class="p-3">Reporter</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($claims)): ?>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="d-flex align-items-center">
                                            <?php if ($claim['image_path']): ?>
                                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($claim['image_path']); ?>" 
                                                     alt="Item" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($claim['item_title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($claim['category']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div>
                                            <strong><?php echo htmlspecialchars($claim['claimer_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($claim['claimer_email']); ?></small>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <strong><?php echo htmlspecialchars($claim['reporter_name']); ?></strong>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge bg-<?php 
                                            echo $claim['status'] === 'pending' ? 'warning' : 
                                                 ($claim['status'] === 'approved' ? 'success' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-muted small">
                                        <?php echo date('M d, Y', strtotime($claim['created_at'])); ?>
                                    </td>
                                    <td class="p-3">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewClaim(<?php echo htmlspecialchars(json_encode($claim)); ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No claims found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Single Reusable Modal -->
<div class="modal fade" id="claimViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title"><i class="bi bi-file-earmark-check"></i> Claim #<span id="modalClaimId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">Item Details</h6>
                        <p class="mb-2"><strong>Title:</strong> <span id="modalItemTitle"></span></p>
                        <p class="mb-2"><strong>Category:</strong> <span id="modalCategory"></span></p>
                        <p class="mb-2"><strong>Reported By:</strong> <span id="modalReporterName"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">Claimer Details</h6>
                        <p class="mb-2"><strong>Name:</strong> <span id="modalClaimerName"></span></p>
                        <p class="mb-2"><strong>Email:</strong> <span id="modalClaimerEmail"></span></p>
                        <p class="mb-2"><strong>Phone:</strong> <span id="modalClaimerPhone"></span></p>
                    </div>
                </div>
                <hr>
                <div class="mb-4">
                    <h6 class="mb-3">Claim Evidence</h6>
                    <p id="modalEvidenceDesc"></p>
                    <div id="modalEvidenceImage"></div>
                </div>
                <hr>
                <div class="alert alert-info border-0">
                    <strong>Current Status:</strong> 
                    <span id="modalStatus" class="badge"></span>
                    <span id="modalApprover"></span>
                </div>
                <div id="modalActionButtons"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function viewClaim(claim) {
    // Fill modal with claim data
    document.getElementById('modalClaimId').textContent = claim.id;
    document.getElementById('modalItemTitle').textContent = claim.item_title || 'N/A';
    document.getElementById('modalCategory').textContent = claim.category || 'N/A';
    document.getElementById('modalReporterName').textContent = claim.reporter_name || 'N/A';
    document.getElementById('modalClaimerName').textContent = claim.claimer_name || 'N/A';
    document.getElementById('modalClaimerEmail').textContent = claim.claimer_email || 'N/A';
    document.getElementById('modalClaimerPhone').textContent = claim.phone || 'N/A';
    
    // Handle evidence
    if (claim.proof_description.includes(' | Evidence: ')) {
        const parts = claim.proof_description.split(' | Evidence: ');
        document.getElementById('modalEvidenceDesc').textContent = parts[0];
        if (parts[1] && parts[1].includes('/uploads')) {
            document.getElementById('modalEvidenceImage').innerHTML = 
                '<img src="<?php echo BASE_URL; ?>/' + parts[1] + '" class="img-fluid rounded" alt="Evidence" style="max-width: 300px;">';
        } else {
            document.getElementById('modalEvidenceImage').innerHTML = '';
        }
    } else {
        document.getElementById('modalEvidenceDesc').textContent = claim.proof_description;
        document.getElementById('modalEvidenceImage').innerHTML = '';
    }
    
    // Status badge
    const statusBadge = document.getElementById('modalStatus');
    statusBadge.textContent = claim.status.charAt(0).toUpperCase() + claim.status.slice(1);
    statusBadge.className = 'badge bg-' + 
        (claim.status === 'pending' ? 'warning' : 
         (claim.status === 'approved' ? 'success' : 'danger'));
    
    // Approver info
    const approverSpan = document.getElementById('modalApprover');
    if (claim.status === 'approved' && claim.approver_name) {
        approverSpan.innerHTML = '<br><small style="color: #28a745; margin-top: 8px; display: inline-block;"><i class="bi bi-check-circle"></i> Approved by: <strong>' + claim.approver_name + '</strong></small>';
    } else {
        approverSpan.innerHTML = '';
    }
    
    // Action buttons
    const actionDiv = document.getElementById('modalActionButtons');
    if (claim.status === 'pending') {
        actionDiv.innerHTML = '<div class="d-flex gap-2">' +
            '<button type="button" class="btn btn-success flex-grow-1" onclick="approveClaim(' + claim.id + ', ' + claim.item_id + ', \'' + claim.claimer_name + '\')">' +
            '<i class="bi bi-check-circle"></i> Approve Claim</button>' +
            '<button type="button" class="btn btn-danger flex-grow-1" onclick="rejectClaim(' + claim.id + ')">' +
            '<i class="bi bi-x-circle"></i> Reject Claim</button>' +
            '<button type="button" class="btn btn-primary flex-grow-1" onclick="sendMessage(' + claim.id + ', \'' + claim.claimer_name + '\')">' +
            '<i class="bi bi-chat"></i> Send Message</button></div>';
    } else {
        actionDiv.innerHTML = '<div class="alert alert-light border-warning"><small>This claim has already been processed and cannot be modified.</small></div>';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('claimViewModal')).show();
}

function approveClaim(claimId, itemId, claimerName) {
    alert('Collection Message Required\n\nYou MUST send a collection/pickup message to the student before approving!');
    
    window.currentApproveClaimId = claimId;
    window.isApprovalMessage = true;
    
    document.getElementById('messageLabel').textContent = 'Collection/Pickup Instructions for ' + claimerName;
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Claim rejected successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.message || 'Failed to reject claim'));
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Yes, Reject';
        }
    })
    .catch(error => {
        alert('❌ Error: ' + error.message);
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Yes, Reject';
    });
}

function sendMessage(claimId, claimerName) {
    window.currentClaimId = claimId;
    window.isApprovalMessage = false;
    
    document.getElementById('messageLabel').textContent = 'Send Message to ' + claimerName;
    document.getElementById('messageContent').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('sendMessageBtn').textContent = 'Send Message';
    document.getElementById('sendMessageBtn').className = 'btn btn-primary';
    document.getElementById('sendMessageBtn').disabled = false;
    
    let messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    messageModal.show();
    
    setTimeout(() => {
        document.getElementById('messageContent').focus();
    }, 500);
}

// Submit message function
function submitMessage() {
    const messageText = document.getElementById('messageContent').value.trim();
    if (!messageText) {
        alert('Please enter a message');
        return;
    }
    
    const claimId = window.currentApproveClaimId || window.currentClaimId;
    const isApproval = window.isApprovalMessage;
    
    document.getElementById('sendMessageBtn').disabled = true;
    document.getElementById('sendMessageBtn').textContent = 'Sending...';
    
    fetch('<?php echo BASE_URL; ?>/actions/send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'claim_id=' + claimId + '&message=' + encodeURIComponent(messageText) + '&is_collection_message=' + (isApproval ? '1' : '0')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isApproval) {
                // Approve the claim after sending message
                fetch('<?php echo BASE_URL; ?>/actions/approve_claim.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'claim_id=' + claimId
                })
                .then(response => {
                    console.log('Approve response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Approve raw response:', text);
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
                location.reload();
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
}
</script>

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

.table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}
</style>

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

<?php require_once '../includes/footer.php'; ?>
