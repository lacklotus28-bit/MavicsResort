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
    $status = $data['status'] ?? null;
    
    if (empty($userId) || empty($status)) {
        throw new Exception('User ID and status are required');
    }
    
    if (!in_array($status, ['active', 'inactive', 'blocked'])) {
        throw new Exception('Invalid status value');
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE customers SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $userId]);
    
    // Log the activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
        VALUES (:admin_id, 'user_status_change', 'customers', :user_id, :ip, :user_agent)
    ");
    
    $logStmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    $_SESSION['success_message'] = 'User status updated successfully';
    
    echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
