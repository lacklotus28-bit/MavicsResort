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
    $stmt = $conn->prepare("SELECT id FROM customer_payment_methods WHERE id = ? AND customer_id = ?");
    $stmt->execute([$methodId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payment method not found']);
        exit();
    }
    
    // Unset all defaults
    $stmt = $conn->prepare("UPDATE customer_payment_methods SET is_default = 0 WHERE customer_id = ?");
    $stmt->execute([$userId]);
    
    // Set new default
    $stmt = $conn->prepare("UPDATE customer_payment_methods SET is_default = 1 WHERE id = ?");
    $stmt->execute([$methodId]);
    
    echo json_encode(['success' => true, 'message' => 'Default payment method updated']);
    
} catch (PDOException $e) {
    error_log("Error setting default payment method: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update default payment method']);
}
?>
