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
    $conn = getDBConnection();
    
    // Get form data
    $paymentType = $_POST['payment_type'] ?? '';
    $provider = $_POST['provider'] ?? null;
    $lastFourDigits = $_POST['last_four_digits'] ?? null;
    $cardholderName = $_POST['cardholder_name'] ?? null;
    $expiryMonth = $_POST['expiry_month'] ?? null;
    $expiryYear = $_POST['expiry_year'] ?? null;
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate required fields
    if (empty($paymentType)) {
        echo json_encode(['success' => false, 'message' => 'Payment type is required']);
        exit();
    }
    
    // If setting as default, unset other defaults first
    if ($isDefault) {
        $stmt = $conn->prepare("UPDATE customer_payment_methods SET is_default = 0 WHERE customer_id = ?");
        $stmt->execute([$userId]);
    }
    
    // Insert new payment method
    $stmt = $conn->prepare("
        INSERT INTO customer_payment_methods 
        (customer_id, payment_type, provider, last_four_digits, cardholder_name, expiry_month, expiry_year, is_default, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $userId,
        $paymentType,
        $provider,
        $lastFourDigits,
        $cardholderName,
        $expiryMonth,
        $expiryYear,
        $isDefault
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment method added successfully',
        'method_id' => $conn->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Error adding payment method: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add payment method']);
}
?>
