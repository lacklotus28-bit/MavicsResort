<?php
// api/availability.php - Check venue availability for calendar display

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get request parameters
    $venueId = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : null;
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    if (!$venueId) {
        echo json_encode([
            'success' => false,
            'message' => 'Venue ID is required'
        ]);
        exit;
    }
    
    // Validate venue exists and is available
    $venueStmt = $conn->prepare("SELECT id, name, status FROM venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();
    
    if (!$venue) {
        echo json_encode([
            'success' => false,
            'message' => 'Venue not found'
        ]);
        exit;
    }
    
    if ($venue['status'] === 'unavailable') {
        echo json_encode([
            'success' => false,
            'message' => 'Venue is currently unavailable'
        ]);
        exit;
    }
    
    // Determine date range
    if ($startDate && $endDate) {
        $dateRangeStart = $startDate;
        $dateRangeEnd = $endDate;
    } else {
        // Generate month range
        $dateRangeStart = sprintf('%04d-%02d-01', $year, $month);
        $dateRangeEnd = date('Y-m-t', strtotime($dateRangeStart));
    }
    
    // Get booked dates from bookings table
    $bookingStmt = $conn->prepare("
        SELECT 
            booking_date,
            status,
            event_type,
            start_time,
            end_time,
            guest_count
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date BETWEEN ? AND ?
        AND status IN ('pending', 'confirmed')
        ORDER BY booking_date ASC
    ");
    $bookingStmt->execute([$venueId, $dateRangeStart, $dateRangeEnd]);
    $bookings = $bookingStmt->fetchAll();
    
    // Get blocked dates from venue_blocks table
    $blockStmt = $conn->prepare("
        SELECT 
            block_date,
            reason
        FROM venue_blocks 
        WHERE venue_id = ? 
        AND block_date BETWEEN ? AND ?
        ORDER BY block_date ASC
    ");
    $blockStmt->execute([$venueId, $dateRangeStart, $dateRangeEnd]);
    $blocks = $blockStmt->fetchAll();
    
    // Process availability data
    $availability = [];
    
    // Mark booked dates
    foreach ($bookings as $booking) {
        $date = $booking['booking_date'];
        $availability[$date] = [
            'status' => 'booked',
            'type' => 'booking',
            'details' => [
                'booking_status' => $booking['status'],
                'event_type' => $booking['event_type'],
                'start_time' => $booking['start_time'],
                'end_time' => $booking['end_time'],
                'guest_count' => intval($booking['guest_count'])
            ]
        ];
    }
    
    // Mark blocked dates
    foreach ($blocks as $block) {
        $date = $block['block_date'];
        $availability[$date] = [
            'status' => 'blocked',
            'type' => 'maintenance',
            'details' => [
                'reason' => $block['reason']
            ]
        ];
    }
    
    // Generate calendar grid for the requested period
    $calendar = [];
    $currentDate = new DateTime($dateRangeStart);
    $endDateTime = new DateTime($dateRangeEnd);
    
    while ($currentDate <= $endDateTime) {
        $dateString = $currentDate->format('Y-m-d');
        $dayOfWeek = $currentDate->format('w'); // 0 = Sunday, 6 = Saturday
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        $isToday = $currentDate->format('Y-m-d') === date('Y-m-d');
        $isPast = $currentDate < new DateTime('today');
        
        if (isset($availability[$dateString])) {
            // Date is booked or blocked
            $calendar[$dateString] = $availability[$dateString];
        } elseif ($isPast) {
            // Past dates are not available
            $calendar[$dateString] = [
                'status' => 'past',
                'type' => 'past',
                'details' => []
            ];
        } else {
            // Date is available
            $calendar[$dateString] = [
                'status' => 'available',
                'type' => 'open',
                'details' => [
                    'is_weekend' => $isWeekend,
                    'day_of_week' => $dayOfWeek
                ]
            ];
        }
        
        // Add additional date metadata
        $calendar[$dateString]['date_info'] = [
            'day' => intval($currentDate->format('j')),
            'month' => intval($currentDate->format('n')),
            'year' => intval($currentDate->format('Y')),
            'day_of_week' => $dayOfWeek,
            'is_weekend' => $isWeekend,
            'is_today' => $isToday,
            'is_past' => $isPast
        ];
        
        $currentDate->add(new DateInterval('P1D'));
    }
    
    // Get upcoming bookings summary
    $upcomingStmt = $conn->prepare("
        SELECT 
            booking_date,
            event_type,
            guest_count,
            status
        FROM bookings 
        WHERE venue_id = ? 
        AND booking_date >= CURRENT_DATE()
        AND status IN ('pending', 'confirmed')
        ORDER BY booking_date ASC
        LIMIT 10
    ");
    $upcomingStmt->execute([$venueId]);
    $upcomingBookings = $upcomingStmt->fetchAll();
    
    // Get booking statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
            COUNT(CASE WHEN booking_date >= CURRENT_DATE() THEN 1 END) as future_bookings,
            AVG(guest_count) as avg_guest_count
        FROM bookings 
        WHERE venue_id = ?
        AND status IN ('pending', 'confirmed', 'completed')
    ");
    $statsStmt->execute([$venueId]);
    $stats = $statsStmt->fetch();
    
    // Process stats
    if ($stats) {
        $stats['total_bookings'] = intval($stats['total_bookings']);
        $stats['confirmed_bookings'] = intval($stats['confirmed_bookings']);
        $stats['future_bookings'] = intval($stats['future_bookings']);
        $stats['avg_guest_count'] = floatval($stats['avg_guest_count']);
    }
    
    echo json_encode([
        'success' => true,
        'venue' => [
            'id' => $venue['id'],
            'name' => $venue['name'],
            'status' => $venue['status']
        ],
        'date_range' => [
            'start' => $dateRangeStart,
            'end' => $dateRangeEnd,
            'month' => $month,
            'year' => $year
        ],
        'calendar' => $calendar,
        'availability_summary' => [
            'total_days' => count($calendar),
            'available_days' => count(array_filter($calendar, function($day) {
                return $day['status'] === 'available';
            })),
            'booked_days' => count(array_filter($calendar, function($day) {
                return $day['status'] === 'booked';
            })),
            'blocked_days' => count(array_filter($calendar, function($day) {
                return $day['status'] === 'blocked';
            }))
        ],
        'upcoming_bookings' => $upcomingBookings,
        'booking_stats' => $stats,
        'generated_at' => date('c')
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in availability API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in availability API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
}
?>