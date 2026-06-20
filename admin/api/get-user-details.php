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
    
    if (!isset($_GET['id'])) {
        throw new Exception('User ID is required');
    }
    
    $userId = $_GET['id'];
    
    // Get user details
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get booking history
    $bookingStmt = $conn->prepare("
        SELECT b.*, v.name as venue_name
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        WHERE b.customer_id = :customer_id
        ORDER BY b.booking_date DESC
        LIMIT 20
    ");
    $bookingStmt->bindParam(':customer_id', $userId);
    $bookingStmt->execute();
    $user['bookings'] = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get login history
    $loginStmt = $conn->prepare("
        SELECT *
        FROM customer_login_history
        WHERE customer_id = :customer_id
        ORDER BY login_time DESC
        LIMIT 10
    ");
    $loginStmt->bindParam(':customer_id', $userId);
    $loginStmt->execute();
    $user['login_history'] = $loginStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
