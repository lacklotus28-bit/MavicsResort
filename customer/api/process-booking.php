<?php
// api/process-booking.php - Process booking submissions

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Start session and check authentication
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User must be logged in to make a booking'
    ]);
    exit;
}

// Include database configuration
require_once '../config/database.php';

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $requiredFields = [
        'venue_id', 'event_type', 'event_date', 'start_time', 
        'end_time', 'guest_count', 'total_amount', 'payment_method'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate customer data
    if (!isset($data['customer']) || !is_array($data['customer'])) {
        throw new Exception('Customer information is required');
    }
    
    $customerRequiredFields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($customerRequiredFields as $field) {
        if (!isset($data['customer'][$field]) || empty($data['customer'][$field])) {
            throw new Exception("Missing customer field: $field");
        }
    }
    
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Validate venue exists and is available
    $venueStmt = $conn->prepare("
        SELECT id, name, status, capacity, price_per_hour 
        FROM venues 
        WHERE id = ? AND status IN ('available', 'maintenance')
    ");
    $venueStmt->execute([$data['venue_id']]);
    $venue = $venueStmt->fetch();
    
    if (!$venue) {
        throw new Exception('Venue not found or unavailable');
    }
    
    // Check venue capacity
    if ($data['guest_count'] > $venue['capacity']) {
        throw new Exception("Guest count ({$data['guest_count']}) exceeds venue capacity ({$venue['capacity']})");
    }
    
    // Check if venue is available on the requested date
    $availabilityStmt = $conn->prepare("
        SELECT COUNT(*) as booking_count 
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date = ? 
        AND status IN ('pending', 'confirmed')
    ");
    $availabilityStmt->execute([$data['venue_id'], $data['event_date']]);
    $availabilityResult = $availabilityStmt->fetch();
    
    if ($availabilityResult['booking_count'] > 0) {
        throw new Exception('Venue is already booked for the selected date');
    }
    
    // Check for venue blocks
    $blockStmt = $conn->prepare("
        SELECT COUNT(*) as block_count 
        FROM venue_blocks 
        WHERE venue_id = ? AND block_date = ?
    ");
    $blockStmt->execute([$data['venue_id'], $data['event_date']]);
    $blockResult = $blockStmt->fetch();
    
    if ($blockResult['block_count'] > 0) {
        throw new Exception('Venue is not available on the selected date');
    }
    
    // Validate package if provided
    $package = null;
    if (!empty($data['package_id'])) {
        $packageStmt = $conn->prepare("
            SELECT * FROM packages 
            WHERE id = ? AND venue_id = ? AND status = 'active'
        ");
        $packageStmt->execute([$data['package_id'], $data['venue_id']]);
        $package = $packageStmt->fetch();
        
        if (!$package) {
            throw new Exception('Package not found or unavailable');
        }
        
        // Validate guest count against package limits
        if ($package['min_guests'] && $data['guest_count'] < $package['min_guests']) {
            throw new Exception("Package requires minimum {$package['min_guests']} guests");
        }
        
        if ($package['max_guests'] && $data['guest_count'] > $package['max_guests']) {
            throw new Exception("Package supports maximum {$package['max_guests']} guests");
        }
    }
    
    // Validate date (must be in the future)
    $eventDate = new DateTime($data['event_date']);
    $today = new DateTime();
    
    if ($eventDate <= $today) {
        throw new Exception('Event date must be in the future');
    }
    
    // Validate time
    $startTime = new DateTime($data['start_time']);
    $endTime = new DateTime($data['end_time']);
    
    if ($startTime >= $endTime) {
        // Handle overnight events
        $endTime->add(new DateInterval('P1D'));
    }
    
    $duration = $startTime->diff($endTime);
    $hours = $duration->h + ($duration->days * 24);
    
    if ($hours < 1) {
        throw new Exception('Event must be at least 1 hour long');
    }
    
    // Update customer information
    $customerUpdateStmt = $conn->prepare("
        UPDATE customers SET 
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            address = ?,
            emergency_contact_name = ?,
            emergency_contact_phone = ?
        WHERE id = ?
    ");
    
    $customerUpdateStmt->execute([
        $data['customer']['first_name'],
        $data['customer']['last_name'],
        $data['customer']['email'],
        $data['customer']['phone'],
        $data['customer']['address'] ?? null,
        $data['customer']['emergency_contact_name'] ?? null,
        $data['customer']['emergency_contact_phone'] ?? null,
        $_SESSION['user_id']
    ]);
    
    // Calculate pricing
    $totalAmount = floatval($data['total_amount']);
    $downPayment = floatval($data['down_payment'] ?? 0);
    $balance = $totalAmount - $downPayment;
    
    // Determine payment status
    $paymentStatus = 'unpaid';
    if ($downPayment > 0) {
        if ($balance > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }
    }
    
    // Insert booking
    $bookingStmt = $conn->prepare("
        INSERT INTO bookings (
            customer_id, venue_id, package_id, booking_date, start_time, end_time,
            event_type, guest_count, total_amount, down_payment, balance,
            payment_status, status, special_requests
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    
    $bookingStmt->execute([
        $_SESSION['user_id'],
        $data['venue_id'],
        $data['package_id'] ?? null,
        $data['event_date'],
        $data['start_time'],
        $data['end_time'],
        $data['event_type'],
        $data['guest_count'],
        $totalAmount,
        $downPayment,
        $balance,
        $paymentStatus,
        $data['special_requests'] ?? null
    ]);
    
    $bookingId = $conn->lastInsertId();
    
    // Generate booking reference
    $bookingReference = 'MAV-' . date('Ymd') . '-' . str_pad($bookingId, 4, '0', STR_PAD_LEFT);
    
    // Create payment record if payment is made
    if ($downPayment > 0) {
        $paymentStmt = $conn->prepare("
            INSERT INTO payments (
                booking_id, amount, payment_method, payment_status, 
                reference_number, payment_date
            ) VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        
        $paymentReference = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);
        $paymentStmt->execute([
            $bookingId,
            $downPayment,
            $data['payment_method'],
            $paymentReference
        ]);
        
        $paymentId = $conn->lastInsertId();
    }
    
    // Log activity if admin session exists
    if (isset($_SESSION['admin_id'])) {
        $activityStmt = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, table_name, record_id, ip_address, user_agent)
            VALUES (?, 'booking_create', 'bookings', ?, ?, ?)
        ");
        $activityStmt->execute([
            $_SESSION['admin_id'],
            $bookingId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
    
    // Send confirmation email (optional - implement if email system is set up)
    try {
        sendBookingConfirmationEmail($bookingId, $data['customer']['email']);
    } catch (Exception $e) {
        // Log email error but don't fail the booking
        error_log("Failed to send booking confirmation email: " . $e->getMessage());
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare response data
    $response = [
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $bookingId,
        'booking_reference' => $bookingReference,
        'payment_status' => $paymentStatus,
        'data' => [
            'venue_name' => $venue['name'],
            'event_date' => $data['event_date'],
            'event_type' => $data['event_type'],
            'guest_count' => $data['guest_count'],
            'total_amount' => $totalAmount,
            'down_payment' => $downPayment,
            'balance' => $balance
        ]
    ];
    
    if (isset($paymentId)) {
        $response['payment_id'] = $paymentId;
        $response['payment_reference'] = $paymentReference;
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Database error in booking processing: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while processing booking',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("General error in booking processing: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmationEmail($bookingId, $customerEmail) {
    // This is a placeholder function
    // Implement actual email sending logic here using PHPMailer or similar
    
    try {
        $conn = getDBConnection();
        
        // Get booking details
        $stmt = $conn->prepare("
            SELECT b.*, v.name as venue_name, c.first_name, c.last_name
            FROM bookings b
            JOIN venues v ON b.venue_id = v.id
            JOIN customers c ON b.customer_id = c.id
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception('Booking not found');
        }
        
        // Get email template
        $templateStmt = $conn->prepare("
            SELECT * FROM email_templates 
            WHERE template_type = 'booking_confirmation' AND is_active = 1
            LIMIT 1
        ");
        $templateStmt->execute();
        $template = $templateStmt->fetch();
        
        if ($template) {
            $subject = $template['subject'];
            $body = $template['body'];
            
            // Replace template variables
            $replacements = [
                '{{customer_name}}' => $booking['first_name'] . ' ' . $booking['last_name'],
                '{{venue_name}}' => $booking['venue_name'],
                '{{event_type}}' => $booking['event_type'],
                '{{booking_date}}' => date('F j, Y', strtotime($booking['booking_date'])),
                '{{start_time}}' => date('g:i A', strtotime($booking['start_time'])),
                '{{end_time}}' => date('g:i A', strtotime($booking['end_time'])),
                '{{guest_count}}' => $booking['guest_count'],
                '{{total_amount}}' => number_format($booking['total_amount'], 2),
                '{{status}}' => ucfirst($booking['status']),
                '{{booking_id}}' => $booking['id']
            ];
            
            foreach ($replacements as $placeholder => $value) {
                $subject = str_replace($placeholder, $value, $subject);
                $body = str_replace($placeholder, $value, $body);
            }
            
            // Here you would actually send the email
            // Example with PHP mail() function (not recommended for production):
            // mail($customerEmail, $subject, $body, $headers);
            
            // For production, use PHPMailer or similar:
            /*
            require_once 'vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->setFrom('noreply@mavicsresort.com', 'Mavic\'s Resort');
            $mail->addAddress($customerEmail);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            */
            
            // Log email sending (placeholder)
            error_log("Email would be sent to {$customerEmail} with subject: {$subject}");
        }
        
    } catch (Exception $e) {
        throw new Exception('Failed to send confirmation email: ' . $e->getMessage());
    }
}

/**
 * Validate booking data structure
 */
function validateBookingData($data) {
    $errors = [];
    
    // Validate email format
    if (!filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Validate phone number (basic validation)
    $phone = preg_replace('/[^0-9]/', '', $data['customer']['phone']);
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        $errors[] = 'Invalid phone number';
    }
    
    // Validate numeric fields
    if (!is_numeric($data['guest_count']) || $data['guest_count'] < 1) {
        $errors[] = 'Invalid guest count';
    }
    
    if (!is_numeric($data['total_amount']) || $data['total_amount'] < 0) {
        $errors[] = 'Invalid total amount';
    }
    
    // Validate date format
    $date = DateTime::createFromFormat('Y-m-d', $data['event_date']);
    if (!$date || $date->format('Y-m-d') !== $data['event_date']) {
        $errors[] = 'Invalid event date format';
    }
    
    // Validate time format
    $startTime = DateTime::createFromFormat('H:i', $data['start_time']);
    $endTime = DateTime::createFromFormat('H:i', $data['end_time']);
    
    if (!$startTime || $startTime->format('H:i') !== $data['start_time']) {
        $errors[] = 'Invalid start time format';
    }
    
    if (!$endTime || $endTime->format('H:i') !== $data['end_time']) {
        $errors[] = 'Invalid end time format';
    }
    
    // Validate payment method
    $validPaymentMethods = ['cash', 'bank_transfer', 'credit_card', 'gcash', 'paymaya'];
    if (!in_array($data['payment_method'], $validPaymentMethods)) {
        $errors[] = 'Invalid payment method';
    }
    
    // Validate event type
    $validEventTypes = [
        'Wedding Reception', 'Birthday Party', 'Corporate Event', 
        'Conference', 'Social Gathering', 'Other'
    ];
    if (!in_array($data['event_type'], $validEventTypes)) {
        $errors[] = 'Invalid event type';
    }
    
    return $errors;
}

/**
 * Calculate booking pricing
 */
function calculateBookingPricing($venue, $package, $startTime, $endTime) {
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    
    // Handle overnight events
    if ($end <= $start) {
        $end->add(new DateInterval('P1D'));
    }
    
    $interval = $start->diff($end);
    $hours = $interval->h + ($interval->days * 24) + ($interval->i / 60);
    
    if ($package) {
        // Package-based pricing
        $basePrice = floatval($package['price']);
        $includedHours = intval($package['duration_hours']) ?: 8;
        
        if ($hours > $includedHours) {
            // Add extra hours at venue hourly rate
            $extraHours = $hours - $includedHours;
            $extraCost = $extraHours * floatval($venue['price_per_hour']);
            $totalPrice = $basePrice + $extraCost;
        } else {
            $totalPrice = $basePrice;
        }
    } else {
        // Hourly pricing
        $totalPrice = $hours * floatval($venue['price_per_hour']);
    }
    
    return [
        'total_amount' => $totalPrice,
        'hours' => $hours,
        'hourly_rate' => floatval($venue['price_per_hour']),
        'package_price' => $package ? floatval($package['price']) : 0
    ];
}

/**
 * Check business rules
 */
function checkBookingRules($conn, $data) {
    $errors = [];
    
    // Get booking rules
    $rulesStmt = $conn->prepare("
        SELECT rule_name, rule_value, rule_type 
        FROM booking_rules 
        WHERE is_active = 1
    ");
    $rulesStmt->execute();
    $rules = $rulesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Check advance booking days
    if (isset($rules['min_advance_booking_days'])) {
        $minDays = intval($rules['min_advance_booking_days']);
        $eventDate = new DateTime($data['event_date']);
        $minDate = new DateTime();
        $minDate->add(new DateInterval("P{$minDays}D"));
        
        if ($eventDate < $minDate) {
            $errors[] = "Booking must be made at least {$minDays} days in advance";
        }
    }
    
    if (isset($rules['max_advance_booking_days'])) {
        $maxDays = intval($rules['max_advance_booking_days']);
        $eventDate = new DateTime($data['event_date']);
        $maxDate = new DateTime();
        $maxDate->add(new DateInterval("P{$maxDays}D"));
        
        if ($eventDate > $maxDate) {
            $errors[] = "Booking cannot be made more than {$maxDays} days in advance";
        }
    }
    
    // Check same day booking
    if (isset($rules['allow_same_day_booking']) && $rules['allow_same_day_booking'] == '0') {
        $eventDate = new DateTime($data['event_date']);
        $today = new DateTime();
        
        if ($eventDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $errors[] = "Same day bookings are not allowed";
        }
    }
    
    return $errors;
}
?>