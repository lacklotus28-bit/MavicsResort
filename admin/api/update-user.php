<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $conn = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $userId = $_POST['user_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($userId) || empty($firstName) || empty($lastName) || empty($email)) {
        throw new Exception('Required fields are missing');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if email is already taken by another user
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE email = :email AND id != :id");
    $checkStmt->execute(['email' => $email, 'id' => $userId]);
    if ($checkStmt->fetch()) {
        throw new Exception('Email is already in use by another user');
    }
    
    // Update user
    $stmt = $conn->prepare("
        UPDATE customers 
        SET first_name = :first_name,
            last_name = :last_name,
            email = :email,
            phone = :phone,
            address = :address,
            status = :status,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'status' => $status,
        'id' => $userId
    ]);
    
    // Log the activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
        VALUES (:admin_id, 'user_update', 'customers', :user_id, :ip, :user_agent)
    ");
    
    $logStmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    $_SESSION['success_message'] = 'User updated successfully';
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
