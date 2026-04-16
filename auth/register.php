<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if (hasRole('admin')) {
        header('Location: ' . BASE_URL . '/admin.php');
    } elseif (hasRole('security')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = sanitize($_POST['registration_id']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($registration_id) || empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Check if email or registration ID already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR registration_id = ?");
            $stmt->execute([$email, $registration_id]);
            if ($stmt->fetch()) {
                $error = 'Email or Registration ID already exists';
            } else {
                // Insert new user with default 'student' role
                $stmt = $pdo->prepare("INSERT INTO users (registration_id, name, email, phone, password, role) 
                                      VALUES (?, ?, ?, ?, ?, 'student')");
                $stmt->execute([$registration_id, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);

                $success = 'Registration successful! You can now login.';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card login-card mt-5">
                <div class="card-header text-center" style="background-color: #ed1c24; color: white; border: none;">
                    <img src="<?php echo BASE_URL; ?>/assets/images/lostfound.png" alt="ANU Lost and Found" class="login-logo mb-3">
                    <p class="mb-0">Create an Account</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="text" name="fakeusernameremembered" value="" style="display:none;">
                        <input type="password" name="fakepasswordremembered" value="" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="registration_id" class="form-label">Registration ID</label>
                                <input type="text" class="form-control" id="registration_id" name="registration_id" placeholder="e.g. ANU123456" autocomplete="off" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Your full name" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@anu.ac.ke" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+254 7XXXXXXXX" autocomplete="off" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create your password" autocomplete="off" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account?</p>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-link">Login Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>