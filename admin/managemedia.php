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

if (($_SESSION['user_role'] ?? null) !== 'admin') {
    $_SESSION['error'] = 'Admin access required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Handle media upload
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_media') {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    
    if ($item_id > 0 && isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        try {
            // Get item details
            $stmt = $pdo->prepare("SELECT id, image_path FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                $uploadMessage = '<div class="alert alert-danger">Item not found</div>';
            } else {
                // Delete old image if exists
                if ($item['image_path'] && file_exists('../' . $item['image_path'])) {
                    unlink('../' . $item['image_path']);
                }
                
                // Upload new image
                $file = $_FILES['media'];
                $uploadDir = '../uploads/items/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
                    $newFilePath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $newFilePath)) {
                        // Update database
                        $relativePath = 'uploads/items/' . $newFileName;
                        $stmt = $pdo->prepare("UPDATE items SET image_path = ? WHERE id = ?");
                        if ($stmt->execute([$relativePath, $item_id])) {
                            $_SESSION['success'] = 'Media updated successfully!';
                            header('Location: managemedia.php');
                            exit();
                        } else {
                            unlink($newFilePath);
                            $uploadMessage = '<div class="alert alert-danger">Failed to update database</div>';
                        }
                    } else {
                        $uploadMessage = '<div class="alert alert-danger">Failed to upload file</div>';
                    }
                } else {
                    $uploadMessage = '<div class="alert alert-danger">Invalid file type. Allowed: JPG, PNG, GIF, WebP</div>';
                }
            }
        } catch (Exception $e) {
            $uploadMessage = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $uploadMessage = '<div class="alert alert-danger">Please select a file to upload</div>';
    }
}

// Get all items with images
$stmt = $pdo->prepare("
    SELECT id, title, category, type, image_path, created_at 
    FROM items 
    ORDER BY created_at DESC
");
$stmt->execute();
$items = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-lg admin-dashboard py-5" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 style="color: #ed1c24;"><i class="bi bi-image"></i> Manage Media</h2>
                    <p class="text-muted">Upload and manage item images</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($uploadMessage)): ?>
        <?php echo $uploadMessage; ?>
    <?php endif; ?>

    <!-- Items Media Grid -->
    <div class="row">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #fffef0 0%, #fffff9 100%); border-left: 5px solid #ed1c24;">
                        <!-- Item Info -->
                        <div class="card-header border-bottom-0" style="background: transparent; padding: 1.25rem;">
                            <h6 class="mb-1" style="color: #ed1c24;"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($item['title']); ?></h6>
                            <small class="text-muted d-block">
                                <strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?> | 
                                <strong>Type:</strong> <span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?>"><?php echo ucfirst($item['type']); ?></span>
                            </small>
                            <small class="text-muted d-block mt-1">
                                <strong>Added:</strong> <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                            </small>
                        </div>

                        <!-- Media Preview -->
                        <div class="card-body text-center p-3">
                            <?php if ($item['image_path'] && file_exists('../' . $item['image_path'])): ?>
                                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="img-fluid rounded" style="max-height: 250px; object-fit: cover; cursor: pointer;" 
                                     data-bs-toggle="modal" data-bs-target="#imageModal" 
                                     onclick="viewFullImage('<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($item['image_path']); ?>', '<?php echo htmlspecialchars($item['title']); ?>')">
                                <small class="d-block text-muted mt-2">Click to view full size</small>
                            <?php else: ?>
                                <div class="bg-light rounded p-5 text-muted">
                                    <i class="bi bi-image fs-1 d-block mb-2"></i>
                                    No image uploaded
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Form -->
                        <div class="card-footer bg-transparent border-top-0 p-3">
                            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="action" value="upload_media">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="file" name="media" class="form-control form-control-sm" accept="image/*" required style="flex: 1;">
                                <button type="submit" class="btn btn-sm btn-primary" title="Upload new media">
                                    <i class="bi bi-upload"></i>
                                </button>
                            </form>
                            <small class="text-muted d-block mt-2">JPG, PNG, GIF, WebP up to 5MB</small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center p-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <h5>No items found</h5>
                    <p class="text-muted">Start by creating some items in the system</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image Modal for Full Size Preview -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0" style="background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-image"></i> <span id="modalImageTitle"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-black">
                <img id="modalImage" src="" alt="" class="img-fluid w-100" style="max-height: 600px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
function viewFullImage(imageUrl, title) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('modalImageTitle').textContent = title;
}

// Show loading indicator during upload
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const button = this.querySelector('button[type="submit"]');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
        button.disabled = true;
    });
});
</script>

<?php require_once '../includes/footer.php';
