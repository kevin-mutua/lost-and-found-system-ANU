<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if (hasRole('admin')) {
        header('Location: admin/dashboard.php');
    } elseif (hasRole('security')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}
require_once 'includes/header.php';
?>

<?php
require_once 'includes/footer.php';
?>