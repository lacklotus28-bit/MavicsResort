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
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    // Check if user has active bookings
    $checkStmt = $conn->prepare("
        SELECT COUNT(*) as active_bookings 
        FROM bookings 
        WHERE customer_id = :id 
        AND status IN ('pending', 'confirmed')
    ");
    $checkStmt->execute(['id' => $userId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['active_bookings'] > 0) {
        throw new Exception('Cannot delete user with active bookings. Please cancel their bookings first.');
    }
    
    // Delete user (bookings will cascade delete due to foreign key)
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    
    // Log the activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
        VALUES (:admin_id, 'user_delete', 'customers', :user_id, :ip, :user_agent)
    ");
    
    $logStmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    $_SESSION['success_message'] = 'User deleted successfully';
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
