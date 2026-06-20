<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['current_password']) || empty($input['new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Current password and new password are required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $input['current_password'];
$new_password = $input['new_password'];

// Validate new password strength
if (strlen($new_password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be at least 8 characters long'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get current password from database
    $query = "SELECT password_hash as password FROM customers WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_query = "UPDATE customers 
                     SET password_hash = :password,
                         updated_at = NOW()
                     WHERE id = :user_id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':password', $hashed_password);
    $update_stmt->bindParam(':user_id', $user_id);
    
    if ($update_stmt->execute()) {
        // Log activity
        try {
            $log_query = "INSERT INTO activity_log (user_id, activity_type, activity_title, activity_description, created_at) 
                          VALUES (:user_id, 'password_change', 'Password Changed', 'You changed your account password', NOW())";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->bindParam(':user_id', $user_id);
            $log_stmt->execute();
        } catch (PDOException $e) {
            // Ignore activity log errors
            error_log("Activity log error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        throw new Exception('Failed to change password');
    }
    
} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>