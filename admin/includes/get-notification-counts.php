<?php
// get-notification-counts.php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Get booking count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $bookingCount = $stmt->fetch()['count'];
    
    // Get payment count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'pending'");
    $paymentCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'bookings' => (int)$bookingCount,
        'payments' => (int)$paymentCount
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'bookings' => 0,
        'payments' => 0,
        'error' => $e->getMessage()
    ]);
}
