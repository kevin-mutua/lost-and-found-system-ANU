<?php
/**
 * Admin role verification
 * Include this file to restrict access to admin-only pages
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

define('IS_ADMIN', true);
