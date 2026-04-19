<?php
session_start();
define('BASE_URL', '/lost_and_found');
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Get action (login or register)
$action = sanitize($_POST['action'] ?? 'login');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // REGISTER ACTION
    if ($action === 'register') {
        $registration_id = sanitize($_POST['registration_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($registration_id) || empty($name) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = 'All fields are required';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = 'Invalid email format';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } elseif ($password !== $confirm_password) {
            $_SESSION['register_error'] = 'Passwords do not match';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } elseif (strlen($password) < 6) {
            $_SESSION['register_error'] = 'Password must be at least 6 characters long';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } else {
            try {
                // Check if email or registration ID already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR registration_id = ?");
                $stmt->execute([$email, $registration_id]);
                if ($stmt->fetch()) {
                    $_SESSION['register_error'] = 'Email or Registration ID already exists';
                    header('Location: ' . BASE_URL . '/index.php');
                    exit();
                } else {
                    // Insert new user with default 'student' role and is_active = 1
                    $stmt = $pdo->prepare("INSERT INTO users (registration_id, name, email, password, role, is_active) 
                                          VALUES (?, ?, ?, ?, 'student', 1)");
                    $stmt->execute([$registration_id, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);

                    $_SESSION['login_error'] = 'Registration successful! Please login with your credentials.';
                    header('Location: ' . BASE_URL . '/index.php');
                    exit();
                }
            } catch(PDOException $e) {
                $_SESSION['register_error'] = 'Registration failed. Please try again.';
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            }
        }
    } 
    
    // LOGIN ACTION (default)
    else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check if user is deactivated
                if ($user['is_active'] == 0) {
                    $_SESSION['login_error'] = 'Your account has been deactivated. Contact admin.';
                    header('Location: ' . BASE_URL . '/index.php');
                    exit();
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['registration_id'] = $user['registration_id'];
                $_SESSION['phone'] = $user['phone'] ?? '';
                $_SESSION['created_at'] = $user['created_at'];

                // Redirect based on user role
                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                } elseif ($user['role'] === 'security') {
                    header('Location: ' . BASE_URL . '/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . '/dashboard.php');
                }
                exit();
            } else {
                $_SESSION['login_error'] = 'Invalid email or password';
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            }
        } catch(PDOException $e) {
            $_SESSION['login_error'] = 'Login failed. Please try again.';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }
    }
} else {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
?>