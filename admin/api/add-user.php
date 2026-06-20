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
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        throw new Exception('Required fields are missing');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE email = :email");
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->fetch()) {
        throw new Exception('Email is already registered');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO customers (first_name, last_name, email, phone, address, password_hash, status, email_verified)
        VALUES (:first_name, :last_name, :email, :phone, :address, :password_hash, 'active', 1)
    ");
    
    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'password_hash' => $hashedPassword
    ]);
    
    $userId = $conn->lastInsertId();
    
    // Log the activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
        VALUES (:admin_id, 'user_create', 'customers', :user_id, :ip, :user_agent)
    ");
    
    $logStmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    $_SESSION['success_message'] = 'User added successfully';
    
    echo json_encode(['success' => true, 'message' => 'User added successfully', 'user_id' => $userId]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
