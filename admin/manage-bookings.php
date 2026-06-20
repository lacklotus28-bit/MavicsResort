<?php
// Manage Bookings - Mavic's Resort
// Disable error display for AJAX requests to prevent HTML in JSON responses
if (isset($_POST['action'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

require_once 'config/database.php';
require_once 'classes/auth.php';
require_once 'includes/admin-helpers.php';

$auth = new Auth();

// Check authentication
if (!$auth->isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit;
}

$admin = $auth->getCurrentAdmin();
$page_title = 'Manage Bookings';

// Handle logout
if (isset($_POST['logout'])) {
    $auth->adminLogout();
    header("Location: admin-login.php");
    exit;
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    // Clear any output buffers to prevent stray HTML/errors in JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        $conn = getDBConnection();
        
        switch ($_POST['action']) {
            case 'update_status':
                $booking_id = intval($_POST['booking_id']);
                $new_status = clean($_POST['status']);
                
                $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $booking_id]);
                
                // Log activity
                logActivity($conn, $admin['id'], 'booking_status_update', 'bookings', $booking_id);
                
                echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
                exit;
                
            case 'delete_booking':
                $booking_id = intval($_POST['booking_id']);
                
                $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);
                
                // Log activity
                logActivity($conn, $admin['id'], 'booking_delete', 'bookings', $booking_id);
                
                echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
                exit;
                
            case 'get_booking_details':
                $booking_id = intval($_POST['booking_id']);
                
                $stmt = $conn->prepare("
                    SELECT b.*, 
                           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                           c.email, c.phone, c.address,
                           v.name as venue_name, v.capacity,
                           p.name as package_name
                    FROM bookings b
                    JOIN customers c ON b.customer_id = c.id
                    JOIN venues v ON b.venue_id = v.id
                    LEFT JOIN packages p ON b.package_id = p.id
                    WHERE b.id = ?
                ");
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode($booking);
                exit;

            // ADD THE NEW CASES HERE - INSERT AFTER THE EXISTING CASES
            case 'get_venues_packages':
                // Get all venues
                $venues_stmt = $conn->query("
                    SELECT id, name, capacity, price_per_hour, status 
                    FROM venues 
                    WHERE status = 'available' 
                    ORDER BY name
                ");
                $venues = $venues_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get all packages
                $packages_stmt = $conn->query("
                    SELECT id, venue_id, name, price, description 
                    FROM packages 
                    WHERE status = 'active' 
                    ORDER BY venue_id, name
                ");
                $packages = $packages_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'venues' => $venues,
                    'packages' => $packages
                ]);
                exit;

            case 'check_availability':
                $venue_id = intval($_POST['venue_id']);
                $date = clean($_POST['date']);
                $exclude_booking = isset($_POST['exclude_booking']) ? intval($_POST['exclude_booking']) : 0;
                
                // Check for existing bookings (excluding current booking if editing)
                $availability_sql = "
                    SELECT COUNT(*) as booking_count 
                    FROM bookings 
                    WHERE venue_id = ? 
                    AND booking_date = ? 
                    AND status IN ('pending', 'confirmed')
                ";
                
                $availability_params = [$venue_id, $date];
                
                if ($exclude_booking > 0) {
                    $availability_sql .= " AND id != ?";
                    $availability_params[] = $exclude_booking;
                }
                
                $availability_stmt = $conn->prepare($availability_sql);
                $availability_stmt->execute($availability_params);
                $booking_count = $availability_stmt->fetch(PDO::FETCH_ASSOC)['booking_count'];
                
                // Check for venue blocks
                $block_stmt = $conn->prepare("
                    SELECT COUNT(*) as block_count 
                    FROM venue_blocks 
                    WHERE venue_id = ? AND block_date = ?
                ");
                $block_stmt->execute([$venue_id, $date]);
                $block_count = $block_stmt->fetch(PDO::FETCH_ASSOC)['block_count'];
                
                $available = ($booking_count == 0 && $block_count == 0);
                
                echo json_encode([
                    'success' => true,
                    'available' => $available,
                    'booking_count' => $booking_count,
                    'block_count' => $block_count
                ]);
                exit;

            case 'update_booking':
                $booking_id = intval($_POST['booking_id']);
                $venue_id = intval($_POST['venue_id']);
                $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
                $booking_date = clean($_POST['booking_date']);
                $start_time = clean($_POST['start_time']);
                $end_time = clean($_POST['end_time']);
                $event_type = clean($_POST['event_type']);
                $guest_count = intval($_POST['guest_count']);
                $down_payment = !empty($_POST['down_payment']) ? floatval($_POST['down_payment']) : null;
                $payment_status = clean($_POST['payment_status']);
                $status = clean($_POST['status']);
                $special_requests = clean($_POST['special_requests']);
                
                try {
                    $conn->beginTransaction();
                    
                    // Get current booking data for comparison and logging
                    $current_stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
                    $current_stmt->execute([$booking_id]);
                    $current_booking = $current_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$current_booking) {
                        throw new Exception('Booking not found');
                    }
                    
                    // Validate venue capacity
                    $venue_stmt = $conn->prepare("SELECT capacity FROM venues WHERE id = ?");
                    $venue_stmt->execute([$venue_id]);
                    $venue = $venue_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$venue) {
                        throw new Exception('Selected venue not found');
                    }
                    
                    if ($guest_count > $venue['capacity']) {
                        // Log warning but allow the booking (admin decision)
                        error_log("Warning: Guest count ({$guest_count}) exceeds venue capacity ({$venue['capacity']}) for booking {$booking_id}");
                    }
                    
                    // Check availability for the new date/venue (excluding current booking)
                    if ($venue_id != $current_booking['venue_id'] || $booking_date != $current_booking['booking_date']) {
                        $check_stmt = $conn->prepare("
                            SELECT COUNT(*) as conflicts 
                            FROM bookings 
                            WHERE venue_id = ? 
                            AND booking_date = ? 
                            AND status IN ('pending', 'confirmed') 
                            AND id != ?
                        ");
                        $check_stmt->execute([$venue_id, $booking_date, $booking_id]);
                        $conflicts = $check_stmt->fetch(PDO::FETCH_ASSOC)['conflicts'];
                        
                        if ($conflicts > 0) {
                            throw new Exception('Selected venue is not available for the chosen date');
                        }
                        
                        // Check for venue blocks
                        $block_stmt = $conn->prepare("
                            SELECT COUNT(*) as blocks 
                            FROM venue_blocks 
                            WHERE venue_id = ? AND block_date = ?
                        ");
                        $block_stmt->execute([$venue_id, $booking_date]);
                        $blocks = $block_stmt->fetch(PDO::FETCH_ASSOC)['blocks'];
                        
                        if ($blocks > 0) {
                            throw new Exception('Selected venue is blocked for the chosen date');
                        }
                    }
                    
                    // Calculate total amount
                    $total_amount = calculateBookingAmount($conn, $venue_id, $package_id, $start_time, $end_time);
                    
                    // Calculate balance
                    $balance = $total_amount - ($down_payment ?? 0);
                    
                    // Determine payment status if not explicitly set
                    if ($down_payment === null || $down_payment == 0) {
                        $payment_status = 'unpaid';
                    } else if ($down_payment >= $total_amount) {
                        $payment_status = 'paid';
                        $down_payment = $total_amount; // Cap at total amount
                        $balance = 0;
                    } else {
                        $payment_status = 'partial';
                    }
                    
                    // Update the booking
                    $update_stmt = $conn->prepare("
                        UPDATE bookings SET
                            venue_id = ?,
                            package_id = ?,
                            booking_date = ?,
                            start_time = ?,
                            end_time = ?,
                            event_type = ?,
                            guest_count = ?,
                            total_amount = ?,
                            down_payment = ?,
                            balance = ?,
                            payment_status = ?,
                            status = ?,
                            special_requests = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $update_result = $update_stmt->execute([
                        $venue_id,
                        $package_id,
                        $booking_date,
                        $start_time,
                        $end_time,
                        $event_type,
                        $guest_count,
                        $total_amount,
                        $down_payment,
                        $balance,
                        $payment_status,
                        $status,
                        $special_requests,
                        $booking_id
                    ]);
                    
                    if (!$update_result) {
                        throw new Exception('Failed to update booking');
                    }
                    
                    // Log the activity with old and new values
                    $new_values = [
                        'venue_id' => $venue_id,
                        'package_id' => $package_id,
                        'booking_date' => $booking_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'event_type' => $event_type,
                        'guest_count' => $guest_count,
                        'total_amount' => $total_amount,
                        'down_payment' => $down_payment,
                        'balance' => $balance,
                        'payment_status' => $payment_status,
                        'status' => $status,
                        'special_requests' => $special_requests
                    ];
                    
                    logActivity($conn, $admin['id'], 'booking_update', 'bookings', $booking_id, $current_booking, $new_values);
                    
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Booking updated successfully',
                        'booking_id' => $booking_id
                    ]);
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                exit;

            case 'get_customers':
                // Get all customers for dropdown
                $customers_stmt = $conn->query("
                    SELECT id, first_name, last_name, email, phone 
                    FROM customers 
                    ORDER BY first_name, last_name
                ");
                $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'customers' => $customers
                ]);
                exit;

            case 'check_email':
                $email = clean($_POST['email']);
                
                $email_stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE email = ?");
                $email_stmt->execute([$email]);
                $email_exists = $email_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                
                echo json_encode([
                    'success' => true,
                    'exists' => $email_exists
                ]);
                exit;

            case 'create_booking':
                $customer_type = clean($_POST['customer_type']);
                $venue_id = intval($_POST['venue_id']);
                $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
                $booking_date = clean($_POST['booking_date']);
                $start_time = clean($_POST['start_time']);
                $end_time = clean($_POST['end_time']);
                $event_type = clean($_POST['event_type']);
                $guest_count = intval($_POST['guest_count']);
                $down_payment = !empty($_POST['down_payment']) ? floatval($_POST['down_payment']) : 0;
                $payment_status = clean($_POST['payment_status']);
                $special_requests = clean($_POST['special_requests']);
                
                try {
                    $conn->beginTransaction();
                    
                    // Validate venue exists and get capacity
                    $venue_stmt = $conn->prepare("SELECT capacity, price_per_hour FROM venues WHERE id = ? AND status = 'available'");
                    $venue_stmt->execute([$venue_id]);
                    $venue = $venue_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$venue) {
                        throw new Exception('Selected venue is not available');
                    }
                    
                    // Validate guest count against venue capacity
                    if ($guest_count > $venue['capacity']) {
                        // Log warning but allow booking (admin decision)
                        error_log("Warning: Guest count ({$guest_count}) exceeds venue capacity ({$venue['capacity']}) for new booking");
                    }
                    
                    // Check venue availability
                    $availability_stmt = $conn->prepare("
                        SELECT COUNT(*) as conflicts 
                        FROM bookings 
                        WHERE venue_id = ? 
                        AND booking_date = ? 
                        AND status IN ('pending', 'confirmed')
                    ");
                    $availability_stmt->execute([$venue_id, $booking_date]);
                    $conflicts = $availability_stmt->fetch(PDO::FETCH_ASSOC)['conflicts'];
                    
                    if ($conflicts > 0) {
                        throw new Exception('Selected venue is not available for the chosen date');
                    }
                    
                    // Check for venue blocks
                    $block_stmt = $conn->prepare("
                        SELECT COUNT(*) as blocks 
                        FROM venue_blocks 
                        WHERE venue_id = ? AND block_date = ?
                    ");
                    $block_stmt->execute([$venue_id, $booking_date]);
                    $blocks = $block_stmt->fetch(PDO::FETCH_ASSOC)['blocks'];
                    
                    if ($blocks > 0) {
                        throw new Exception('Selected venue is blocked for the chosen date');
                    }
                    
                    // Handle customer creation or selection
                    $customer_id = null;
                    
                    if ($customer_type === 'existing') {
                        $customer_id = intval($_POST['customer_id']);
                        
                        // Verify customer exists
                        $customer_check = $conn->prepare("SELECT id FROM customers WHERE id = ?");
                        $customer_check->execute([$customer_id]);
                        if (!$customer_check->fetch()) {
                            throw new Exception('Selected customer not found');
                        }
                        
                    } elseif ($customer_type === 'new') {
                        $first_name = clean($_POST['first_name']);
                        $last_name = clean($_POST['last_name']);
                        $email = clean($_POST['email']);
                        $phone = clean($_POST['phone']);
                        $address = clean($_POST['address']);
                        
                        // Validate required fields
                        if (empty($first_name) || empty($last_name) || empty($email)) {
                            throw new Exception('First name, last name, and email are required for new customers');
                        }
                        
                        // Validate email format
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception('Invalid email format');
                        }
                        
                        // Check if email already exists
                        $email_check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
                        $email_check->execute([$email]);
                        if ($email_check->fetch()) {
                            throw new Exception('Email already exists. Please use existing customer or different email.');
                        }
                        
                        // Create new customer
                        $customer_stmt = $conn->prepare("
                            INSERT INTO customers (first_name, last_name, email, phone, address, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $customer_result = $customer_stmt->execute([$first_name, $last_name, $email, $phone, $address]);
                        
                        if (!$customer_result) {
                            throw new Exception('Failed to create customer');
                        }
                        
                        $customer_id = $conn->lastInsertId();
                        
                        // Log customer creation
                        logActivity($conn, $admin['id'], 'customer_create', 'customers', $customer_id);
                        
                    } else {
                        throw new Exception('Invalid customer type');
                    }
                    
                    // Calculate total amount
                    $total_amount = calculateBookingAmount($conn, $venue_id, $package_id, $start_time, $end_time);
                    
                    // Validate down payment
                    if ($down_payment > $total_amount) {
                        $down_payment = $total_amount;
                    }
                    
                    // Calculate balance
                    $balance = $total_amount - $down_payment;
                    
                    // Determine payment status
                    if ($down_payment == 0) {
                        $payment_status = 'unpaid';
                    } elseif ($down_payment >= $total_amount) {
                        $payment_status = 'paid';
                        $balance = 0;
                    } else {
                        $payment_status = 'partial';
                    }
                    
                    // Create the booking
                    $booking_stmt = $conn->prepare("
                        INSERT INTO bookings (
                            customer_id, venue_id, package_id, booking_date, start_time, end_time,
                            event_type, guest_count, total_amount, down_payment, balance,
                            payment_status, status, special_requests, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW()
                        )
                    ");
                    
                    $booking_result = $booking_stmt->execute([
                        $customer_id,
                        $venue_id,
                        $package_id,
                        $booking_date,
                        $start_time,
                        $end_time,
                        $event_type,
                        $guest_count,
                        $total_amount,
                        $down_payment,
                        $balance,
                        $payment_status,
                        $special_requests
                    ]);
                    
                    if (!$booking_result) {
                        throw new Exception('Failed to create booking');
                    }
                    
                    $booking_id = $conn->lastInsertId();
                    
                    // Log booking creation
                    $booking_data = [
                        'customer_id' => $customer_id,
                        'venue_id' => $venue_id,
                        'package_id' => $package_id,
                        'booking_date' => $booking_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'event_type' => $event_type,
                        'guest_count' => $guest_count,
                        'total_amount' => $total_amount,
                        'down_payment' => $down_payment,
                        'balance' => $balance,
                        'payment_status' => $payment_status,
                        'special_requests' => $special_requests
                    ];
                    
                    logActivity($conn, $admin['id'], 'booking_create', 'bookings', $booking_id, null, $booking_data);
                    
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Booking created successfully',
                        'booking_id' => $booking_id,
                        'customer_id' => $customer_id
                    ]);
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                exit;                
        }
        
    } catch (Exception $e) {
        $response = json_encode(['success' => false, 'message' => $e->getMessage()]);
        ob_end_clean();
        echo $response;
        exit;
    }
    
    // If we reach here, clean buffer and exit
    ob_end_clean();
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? clean($_GET['date']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $conn = getDBConnection();
    
    // Debug: Test basic connection
    $test_query = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $total_bookings_test = $test_query->fetch(PDO::FETCH_ASSOC);
    
    // Debug output (remove this after fixing)
    error_log("Total bookings in DB: " . $total_bookings_test['count']);
    
    // Build WHERE clause with debugging
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "b.status = ?";
        $params[] = $status_filter;
        error_log("Added status filter: " . $status_filter);
    }
    
    if ($date_filter) {
        $where_conditions[] = "DATE(b.booking_date) = ?";
        $params[] = $date_filter;
        error_log("Added date filter: " . $date_filter);
    }
    
    if ($search) {
        $where_conditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR b.event_type LIKE ? OR v.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        error_log("Added search filter: " . $search);
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Debug: Log the final query
    error_log("WHERE clause: " . $where_clause);
    error_log("Parameters: " . print_r($params, true));
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN venues v ON b.venue_id = v.id
        $where_clause
    ";
    
    error_log("Count SQL: " . $count_sql);
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_bookings = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    error_log("Filtered bookings count: " . $total_bookings);
    
    $total_pages = ceil($total_bookings / $per_page);
    
    // Get bookings with explicit FETCH mode
    $sql = "
        SELECT b.*, 
               CONCAT(c.first_name, ' ', c.last_name) as customer_name,
               c.email, c.phone,
               v.name as venue_name,
               p.name as package_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN venues v ON b.venue_id = v.id
        LEFT JOIN packages p ON b.package_id = p.id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    error_log("Main SQL: " . $sql);
    error_log("LIMIT: $per_page OFFSET: $offset");
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Retrieved bookings count: " . count($bookings));
    
    // If you're still getting empty results, try this simple query:
    if (empty($bookings) && empty($where_conditions)) {
        error_log("Trying simple query...");
        $simple_sql = "SELECT b.id, CONCAT(c.first_name, ' ', c.last_name) as customer_name, b.event_type, v.name as venue_name FROM bookings b JOIN customers c ON b.customer_id = c.id JOIN venues v ON b.venue_id = v.id LIMIT 10";
        $simple_stmt = $conn->query($simple_sql);
        $simple_results = $simple_stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Simple query results: " . print_r($simple_results, true));
    }
    
    // Get summary stats with the same WHERE clause
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN venues v ON b.venue_id = v.id
        $where_clause
    ";
    
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get venues for filter dropdown
    $venues_stmt = $conn->query("SELECT id, name FROM venues ORDER BY name");
    $venues = $venues_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Manage bookings error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    $bookings = [];
    $total_bookings = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0];
    $venues = [];
}


// function logActivity($conn, $admin_id, $action, $table_name, $record_id, $old_values = null, $new_values = null) {
//     try {
//         $stmt = $conn->prepare("
//             INSERT INTO activity_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
//             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
//         ");
//         $stmt->execute([
//             $admin_id,
//             $action,
//             $table_name,
//             $record_id,
//             $old_values ? json_encode($old_values) : null,
//             $new_values ? json_encode($new_values) : null,
//             $_SERVER['REMOTE_ADDR'] ?? null,
//             $_SERVER['HTTP_USER_AGENT'] ?? null
//         ]);
//     } catch (Exception $e) {
//         error_log("Activity log error: " . $e->getMessage());
//     }
// }

/**
 * Calculate booking amount based on venue, package, and duration
 * ADD THIS FUNCTION AT THE END OF THE PHP SECTION, BEFORE THE HTML
 */
function calculateBookingAmount($conn, $venue_id, $package_id, $start_time, $end_time) {
    $total = 0;
    
    // Get venue hourly rate
    $venue_stmt = $conn->prepare("SELECT price_per_hour FROM venues WHERE id = ?");
    $venue_stmt->execute([$venue_id]);
    $venue = $venue_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($venue) {
        // Calculate hours
        $start = new DateTime("1970-01-01 $start_time");
        $end = new DateTime("1970-01-01 $end_time");
        
        // Handle next day scenario
        if ($end < $start) {
            $end->add(new DateInterval('P1D'));
        }
        
        $interval = $start->diff($end);
        $hours = $interval->h + ($interval->days * 24);
        
        // Round up partial hours
        if ($interval->i > 0) {
            $hours++;
        }
        
        $total += $venue['price_per_hour'] * $hours;
    }
    
    // Add package price if selected
    if ($package_id) {
        $package_stmt = $conn->prepare("SELECT price FROM packages WHERE id = ?");
        $package_stmt->execute([$package_id]);
        $package = $package_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($package) {
            $total += $package['price'];
        }
    }
    
    return $total;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/manage-bookings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="manage-bookings-page">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Bookings Content -->
        <div class="bookings-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-left">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt"></i>
                        Manage Bookings
                    </h1>
                    <p class="page-subtitle">View and manage all venue bookings</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openAddBookingModal()">
                        <i class="fas fa-plus"></i>
                        Add New Booking
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Bookings</h3>
                        <span class="stat-number"><?php echo $stats['total']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <span class="stat-number"><?php echo $stats['pending']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon confirmed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Confirmed</h3>
                        <span class="stat-number"><?php echo $stats['confirmed']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed</h3>
                        <span class="stat-number"><?php echo $stats['completed']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="filters-section">
                <div class="filters-card">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <div class="search-input">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search" name="search" placeholder="Search by customer, event type, or venue..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                Filter
                            </button>
                            <a href="manage-bookings.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="table-section">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Bookings List</h3>
                        <div class="table-actions">
                            <button class="btn btn-outline btn-sm" onclick="exportBookings()">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Event Type</th>
                                    <th>Venue</th>
                                    <th>Date & Time</th>
                                    <th>Guests</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="9" class="no-data">
                                            <div class="no-data-content">
                                                <i class="fas fa-calendar-times"></i>
                                                <p>No bookings found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td class="booking-id">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?php echo getInitials($booking['customer_name']); ?>
                                                    </div>
                                                    <div class="customer-details">
                                                        <span class="customer-name"><?php echo clean($booking['customer_name']); ?></span>
                                                        <span class="customer-email"><?php echo clean($booking['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="event-type"><?php echo clean($booking['event_type']); ?></td>
                                            <td class="venue-name"><?php echo clean($booking['venue_name']); ?></td>
                                            <td>
                                                <div class="datetime-info">
                                                    <span class="date"><?php echo formatDate($booking['booking_date']); ?></span>
                                                    <span class="time"><?php echo formatTime($booking['start_time']); ?> - <?php echo formatTime($booking['end_time']); ?></span>
                                                </div>
                                            </td>
                                            <td class="guest-count"><?php echo $booking['guest_count']; ?></td>
                                            <td class="amount"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                            <td>
                                                <select class="status-select" data-booking-id="<?php echo $booking['id']; ?>" onchange="updateStatus(this)">
                                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action btn-view" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn-action btn-edit" onclick="editBooking(<?php echo $booking['id']; ?>)" title="Edit Booking">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-action btn-delete" onclick="deleteBooking(<?php echo $booking['id']; ?>)" title="Delete Booking">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?>-<?php echo min($page * $per_page, $total_bookings); ?> of <?php echo $total_bookings; ?> bookings
                            </div>
                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo $status_filter ? "&status=$status_filter" : ''; ?><?php echo $date_filter ? "&date=$date_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" class="pagination-btn">First</a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?><?php echo $date_filter ? "&date=$date_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" class="pagination-btn">Prev</a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?><?php echo $date_filter ? "&date=$date_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" 
                                       class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?><?php echo $date_filter ? "&date=$date_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" class="pagination-btn">Next</a>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?><?php echo $date_filter ? "&date=$date_filter" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" class="pagination-btn">Last</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Booking Details</h2>
                <button class="modal-close" onclick="closeModal('bookingModal')">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Action</h2>
                <button class="modal-close" onclick="closeModal('confirmModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to perform this action?</p>
                <div class="modal-actions">
                    <button class="btn btn-danger" id="confirmBtn">Confirm</button>
                    <button class="btn btn-outline" onclick="closeModal('confirmModal')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>

    <script src="js/admin-main.js"></script>
    <script src="js/manage-bookings.js"></script>
</body>
</html>