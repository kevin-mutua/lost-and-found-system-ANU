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

// Auto-initialize all existing users to is_active = 1 if column was just added
try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
    $stmt->execute();
} catch(Exception $e) {
    // Column might not exist yet, ignore
}

// Add course column if it doesn't exist (migration)
try {
    $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN course VARCHAR(255) DEFAULT 'N/A'");
    $stmt->execute();
} catch(Exception $e) {
    // Column already exists, ignore
}

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_role') {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $new_role = isset($_POST['role']) ? $_POST['role'] : '';
        
        if ($user_id > 0 && in_array($new_role, ['student', 'security', 'admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id])) {
                $_SESSION['success'] = 'User role updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update user role.';
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit();
    } elseif ($_POST['action'] === 'toggle_status') {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
            // Get current status
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $new_status = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $user_id])) {
                $_SESSION['success'] = 'User status updated successfully!';
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit();
    } elseif ($_POST['action'] === 'delete_user') {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $_SESSION['success'] = 'User deleted successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to delete user.';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit();
    } elseif ($_POST['action'] === 'update_course') {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $course = isset($_POST['course']) ? trim($_POST['course']) : '';
        
        if ($user_id > 0 && !empty($course)) {
            $stmt = $pdo->prepare("UPDATE users SET course = ? WHERE id = ?");
            if ($stmt->execute([$course, $user_id])) {
                $_SESSION['success'] = 'Course updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update course.';
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit();
    }
}

// Get all users
$stmt = $pdo->prepare("
    SELECT id, registration_id, name, email, phone, role, is_active, course, created_at 
    FROM users 
    ORDER BY created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Count users by role
$stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stmt->execute();
$roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/header.php';
?><div class="container-lg admin-dashboard py-5" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; max-width: 1100px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 style="color: #ed1c24;"><i class="bi bi-people"></i> User Management</h2>
                    <p class="text-muted">Manage students, security personnel, and admin accounts</p>
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
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- User Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #ed1c24;">
                <div class="card-body p-3">
                    <h4 style="color: #ed1c24; font-weight: 700; font-size: 1rem;"><?php echo count($users); ?></h4>
                    <p class="mb-0 small text-muted">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #fac923;">
                <div class="card-body p-3">
                    <h4 style="color: #fac923; font-weight: 700; font-size: 1rem;"><?php echo $roleCounts['student'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Students</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #000000;">
                <div class="card-body p-3">
                    <h4 style="color: #000000; font-weight: 700; font-size: 1rem;"><?php echo $roleCounts['security'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Security</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-3" style="background: white; border-left: 4px solid #ed1c24;">
                <div class="card-body p-3">
                    <h4 style="color: #ed1c24; font-weight: 700; font-size: 1rem;"><?php echo $roleCounts['admin'] ?? 0; ?></h4>
                    <p class="mb-0 small text-muted">Admins</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); color: white; padding: 1.5rem;">
            <h6 class="mb-0"><i class="bi bi-table"></i> All Users</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #ed1c24;">
                        <tr>
                            <th class="p-3">User Info</th>
                            <th class="p-3">Phone</th>
                            <th class="p-3">Course</th>
                            <th class="p-3">Joined</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Actions</th>
                            <th class="p-3 text-center" style="width: 50px;">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="p-3" style="font-weight: 600;">
                                        <div style="font-size: 1rem; margin-bottom: 5px;">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                <span class="badge bg-secondary">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #ed1c24; margin-bottom: 3px;">
                                            <strong><?php echo htmlspecialchars($user['registration_id'] ?? 'N/A'); ?></strong>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666; margin-bottom: 3px;">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                        <div style="font-size: 0.8rem;">
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                     ($user['role'] === 'security' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-muted small"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td class="p-3">
                                        <?php if ($user['role'] === 'student'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_course">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="course" class="form-select form-select-sm" style="width: 140px; display: inline-block; font-size: 0.85rem;" onchange="this.form.submit();">
                                                <option value="">Select</option>
                                                <!-- School of Law -->
                                                <optgroup label="Law">
                                                    <option value="Bachelor of Laws (LLB)" <?php echo ($user['course'] === 'Bachelor of Laws (LLB)') ? 'selected' : ''; ?>>LLB</option>
                                                    <option value="Bachelor of Arts in Criminal Justice Security Studies" <?php echo ($user['course'] === 'Bachelor of Arts in Criminal Justice Security Studies') ? 'selected' : ''; ?>>Criminal Justice</option>
                                                </optgroup>
                                                <!-- School of Business -->
                                                <optgroup label="Business">
                                                    <option value="PhD in Business Administration and Management" <?php echo ($user['course'] === 'PhD in Business Administration and Management') ? 'selected' : ''; ?>>PhD Business</option>
                                                    <option value="Master of Business Administration (MBA)" <?php echo ($user['course'] === 'Master of Business Administration (MBA)') ? 'selected' : ''; ?>>MBA</option>
                                                    <option value="Bachelor of Commerce (Accounting)" <?php echo ($user['course'] === 'Bachelor of Commerce (Accounting)') ? 'selected' : ''; ?>>B.Com Accounting</option>
                                                    <option value="Bachelor of Commerce (Banking and Finance)" <?php echo ($user['course'] === 'Bachelor of Commerce (Banking and Finance)') ? 'selected' : ''; ?>>B.Com Finance</option>
                                                    <option value="Bachelor of Commerce (Marketing Management)" <?php echo ($user['course'] === 'Bachelor of Commerce (Marketing Management)') ? 'selected' : ''; ?>>B.Com Marketing</option>
                                                    <option value="Bachelor of Science in International Business Management" <?php echo ($user['course'] === 'Bachelor of Science in International Business Management') ? 'selected' : ''; ?>>Int'l Business</option>
                                                    <option value="Bachelor of Human Resource Management" <?php echo ($user['course'] === 'Bachelor of Human Resource Management') ? 'selected' : ''; ?>>HRM</option>
                                                    <option value="Certificate in Business Management" <?php echo ($user['course'] === 'Certificate in Business Management') ? 'selected' : ''; ?>>Cert Business</option>
                                                    <option value="Certified Procurement and Supply Professional of Kenya (CPSP-K)" <?php echo ($user['course'] === 'Certified Procurement and Supply Professional of Kenya (CPSP-K)') ? 'selected' : ''; ?>>CPSP-K</option>
                                                </optgroup>
                                                <!-- School of Science and Technology -->
                                                <optgroup label="Science & Tech">
                                                    <option value="Master of Science in Applied Information Technology (MSc. AIT)" <?php echo ($user['course'] === 'Master of Science in Applied Information Technology (MSc. AIT)') ? 'selected' : ''; ?>>MSc AIT</option>
                                                    <option value="Master of Science in Environmental Resource Management (MSc. ERM)" <?php echo ($user['course'] === 'Master of Science in Environmental Resource Management (MSc. ERM)') ? 'selected' : ''; ?>>MSc ERM</option>
                                                    <option value="Bachelor of Science in Computer Science" <?php echo ($user['course'] === 'Bachelor of Science in Computer Science') ? 'selected' : ''; ?>>BSc CS</option>
                                                    <option value="Bachelor of Business and Information Technology" <?php echo ($user['course'] === 'Bachelor of Business and Information Technology') ? 'selected' : ''; ?>>BBIT</option>
                                                    <option value="Bachelor of Science in Procurement and Supply Chain Management" <?php echo ($user['course'] === 'Bachelor of Science in Procurement and Supply Chain Management') ? 'selected' : ''; ?>>Supply Chain</option>
                                                    <option value="Bachelor of Science in Environmental and Natural Resource Management" <?php echo ($user['course'] === 'Bachelor of Science in Environmental and Natural Resource Management') ? 'selected' : ''; ?>>Env Management</option>
                                                    <option value="Diploma in Information Technology" <?php echo ($user['course'] === 'Diploma in Information Technology') ? 'selected' : ''; ?>>Dip IT</option>
                                                    <option value="Diploma in Mobile Computing" <?php echo ($user['course'] === 'Diploma in Mobile Computing') ? 'selected' : ''; ?>>Dip Mobile</option>
                                                    <option value="International Advanced Diploma in Computer Studies (IADCS)" <?php echo ($user['course'] === 'International Advanced Diploma in Computer Studies (IADCS)') ? 'selected' : ''; ?>>IADCS</option>
                                                </optgroup>
                                                <!-- School of Humanities and Social Sciences -->
                                                <optgroup label="Humanities">
                                                    <option value="Bachelor of Arts in Peace and Conflict Studies" <?php echo ($user['course'] === 'Bachelor of Arts in Peace and Conflict Studies') ? 'selected' : ''; ?>>Peace Studies</option>
                                                    <option value="Bachelor of Mass Communication (Electronic)" <?php echo ($user['course'] === 'Bachelor of Mass Communication (Electronic)') ? 'selected' : ''; ?>>Mass Comm (E)</option>
                                                    <option value="Bachelor of Mass Communication (Print Media)" <?php echo ($user['course'] === 'Bachelor of Mass Communication (Print Media)') ? 'selected' : ''; ?>>Mass Comm (P)</option>
                                                </optgroup>
                                                <!-- Other Programs -->
                                                <optgroup label="Other">
                                                    <option value="Master of Divinity" <?php echo ($user['course'] === 'Master of Divinity') ? 'selected' : ''; ?>>M.Div</option>
                                                    <option value="Bachelor of Arts in Theology" <?php echo ($user['course'] === 'Bachelor of Arts in Theology') ? 'selected' : ''; ?>>Theology</option>
                                                    <option value="Bachelor of Arts in Education" <?php echo ($user['course'] === 'Bachelor of Arts in Education') ? 'selected' : ''; ?>>Education</option>
                                                </optgroup>
                                            </select>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                 ($user['role'] === 'security' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-muted small"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="p-3">
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <!-- Message Button -->
                                            <button type="button" class="btn btn-sm btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#messageModal" onclick="prepareMessage(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-chat-left-text"></i> Msg
                                            </button>

                                            <!-- Toggle Status Button -->
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?');">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" 
                                                        title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="bi bi-<?php echo $user['is_active'] ? 'lock' : 'unlock'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete user">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No users found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Help Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info border-0">
                <h6 class="mb-2"><i class="bi bi-info-circle"></i> User Management Guide</h6>
                <ul class="mb-0 small">
                    <li><strong>Students:</strong> Regular users who can report lost items and claim found items</li>
                    <li><strong>Security:</strong> Staff members who can moderate reports and assist in item recovery</li>
                    <li><strong>Admin:</strong> Full system access including user management, reports, and all features</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0" style="background: linear-gradient(135deg, #ed1c24 0%, #000000 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-chat-left-text"></i> Send Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-600">To: <span id="recipientName"></span></label>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600">Message <small class="text-muted">(max 300 characters)</small></label>
                    <textarea id="messageText" class="form-control" rows="5" placeholder="Type your message..." maxlength="300" style="resize: none;"></textarea>
                    <small class="text-muted d-block mt-1"><span id="charCount">0</span>/300 characters</small>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendUserMessage()">
                    <i class="bi bi-send"></i> Send Message
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedUserId = null;
let selectedUserName = null;

function prepareMessage(userId, userName) {
    selectedUserId = userId;
    selectedUserName = userName;
    document.getElementById('recipientName').textContent = userName;
    document.getElementById('messageText').value = '';
    document.getElementById('charCount').textContent = '0';
}

document.getElementById('messageText')?.addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

function sendUserMessage() {
    const message = document.getElementById('messageText').value.trim();
    
    if (!message) {
        alert('Please enter a message');
        return;
    }
    
    if (!selectedUserId) {
        alert('No user selected');
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>/actions/send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'recipient_id=' + selectedUserId + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('✓ Message sent to ' + selectedUserName);
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
                modal.hide();
            } else {
                alert('Error: ' + (data.message || 'Failed to send message'));
            }
        } catch (e) {
            alert('Error sending message. Please try again.');
            console.error('Response:', text);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
