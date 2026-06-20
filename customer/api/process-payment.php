<?php
/**
 * Process Payment API
 * Handles payment submission and proof upload
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

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Get form data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$reference_number = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$user_id = $_SESSION['user_id'];

// Validate required fields
if (!$booking_id || !$amount || !$payment_method || !$reference_number) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

// Validate amount
if ($amount < 500) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Minimum payment amount is ₱500'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify booking belongs to user
    $stmt = $conn->prepare("
        SELECT id, balance, payment_status, status 
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
    
    // Check if booking can accept payments
    if ($booking['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot make payment for a cancelled booking'
        ]);
        exit();
    }
    
    if ($booking['payment_status'] === 'paid') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'This booking is already fully paid'
        ]);
        exit();
    }
    
    // Validate amount doesn't exceed balance
    if ($amount > $booking['balance']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Payment amount exceeds remaining balance'
        ]);
        exit();
    }
    
    // Handle file upload (if not cash payment)
    $proof_filename = null;
    if ($payment_method !== 'cash' && isset($_FILES['payment_proof'])) {
        $file = $_FILES['payment_proof'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Only JPG and PNG are allowed'
            ]);
            exit();
        }
        
        // Validate file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'File size must be less than 5MB'
            ]);
            exit();
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $proof_filename = 'payment_' . $booking_id . '_' . time() . '.' . $extension;
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Move uploaded file
        $upload_path = $upload_dir . $proof_filename;
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to save uploaded file');
        }
    }
    
    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (
            booking_id, 
            amount, 
            payment_method, 
            reference_number, 
            payment_proof, 
            notes, 
            payment_status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $booking_id,
        $amount,
        $payment_method,
        $reference_number,
        $proof_filename,
        $notes
    ]);
    
    $payment_id = $conn->lastInsertId();
    
    // Update booking payment status if applicable
    $new_balance = $booking['balance'] - $amount;
    $new_payment_status = $new_balance <= 0 ? 'paid' : 'partial';
    
    // Don't update balance yet, wait for verification
    // But we can update payment_status to show payment is pending
    if ($booking['payment_status'] === 'pending') {
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'partial',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$booking_id]);
    }
    
    // Log the payment submission
    error_log("Payment submitted - Booking: #{$booking_id}, Amount: ₱{$amount}, Method: {$payment_method}, Reference: {$reference_number}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully',
        'payment_id' => $payment_id,
        'data' => [
            'booking_id' => $booking_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'reference_number' => $reference_number,
            'status' => 'pending'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in process-payment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in process-payment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>