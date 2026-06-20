<?php
// Export Bookings - Mavic's Resort Admin
require_once 'config/database.php';
require_once 'classes/Auth.php';

$auth = new Auth();

// Check if admin is logged in
if (!$auth->isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit;
}

$conn = getDBConnection();

// Get filter parameters (same as manage-bookings.php)
$status_filter = $_GET['status'] ?? 'all';
$venue_filter = $_GET['venue'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($venue_filter !== 'all') {
    $where_conditions[] = "b.venue_id = ?";
    $params[] = $venue_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "b.booking_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "b.booking_date <= ?";
    $params[] = $date_to;
}

if (!empty($search)) {
    $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR b.event_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get bookings for export
$query = "SELECT 
            b.id,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            v.name as venue_name,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.event_type,
            b.guest_count,
            b.total_amount,
            b.status,
            b.special_requests,
            b.created_at,
            b.updated_at
          FROM bookings b 
          JOIN customers c ON b.customer_id = c.id 
          JOIN venues v ON b.venue_id = v.id 
          $where_clause
          ORDER BY b.booking_date ASC, b.start_time ASC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Generate filename with current date and filters
    $filename = 'mavics-resort-bookings-' . date('Y-m-d');
    if ($status_filter !== 'all') {
        $filename .= '-' . $status_filter;
    }
    if ($venue_filter !== 'all') {
        // Get venue name for filename
        $venue_stmt = $conn->prepare("SELECT name FROM venues WHERE id = ?");
        $venue_stmt->execute([$venue_filter]);
        $venue_name = $venue_stmt->fetchColumn();
        $filename .= '-' . preg_replace('/[^a-z0-9]/i', '', strtolower($venue_name));
    }
    $filename .= '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    $headers = [
        'Booking ID',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'Venue Name',
        'Booking Date',
        'Start Time',
        'End Time',
        'Event Type',
        'Guest Count',
        'Total Amount',
        'Status',
        'Special Requests',
        'Created Date',
        'Last Updated'
    ];
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($bookings as $booking) {
        $row = [
            str_pad($booking['id'], 4, '0', STR_PAD_LEFT),
            $booking['customer_name'],
            $booking['customer_email'],
            $booking['customer_phone'] ?? '',
            $booking['venue_name'],
            date('Y-m-d', strtotime($booking['booking_date'])),
            date('H:i', strtotime($booking['start_time'])),
            date('H:i', strtotime($booking['end_time'])),
            $booking['event_type'],
            $booking['guest_count'],
            number_format($booking['total_amount'], 2),
            ucfirst($booking['status']),
            $booking['special_requests'] ?? '',
            date('Y-m-d H:i:s', strtotime($booking['created_at'])),
            date('Y-m-d H:i:s', strtotime($booking['updated_at']))
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    error_log('Export bookings error: ' . $e->getMessage());
    header("Location: manage-bookings.php?error=" . urlencode("Export failed. Please try again."));
}

exit;
?>