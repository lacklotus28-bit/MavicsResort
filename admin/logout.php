<?php
// Logout Handler - Mavic's Resort Admin
require_once 'config/database.php';
require_once 'classes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $auth = new Auth();
    
    // Check if user is logged in
    if ($auth->isAdminLoggedIn()) {
        // Log the logout activity
        $admin = $auth->getCurrentAdmin();
        if ($admin) {
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs (admin_id, action, ip_address, user_agent) 
                    VALUES (?, 'admin_logout', ?, ?)
                ");
                $stmt->execute([
                    $admin['id'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (Exception $e) {
                error_log("Logout logging error: " . $e->getMessage());
            }
        }
        
        // Perform logout
        $auth->adminLogout();
    }
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
}

// Always redirect to login page
header("Location: admin-login.php?logout=success");
exit;
?>