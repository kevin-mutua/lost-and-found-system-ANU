<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

$error = '';
$success = '';
$userRole = $_SESSION['user_role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $type = sanitize($_POST['type']);
    $category = sanitize($_POST['category']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $image = $_FILES['image'] ?? null;

    // Security officers can only report found items, not lost items
    if ($userRole === 'security' && $type === 'lost') {
        $_SESSION['error'] = 'Security officers can only report found items, not lost items.';
        header('Location: ../report.php');
        exit();
    }

    // Log submission for debugging
    error_log("=== REPORT SUBMISSION ===");
    error_log("POST fields received: title, type, category, description, location");
    error_log("Files received: " . (empty($_FILES) ? 'NONE' : json_encode(array_keys($_FILES))));
    if ($image) {
        error_log("Image details - name: {$image['name']}, error: {$image['error']}, size: {$image['size']}, type: {$image['type']}");
    } else {
        error_log("No image in \$_FILES");
    }

    // Validation
    if (empty($title) || empty($type) || empty($category) || empty($description) || empty($location)) {
        $error = 'All fields are required';
    } elseif ($image && $image['error'] !== UPLOAD_ERR_OK && $image['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = 'Image upload failed';
    } else {
        try {
            // Handle image upload
            $image_path = null;
            if ($image && $image['error'] === UPLOAD_ERR_OK && !empty($image['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($image['type'], $allowed_types)) {
                    $error = 'Invalid image format. Only JPG, PNG, and GIF are allowed.';
                    error_log("Image rejected - invalid type: {$image['type']}");
                } elseif ($image['size'] > $max_size) {
                    $error = 'Image size must not exceed 2MB';
                    error_log("Image rejected - too large: {$image['size']} bytes");
                } else {
                    $image_name = uniqid() . '_' . basename($image['name']);
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $upload_path = $upload_dir . $image_name;
                    
                    if (move_uploaded_file($image['tmp_name'], $upload_path)) {
                        $image_path = 'uploads/' . $image_name;
                        error_log("Image uploaded successfully: {$image_path}");
                    } else {
                        $error = 'Failed to upload image. Please check folder permissions.';
                        error_log("move_uploaded_file failed. tmp_name: {$image['tmp_name']}, target: {$upload_path}");
                        error_log("Upload dir exists: " . (is_dir($upload_dir) ? 'YES' : 'NO'));
                        error_log("Upload dir writable: " . (is_writable($upload_dir) ? 'YES' : 'NO'));
                    }
                }
            } else {
                if ($image) {
                    error_log("Image file not processed - error code: {$image['error']}");
                } else {
                    error_log("No image file submitted");
                }
            }

            if (!$error) {
                // Insert item with appropriate initial status
                // Lost items: status = 'reported' (waiting for a match)
                // Found items: status = 'open' (available for claiming)
                $initial_status = ($type === 'lost') ? 'reported' : 'open';
                
                $stmt = $pdo->prepare("INSERT INTO items (user_id, title, type, category, description, location, image_path, status) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $type, $category, $description, $location, $image_path, $initial_status]);
                
                // Get the inserted item ID
                $item_id = $pdo->lastInsertId();
                
                // Log activity
                $item_type = $type === 'lost' ? 'Lost Item' : 'Found Item';
                logActivity($pdo, 'item_reported', "$item_type reported: $title", 'item', $item_id);
                
                // Create notifications for all security and admin users
                require_once '../includes/notifications.php';
                $notif_title = ($type === 'lost') ? 'New Lost Item Report' : 'New Found Item Report';
                $notif_message = "\"$title\" was reported at " . substr($location, 0, 30) . "...";
                $notif_action_url = BASE_URL . '/search.php?view_item=' . $item_id;
                
                error_log("=== NOTIFICATION CREATION ===");
                error_log("Item ID: $item_id, Type: $type, Title: $title");
                error_log("Notification Title: $notif_title");
                error_log("Action URL: $notif_action_url");
                
                // Get all security and admin users
                try {
                    $stmt_admins = $pdo->prepare("SELECT id, name, role FROM users WHERE role IN ('security', 'admin') AND is_active = TRUE");
                    $stmt_admins->execute();
                    $admins = $stmt_admins->fetchAll();
                    
                    error_log("Found " . count($admins) . " admin/security users");
                    
                    if (count($admins) === 0) {
                        error_log("WARNING: No admin/security users found to notify");
                    }
                    
                    foreach ($admins as $admin) {
                        error_log("Creating notification for user: " . $admin['name'] . " (ID: {$admin['id']}, Role: {$admin['role']})");
                        $notif_result = createNotification($admin['id'], $notif_title, $notif_message, 'item_report', $item_id, $_SESSION['user_id'], $notif_action_url);
                        error_log("Notification creation result: " . ($notif_result ? "SUCCESS" : "FAILED"));
                    }
                    error_log("=== NOTIFICATION CREATION COMPLETE ===");
                } catch (Exception $e) {
                    error_log("ERROR creating notifications: " . $e->getMessage());
                }
                
                // Trigger automated matching system
                $match_count = autoMatchItems($item_id);

                $_SESSION['success'] = 'Item reported successfully!';
                header('Location: ../report.php');
                exit();
            }
        } catch(PDOException $e) {
            $error = 'Failed to report item. Please try again.';
        }

        if (!empty($error)) {
            $_SESSION['error'] = $error;
            header('Location: ../report.php');
            exit();
        }
    }
}

?>
