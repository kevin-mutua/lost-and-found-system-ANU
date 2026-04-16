<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Redirect authenticated users to their dashboard
if (isset($_SESSION['user_id'])) {
    if (hasRole('admin')) {
        header('Location: ' . BASE_URL . '/admin.php');
    } elseif (hasRole('security')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit();
}

// Unify /auth/login.php with the main landing page
header('Location: ' . BASE_URL . '/index.php');
exit();
