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
    $status = $data['status'] ?? null;
    
    if (empty($userIds) || !is_array($userIds)) {
        throw new Exception('User IDs are required');
    }
    
    if (empty($status) || !in_array($status, ['active', 'inactive', 'blocked'])) {
        throw new Exception('Invalid status value');
    }
    
    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
    
    // Update user statuses
    $stmt = $conn->prepare("UPDATE customers SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
    $params = array_merge([$status], $userIds);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    // Log the activity
    foreach ($userIds as $userId) {
        $logStmt = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
            VALUES (:admin_id, 'bulk_status_change', 'customers', :user_id, :ip, :user_agent)
        ");
        
        $logStmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    $_SESSION['success_message'] = "$affectedRows user(s) status updated successfully";
    
    echo json_encode([
        'success' => true, 
        'message' => "$affectedRows user(s) status updated successfully",
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
