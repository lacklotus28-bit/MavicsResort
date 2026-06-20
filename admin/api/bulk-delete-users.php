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
    $userIds = $data['user_ids'] ?? [];
    
    if (empty($userIds) || !is_array($userIds)) {
        throw new Exception('User IDs are required');
    }
    
    // Check if any user has active bookings
    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
    $checkStmt = $conn->prepare("
        SELECT customer_id, COUNT(*) as active_bookings 
        FROM bookings 
        WHERE customer_id IN ($placeholders)
        AND status IN ('pending', 'confirmed')
        GROUP BY customer_id
    ");
    $checkStmt->execute($userIds);
    $activeBookings = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($activeBookings)) {
        $usersWithBookings = array_column($activeBookings, 'customer_id');
        throw new Exception('Cannot delete users with active bookings (IDs: ' . implode(', ', $usersWithBookings) . '). Please cancel their bookings first.');
    }
    
    // Delete users
    $stmt = $conn->prepare("DELETE FROM customers WHERE id IN ($placeholders)");
    $stmt->execute($userIds);
    
    $affectedRows = $stmt->rowCount();
    
    // Log the activity
    foreach ($userIds as $userId) {
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
            VALUES (:admin_id, 'bulk_delete', 'customers', :user_id, :ip, :user_agent)
        ");
        
        $logStmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    $_SESSION['success_message'] = "$affectedRows user(s) deleted successfully";
    
    echo json_encode([
        'success' => true, 
        'message' => "$affectedRows user(s) deleted successfully",
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
