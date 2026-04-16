<?php
session_start();

// Prevent browser caching for this dynamic page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

// Check user role BEFORE including header
$userRole = $_SESSION['user_role'] ?? 'student';
$isSecurityOfficer = $userRole === 'security';
$isAdmin = $userRole === 'admin';

// Admins cannot report items - redirect BEFORE header output
if ($isAdmin) {
    $_SESSION['error'] = 'Admins cannot report items. Only students and security officers can report.';
    header('Location: index.php');
    exit();
}

// Safe to include header now
require_once 'includes/header.php';

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
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mt-4 mb-4">Report an Item</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-10 col-sm-12 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Item Reported Successfully! 🎉',
                                    html: '<?php echo htmlspecialchars($success); ?><br><br><strong>Your item has been added to the system.</strong><br>Our team will automatically match your item with others in our database.',
                                    confirmButtonText: 'Continue',
                                    confirmButtonColor: '#ed1c24',
                                    didOpen: (modal) => {
                                        // Auto-hide after 5 seconds
                                        setTimeout(() => {
                                            Swal.hideLoading();
                                        }, 5000);
                                    },
                                    willClose: () => {
                                        // Optionally redirect
                                        window.location.href = '<?php echo BASE_URL; ?>/dashboard.php';
                                    }
                                });
                            });
                        </script>
                    <?php endif; ?>

                    <div class="report-progress mb-4">
                        <div class="progress-step active">
                            <div class="step-circle">1</div>
                            <div class="step-label">Item Details</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle">2</div>
                            <div class="step-label">Where & What</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle">3</div>
                            <div class="step-label">Finish</div>
                        </div>
                    </div>

                    <form id="reportForm" method="POST" action="actions/process_report.php" enctype="multipart/form-data">
                        <div class="report-step active">
                            <div class="mb-3">
                                <label for="title" class="form-label">Item Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       placeholder="e.g., Black Leather Wallet" required>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Item Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select item type</option>
                                    <option value="lost" <?php echo $isSecurityOfficer ? 'disabled' : ''; ?>>Lost</option>
                                    <option value="found">Found</option>
                                </select>
                                <?php if ($isSecurityOfficer): ?>
                                <small class="text-muted"><i class="bi bi-info-circle"></i> As a security officer, you can only report found items.</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select category</option>
                                    <option value="Personal Items">Personal Items</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Documents">Documents</option>
                                    <option value="Clothing">Clothing</option>
                                    <option value="Books">Books</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="report-step">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description"
                                          rows="3" placeholder="Describe the item in detail..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <select class="form-select" id="location" name="location" onchange="handleLocationSelect(this.value, 'report')" required>
                                    <option value="">Select Location...</option>
                                    <option value="Helstrom">Helstrom</option>
                                    <option value="Old Chapel">Old Chapel</option>
                                    <option value="Computer Lab">Computer Lab</option>
                                    <option value="Moore Hall">Moore Hall</option>
                                    <option value="Cafeteria">Cafeteria</option>
                                    <option value="Dining Cafe">Dining Cafe</option>
                                    <option value="Jernighan Chapel">Jernighan Chapel</option>
                                    <option value="Harmons Building">Harmons Building</option>
                                    <option value="Day Scholar Lounge">Day Scholar Lounge</option>
                                    <option value="Entertainment Hall">Entertainment Hall</option>
                                    <option value="Parking Lot">Parking Lot</option>
                                    <option value="Sife Canteen">Sife Canteen</option>
                                    <option value="Gym">Gym</option>
                                    <option value="Pool & TV Room">Pool & TV Room</option>
                                    <option value="Rugby Pitch">Rugby Pitch</option>
                                    <option value="Basketball Court">Basketball Court</option>
                                    <option value="Lecturer Quarters">Lecturer Quarters</option>
                                    <option value="Staff Quarters">Staff Quarters</option>
                                    <option value="Student Quarters">Student Quarters</option>
                                    <option value="other">Other (specify)</option>
                                </select>
                                <input type="text" class="form-control mt-2" id="locationOther" name="locationOther" 
                                       placeholder="Enter specific location" style="display:none;">
                            </div>
                        </div>

                        <div class="report-step">
                            <div class="mb-3">
                                <label for="image" class="form-label">Add an Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Max 2MB — JPG, PNG, GIF accepted</small>
                            </div>
                            
                            <!-- Image Preview -->
                            <div id="imagePreviewContainer" class="mb-3" style="display: none;">
                                <div class="card border-secondary">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-image"></i> Image Preview</h6>
                                    </div>
                                    <div class="card-body text-center p-4">
                                        <img id="imagePreview" src="" alt="Preview" style="max-width: 100%; max-height: 400px; border-radius: 12px; object-fit: contain;">
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle-fill text-success"></i> Image selected and ready!
                                        </small>
                                        <button type="button" id="removeImageBtn" class="btn btn-sm btn-outline-danger float-end">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-light border-secondary mb-0">
                                <strong>Tip:</strong> A clear photo helps someone identify the item faster.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="button" id="prevBtn" class="btn btn-outline-secondary" style="visibility: hidden;">Back</button>
                            <div class="step-hint">Step <span id="stepCounter">1</span> of 3</div>
                            <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
                            <button type="submit" id="submitBtn" class="btn btn-danger d-none">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const steps = document.querySelectorAll('.report-step');
