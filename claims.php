<?php
session_start();

// Prevent browser caching for this dynamic page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

if (!hasRole('student')) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit();
}

require_once 'includes/header.php';

try {
    // Fetch all claims for the current user
    $stmt = $pdo->prepare("
        SELECT c.*, i.title, i.image_path, i.location, i.category, i.type, u.name as reported_by
        FROM claims c
        JOIN items i ON c.item_id = i.id
        JOIN users u ON i.user_id = u.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $all_claims = $stmt->fetchAll();
    
    // Separate claims by status
    $pending_claims = [];
    $approved_claims = [];
    $rejected_claims = [];
    
    foreach ($all_claims as $claim) {
        if ($claim['status'] === 'pending') {
            $pending_claims[] = $claim;
        } elseif ($claim['status'] === 'approved') {
            $approved_claims[] = $claim;
        } elseif ($claim['status'] === 'rejected') {
            $rejected_claims[] = $claim;
        }
    }
} catch (Exception $e) {
    $pending_claims = [];
    $approved_claims = [];
    $rejected_claims = [];
}
?>

<div class="container-lg mt-5" style="max-width: 1100px; margin-left: auto; margin-right: auto;">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="bi bi-clipboard-check me-2"></i>My Claims</h2>
            <p style="color: #666; font-size: 15px; margin-bottom: 24px;">
                <i class="bi bi-info-circle" style="color: #fac923; margin-right: 6px;"></i>
                Track the status of your claims for found items. Once approved, visit the security office or check messages for collection details.
            </p>
        </div>
    </div>

    <!-- Pending Claims -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; padding: 1.5rem;">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split me-2"></i>Pending Claims
                        <span class="badge bg-white text-warning ms-2"><?php echo count($pending_claims); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_claims)): ?>
                        <p class="text-muted text-center py-4">No pending claims</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($pending_claims as $claim): ?>
                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100 border" style="max-height: 200px;">
                                        <div class="row g-0 h-100">
                                            <div class="col-3">
                                                <?php if ($claim['image_path']): ?>
                                                    <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $claim['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($claim['title']); ?>" 
                                                         class="img-fluid rounded-start h-100" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light h-100 d-flex align-items-center justify-content-center rounded-start">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-9">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($claim['title']); ?></h6>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($claim['category']); ?>
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($claim['location']); ?>
                                                    </small>
                                                    <p class="text-warning small mb-1" style="font-size: 0.75rem;">
                                                        <i class="bi bi-clock"></i> Pending
                                                    </p>
                                                    <button type="button" class="btn btn-xs btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="viewClaimDetails(<?php echo $claim['id']; ?>)">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Claims -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1.5rem;">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>Approved Claims
                        <span class="badge bg-white text-success ms-2"><?php echo count($approved_claims); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($approved_claims)): ?>
                        <p class="text-muted text-center py-4">No approved claims</p>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Congratulations!</strong> Your claim has been approved. 
                            <br><small>Please check your messages for further instructions or visit the ANU Security Office for item collection.</small>
                        </div>
                        <div class="row">
                            <?php foreach ($approved_claims as $claim): ?>
                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100 border border-success" style="max-height: 200px;">
                                        <div class="row g-0 h-100">
                                            <div class="col-3">
                                                <?php if ($claim['image_path']): ?>
                                                    <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $claim['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($claim['title']); ?>" 
                                                         class="img-fluid rounded-start h-100" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light h-100 d-flex align-items-center justify-content-center rounded-start">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-9">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($claim['title']); ?></h6>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($claim['category']); ?>
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($claim['location']); ?>
                                                    </small>
                                                    <p class="text-success small mb-1" style="font-size: 0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Approved
                                                    </p>
                                                    <button type="button" class="btn btn-xs btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="viewClaimDetails(<?php echo $claim['id']; ?>)">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejected Claims -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 1.5rem;">
                    <h5 class="mb-0">
                        <i class="bi bi-x-circle me-2"></i>Rejected Claims
                        <span class="badge bg-white text-danger ms-2"><?php echo count($rejected_claims); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rejected_claims)): ?>
                        <p class="text-muted text-center py-4">No rejected claims</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($rejected_claims as $claim): ?>
                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100 border border-danger" style="max-height: 200px;">
                                        <div class="row g-0 h-100">
                                            <div class="col-3">
                                                <?php if ($claim['image_path']): ?>
                                                    <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $claim['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($claim['title']); ?>" 
                                                         class="img-fluid rounded-start h-100" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light h-100 d-flex align-items-center justify-content-center rounded-start">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-9">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($claim['title']); ?></h6>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($claim['category']); ?>
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($claim['location']); ?>
                                                    </small>
                                                    <p class="text-danger small mb-1" style="font-size: 0.75rem;">
                                                        <i class="bi bi-x-circle"></i> Rejected
                                                    </p>
                                                    <button type="button" class="btn btn-xs btn-outline-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="viewClaimDetails(<?php echo $claim['id']; ?>)">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Claim Details Modal -->
<div class="modal fade" id="claimDetailsModal" tabindex="-1" aria-labelledby="claimDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimDetailsLabel">Claim Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="claimDetailsContent">
                <!-- Claim details will be populated here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const claimDetailsModal = new bootstrap.Modal(document.getElementById('claimDetailsModal'));
    
    function viewClaimDetails(claimId) {
        fetch('<?php echo BASE_URL; ?>/actions/get_claim_details.php?claim_id=' + claimId)
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', data.error || 'Failed to load claim details', 'error');
                    return;
                }
                if (data.success) {
                    const claim = data.claim;
                    const claimDate = new Date(claim.created_at).toLocaleDateString();
                    
                    // Parse proof_description to extract reason and evidence path
                    const descParts = claim.proof_description.split(' | Evidence: ');
                    const reason = descParts[0] || 'No reason provided';
                    const evidencePath = descParts[1] || null;
                    
                    let detailsHTML = `
                        <div class="claim-details-container">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Claim Information</h6>
                                    <p><strong>Item:</strong> ${claim.item_title}</p>
                                    <p><strong>Date Claimed:</strong> ${claimDate}</p>
                                    <p><strong>Status:</strong> <span class="badge ${claim.status === 'pending' ? 'bg-warning' : claim.status === 'approved' ? 'bg-success' : 'bg-danger'}">${claim.status.toUpperCase()}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Your Reason</h6>
                                    <p class="text-muted">${reason}</p>
                                </div>
                            </div>
                            
                            ${evidencePath ? `
                                <div class="mb-4">
                                    <h6>Evidence Provided</h6>
                                    <img src="<?php echo BASE_URL; ?>/${evidencePath}" alt="Evidence" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                </div>
                            ` : ''}
                            
                            ${claim.status === 'approved' ? `
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Approved!</strong> Check your messages for collection details or visit the ANU Security Office.
                                </div>
                            ` : claim.status === 'rejected' ? `
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle me-2"></i>
                                    <strong>Claim Rejected</strong> - This claim was not approved. Please contact support for more information.
                                </div>
                            ` : `
                                <div class="alert alert-warning">
                                    <i class="bi bi-hourglass-split me-2"></i>
                                    <strong>Pending</strong> - Your claim is being reviewed. Check back soon!
                                </div>
                            `}
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
</script>

<?php require_once 'includes/footer.php'; ?>
