<?php
// Format status for display
function formatStatus($status) {
    $statuses = [
        'reported' => 'Reported',
        'matched' => 'Matched',
        'verified' => 'Verified',
        'collected' => 'Collected',
        'open' => 'Open',
        'claimed' => 'Claimed'
    ];
    return $statuses[$status] ?? ucfirst($status);
}

// Format type for display
function formatType($type) {
    return ucfirst($type);
}

// Format date for display
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Get role name
function getRoleName() {
    if (!isset($_SESSION['user_role'])) {
        return 'Guest';
    }

    $roles = [
        'student' => 'Student',
        'security' => 'Security Officer',
        'admin' => 'Administrator'
    ];

    return $roles[$_SESSION['user_role']] ?? ucfirst($_SESSION['user_role']);
}

// Get dashboard redirect based on role
function getDashboardRedirect() {
    if (hasRole('admin')) {
        return 'admin/dashboard.php';
    } elseif (hasRole('security')) {
        return 'dashboard.php';
    } else {
        return 'dashboard.php';
    }
}

// Log activity for comprehensive audit trail
function logActivity($pdo, $action_type, $description, $entity_type = null, $entity_id = null, $old_value = null, $new_value = null) {
    try {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action_type, description, entity_type, entity_id, old_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $user_id,
            $action_type,
            $description,
            $entity_type,
            $entity_id,
            $old_value,
            $new_value,
            $ip_address,
            $user_agent
        ]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}