const progressSteps = document.querySelectorAll('.progress-step');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');
const stepCounter = document.getElementById('stepCounter');
let currentStep = 0;

function showStep(index) {
    steps.forEach((step, idx) => {
        step.classList.toggle('active', idx === index);
    });

    progressSteps.forEach((step, idx) => {
        step.classList.toggle('active', idx === index);
        step.classList.toggle('completed', idx < index);
    });

    prevBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
    nextBtn.classList.toggle('d-none', index === steps.length - 1);
    submitBtn.classList.toggle('d-none', index !== steps.length - 1);
    stepCounter.textContent = index + 1;
}

function validateCurrentStep() {
    const fields = steps[currentStep].querySelectorAll('input, select, textarea');
    for (const field of fields) {
        if (field.required && !field.value.trim()) {
            field.reportValidity();
            return false;
        }
    }
    return true;
}

nextBtn.addEventListener('click', () => {
    if (!validateCurrentStep()) return;
    currentStep = Math.min(currentStep + 1, steps.length - 1);
    showStep(currentStep);
});

prevBtn.addEventListener('click', () => {
    currentStep = Math.max(currentStep - 1, 0);
    showStep(currentStep);
});

// Handle form submission - log for debugging
const reportForm = document.getElementById('reportForm');
const imageInput = document.getElementById('image');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const imagePreview = document.getElementById('imagePreview');
const removeImageBtn = document.getElementById('removeImageBtn');

// Image preview functionality
imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid image format. Only JPG, PNG, and GIF are allowed.');
            imageInput.value = '';
            imagePreviewContainer.style.display = 'none';
            return;
        }
        
        // Validate file size (max 2MB)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Image size must not exceed 2MB');
            imageInput.value = '';
            imagePreviewContainer.style.display = 'none';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            imagePreview.src = event.target.result;
            imagePreviewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        imagePreviewContainer.style.display = 'none';
    }
});

// Remove image button
removeImageBtn.addEventListener('click', function(e) {
    e.preventDefault();
    imageInput.value = '';
    imagePreviewContainer.style.display = 'none';
    imagePreview.src = '';
});

reportForm.addEventListener('submit', function(e) {
    // Handle location - merge location values if "Other" is selected
    const locationSelect = document.getElementById('location');
    const locationOther = document.getElementById('locationOther');
    
    if (locationOther && locationOther.style.display !== 'none' && locationOther.value) {
        locationSelect.value = locationOther.value;
    }
});

showStep(currentStep);
</script>

<?php
require_once 'includes/footer.php';
?>