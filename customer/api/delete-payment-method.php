<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $methodId = $data['method_id'] ?? 0;
    
    if (!$methodId) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment method ID']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Verify the payment method belongs to the user
    $stmt = $conn->prepare("SELECT id, is_default FROM customer_payment_methods WHERE id = ? AND customer_id = ?");
    $stmt->execute([$methodId, $userId]);
    $method = $stmt->fetch();
    
    if (!$method) {
        echo json_encode(['success' => false, 'message' => 'Payment method not found']);
        exit();
    }
    
    // Delete the payment method
    $stmt = $conn->prepare("DELETE FROM customer_payment_methods WHERE id = ?");
    $stmt->execute([$methodId]);
    
    // If it was the default, set another one as default
    if ($method['is_default']) {
        $stmt = $conn->prepare("
            SELECT id FROM customer_payment_methods 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $newDefault = $stmt->fetch();
        
        if ($newDefault) {
            $stmt = $conn->prepare("UPDATE customer_payment_methods SET is_default = 1 WHERE id = ?");
            $stmt->execute([$newDefault['id']]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Payment method removed successfully']);
    
} catch (PDOException $e) {
    error_log("Error deleting payment method: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to remove payment method']);
}
?>
