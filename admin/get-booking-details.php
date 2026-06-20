<?php
// Get Booking Details API - Mavic's Resort Admin
require_once 'config/database.php';
require_once 'classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if admin is logged in
if (!$auth->isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$booking_id = (int)$_GET['id'];
$conn = getDBConnection();

try {
    // Get booking details with customer and venue information
    $query = "SELECT b.*,
                     CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                     c.first_name,
                     c.last_name,
                     c.email as customer_email,
                     c.phone as customer_phone,
                     c.address as customer_address,
                     v.name as venue_name,
                     v.description as venue_description,
                     v.capacity as venue_capacity,
                     v.price_per_hour as venue_price_per_hour
              FROM bookings b
              JOIN customers c ON b.customer_id = c.id
              JOIN venues v ON b.venue_id = v.id
              WHERE b.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Get payment information if exists
    $payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1";
    $payment_stmt = $conn->prepare($payment_query);
    $payment_stmt->execute([$booking_id]);
    $payment = $payment_stmt->fetch();
    
    // Add payment info to booking data
    if ($payment) {
        $booking['payment_status'] = $payment['payment_status'];
        $booking['payment_method'] = $payment['payment_method'];
        $booking['payment_amount'] = $payment['amount'];
        $booking['payment_reference'] = $payment['reference_number'];
        $booking['payment_date'] = $payment['payment_date'];
    } else {
        $booking['payment_status'] = 'No Payment';
        $booking['payment_method'] = null;
        $booking['payment_amount'] = null;
        $booking['payment_reference'] = null;
        $booking['payment_date'] = null;
    }
    
    // Calculate additional information
    $booking_date = new DateTime($booking['booking_date']);
    $start_time = new DateTime($booking['start_time']);
    $end_time = new DateTime($booking['end_time']);
    
    // Add calculated fields
    $booking['days_until_event'] = $booking_date->diff(new DateTime())->days;
    $booking['is_past_event'] = $booking_date < new DateTime();
    
    echo json_encode([
        'success' => true,
        'data' => $booking
    ]);
    
} catch (PDOException $e) {
    error_log('Get booking details error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>