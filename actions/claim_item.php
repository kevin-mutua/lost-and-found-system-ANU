<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to submit a claim';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $claim_description = isset($_POST['claim_description']) ? trim($_POST['claim_description']) : '';
    $evidence = $_FILES['evidence'] ?? null;

    if (empty($item_id)) {
        $response['message'] = 'Item ID is required';
        error_log("CLAIM_ITEM: Missing item_id");
        echo json_encode($response);
        exit();
    }
    
    if (!$evidence || $evidence['error'] === UPLOAD_ERR_NO_FILE) {
        $response['message'] = 'Please select an evidence file to upload';
        error_log("CLAIM_ITEM: No file selected");
        echo json_encode($response);
        exit();
    }

    if ($evidence['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Error uploading file. Error code: ' . $evidence['error'];
        error_log("CLAIM_ITEM: File upload error: " . $evidence['error']);
        echo json_encode($response);
        exit();
    }

    try {
        // Check if item exists and is claimable
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND status = 'open'");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $response['message'] = 'Item not found or not claimable';
            error_log("CLAIM_ITEM: Item not found or not open, status check failed for item_id=$item_id");
            echo json_encode($response);
            exit();
        }

        error_log("CLAIM_ITEM: Item found: " . $item['title']);

        // Handle evidence upload
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024;

        if (!in_array($evidence['type'], $allowed_types)) {
            throw new Exception('Invalid evidence format. Only JPG, PNG, and GIF are allowed.');
        }

        if ($evidence['size'] > $max_size) {
            throw new Exception('Evidence size must not exceed 2MB');
        }

        $evidence_name = uniqid() . '_' . basename($evidence['name']);
        $upload_dir = '../uploads/claims/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($evidence['tmp_name'], $upload_dir . $evidence_name)) {
            throw new Exception('Failed to save evidence file.');
        }

        error_log("CLAIM_ITEM: File uploaded to: " . $upload_dir . $evidence_name);

        $evidence_path = 'uploads/claims/' . $evidence_name;

        // Insert claim
        $stmt = $pdo->prepare("INSERT INTO claims (item_id, user_id, proof_description) VALUES (?, ?, ?)");
        $stmt->execute([$item_id, $_SESSION['user_id'], $claim_description . ' | Evidence: ' . $evidence_path]);
        
        // Get the inserted claim ID
        $claim_id = $pdo->lastInsertId();
        error_log("CLAIM_ITEM: Claim inserted with ID: $claim_id");

        $response['success'] = true;
        $response['message'] = 'Claim submitted successfully! You will receive a notification when the item is approved.';
        
        // Send response IMMEDIATELY - BEFORE any other operations
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen(json_encode($response)));
        header('Connection: close');
        
        // Output the response
        echo json_encode($response);
        error_log("CLAIM_ITEM: Sending success response");
        
        // Ensure output gets to client
        ob_flush();
        flush();
        
        // === All operations after this point are DISCONNECTED from response ===
        // The client has already received success and response is complete
        
        // Log activity (moved here to avoid affecting response)
        try {
            logActivity($pdo, 'claim_submitted', "Claim submitted for item: {$item['title']}", 'claim', $claim_id);
        } catch (Exception $log_error) {
            error_log("CLAIM_ITEM: Error logging activity: " . $log_error->getMessage());
        }
        
        // Now do the notification tasks (these don't affect the client response)
        error_log("CLAIM_ITEM: Starting background notification tasks");
        
        // Do notifications in background (not critical to response)
        try {
            // Notify the item reporter about the new claim
            notifyItemClaimed($item_id, $_SESSION['user_id']);
            error_log("CLAIM_ITEM: Notification sent to item reporter");
        } catch (Exception $notif_error) {
            error_log("CLAIM_ITEM: Error notifying item reporter: " . $notif_error->getMessage());
        }
        
        // Notify security officers and admin about the new claim
        try {
            $claimer_name = $_SESSION['user_name'] ?? 'A student';
            $item_title = $item['title'] ?? 'Unknown item';
            $notification_title = "New Claim Submitted! 📝";
            $notification_message = "{$claimer_name} has submitted a claim for: {$item_title}. Please review and approve or reject.";
            
            $stmt_security = $pdo->prepare("
                SELECT id FROM users WHERE role IN ('security', 'admin')
            ");
            $stmt_security->execute();
            $security_officers = $stmt_security->fetchAll();
            
            error_log("CLAIM_ITEM: Found " . count($security_officers) . " security/admin users to notify");
            
            foreach ($security_officers as $officer) {
                createNotification(
                    $officer['id'],
                    $notification_title,
                    $notification_message,
                    'claim',
                    $item_id,
                    $_SESSION['user_id'],
                    BASE_URL . '/admin/claims.php'
                );
            }
        } catch (Exception $notif_error) {
            // Notification error - continue anyway
        }
        
        error_log("CLAIM_ITEM: Claim submission SUCCESS - exiting");
        exit();
        
    } catch (Exception $e) {
        error_log("CLAIM_ITEM: Exception caught - " . $e->getMessage());
        $response['message'] = $e->getMessage();
        http_response_code(500);
        echo json_encode($response);
        exit();
    }
    
} else {
    $response['message'] = 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'];
    error_log("CLAIM_ITEM: Invalid request method");
    echo json_encode($response);
}
?>
