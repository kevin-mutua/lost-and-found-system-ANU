<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
requireAnyRole(['security', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id']) && isset($_POST['action'])) {
    $item_id = $_POST['item_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'verify') {
            // Verify the item
            $stmt = $pdo->prepare("UPDATE items SET status = 'verified' WHERE id = ?");
            $stmt->execute([$item_id]);
            $_SESSION['success'] = "Item verified successfully";
        } elseif ($action === 'mark_collected') {
            // Mark item as collected
            $stmt = $pdo->prepare("UPDATE items SET status = 'collected' WHERE id = ?");
            $stmt->execute([$item_id]);
            $_SESSION['success'] = "Item marked as collected successfully";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error processing item: " . $e->getMessage();
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: ../dashboard.php');
    exit();
}