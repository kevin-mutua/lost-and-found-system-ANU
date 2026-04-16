<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has a specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}

// Redirect if not security
function requireSecurity() {
    requireLogin();
    if (!hasRole('security')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}
