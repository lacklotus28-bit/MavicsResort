<?php
/**
 * Cancel Booking API
 * Handles booking cancellation requests
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit();
}

require_once '../config/database.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !is_numeric($data['booking_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid booking ID'
    ]);
    exit();
}

$booking_id = intval($data['booking_id']);
$user_id = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Verify booking belongs to user and can be cancelled
    $stmt = $conn->prepare("
        SELECT id, status, payment_status 
        FROM bookings 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found'
        ]);
        exit();
    }
    
    // Check if booking can be cancelled
    if ($booking['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Booking is already cancelled'
        ]);
        exit();
    }
    
    if ($booking['status'] === 'completed') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot cancel a completed booking'
        ]);
        exit();
    }
    
    // Update booking status to cancelled
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);
    
    // Log the cancellation
    error_log("Booking #{$booking_id} cancelled by user #{$user_id}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Error cancelling booking: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while cancelling the booking'
    ]);
}
?>