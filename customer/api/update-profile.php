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
$required_fields = ['first_name', 'last_name', 'email', 'phone'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            'success' => false,
            'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
        ]);
        exit;
    }
}

$user_id = $_SESSION['user_id'];
$first_name = trim($input['first_name']);
$last_name = trim($input['last_name']);
$email = trim($input['email']);
$phone = trim($input['phone']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email is already used by another customer
    $check_query = "SELECT id FROM customers WHERE email = :email AND id != :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is already in use by another account'
        ]);
        exit;
    }
    
    // Update customer profile  
    $query = "UPDATE customers 
              SET first_name = :first_name,
                  last_name = :last_name,
                  email = :email,
                  phone = :phone,
                  updated_at = NOW()
              WHERE id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;
        
        // Log activity
        try {
            $log_query = "INSERT INTO activity_log (user_id, activity_type, activity_title, activity_description, created_at) 
                          VALUES (:user_id, 'profile_update', 'Profile Updated', 'You updated your profile information', NOW())";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->bindParam(':user_id', $user_id);
            $log_stmt->execute();
        } catch (PDOException $e) {
            // Ignore activity log errors
            error_log("Activity log error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone
            ]
        ]);
    } else {
        throw new Exception('Failed to update profile');
    }
    
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
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