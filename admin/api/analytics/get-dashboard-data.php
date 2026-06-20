<?php
// Dashboard Analytics API
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $conn = getDBConnection();
    $response = ['success' => true];
    
    // Get date range from request (default to last 30 days)
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    
    // ===== KEY METRICS =====
    
    // Total bookings
    $stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $response['metrics']['total_bookings'] = $stmt->fetch()['total'];
    
    // Bookings by status
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM bookings
        GROUP BY status
    ");
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($status_data as $row) {
        $response['metrics']['bookings_by_status'][$row['status']] = $row['count'];
    }
    
    // Total revenue
    $stmt = $conn->query("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM bookings
        WHERE status != 'cancelled'
    ");
    $response['metrics']['total_revenue'] = $stmt->fetch()['total'];
    
    // Monthly revenue
    $stmt = $conn->query("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM bookings
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND status != 'cancelled'
    ");
    $response['metrics']['monthly_revenue'] = $stmt->fetch()['revenue'];
    
    // Today's revenue
    $stmt = $conn->query("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM bookings
        WHERE DATE(created_at) = CURRENT_DATE()
        AND status != 'cancelled'
    ");
    $response['metrics']['today_revenue'] = $stmt->fetch()['revenue'];
    
    // Revenue growth (compared to last month)
    $stmt = $conn->query("
        SELECT 
            COALESCE(SUM(CASE 
                WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
                THEN total_amount ELSE 0 END), 0) as current_month,
            COALESCE(SUM(CASE 
                WHEN MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
                THEN total_amount ELSE 0 END), 0) as last_month
        FROM bookings
        WHERE status != 'cancelled'
    ");
    $revenue_comparison = $stmt->fetch();
    $current = floatval($revenue_comparison['current_month']);
    $last = floatval($revenue_comparison['last_month']);
    $response['metrics']['revenue_growth'] = $last > 0 ? round((($current - $last) / $last) * 100, 1) : 0;
    
    // Total customers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers");
    $response['metrics']['total_customers'] = $stmt->fetch()['total'];
    
    // Active customers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $response['metrics']['active_customers'] = $stmt->fetch()['total'];
    
    // New customers this month
    $stmt = $conn->query("
        SELECT COUNT(*) as total
        FROM customers
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $response['metrics']['new_customers_month'] = $stmt->fetch()['total'];
    
    // Average booking value
    $stmt = $conn->query("
        SELECT COALESCE(AVG(total_amount), 0) as average
        FROM bookings
        WHERE status != 'cancelled'
    ");
    $response['metrics']['avg_booking_value'] = $stmt->fetch()['average'];
    
    // ===== REVENUE CHART DATA =====
    
    if ($period === 'week') {
        // Last 7 days
        $stmt = $conn->query("
            SELECT 
                DATE(created_at) as date,
                DAYNAME(created_at) as label,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            AND status != 'cancelled'
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY DATE(created_at)
        ");
    } elseif ($period === 'month') {
        // Last 4 weeks
        $stmt = $conn->query("
            SELECT 
                CONCAT('Week ', WEEK(created_at, 1) - WEEK(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), 1) + 1) as label,
                WEEK(created_at, 1) as week_num,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)
            AND status != 'cancelled'
            GROUP BY WEEK(created_at, 1)
            ORDER BY WEEK(created_at, 1)
        ");
    } else {
        // Last 12 months
        $stmt = $conn->query("
            SELECT 
                DATE_FORMAT(created_at, '%b') as label,
                MONTH(created_at) as month_num,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
            AND status != 'cancelled'
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)
        ");
    }
    
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['charts']['revenue'] = [
        'labels' => array_column($chart_data, 'label'),
        'values' => array_map('floatval', array_column($chart_data, 'revenue'))
    ];
    
    // ===== BOOKINGS CHART DATA =====
    
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%b') as label,
            COUNT(*) as count
        FROM bookings
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ");
    
    $bookings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['charts']['bookings'] = [
        'labels' => array_column($bookings_data, 'label'),
        'values' => array_map('intval', array_column($bookings_data, 'count'))
    ];
    
    // ===== POPULAR VENUES =====
    
    $stmt = $conn->query("
        SELECT 
            v.name,
            v.id,
            COUNT(b.id) as booking_count,
            COALESCE(SUM(b.total_amount), 0) as total_revenue
        FROM venues v
        LEFT JOIN bookings b ON v.id = b.venue_id AND b.status != 'cancelled'
        GROUP BY v.id, v.name
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $response['popular_venues'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== EVENT TYPES DISTRIBUTION =====
    
    $stmt = $conn->query("
        SELECT 
            event_type,
            COUNT(*) as count,
            COALESCE(SUM(total_amount), 0) as revenue
        FROM bookings
        WHERE status != 'cancelled'
        GROUP BY event_type
        ORDER BY count DESC
    ");
    $response['event_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== PAYMENT STATUS =====
    
    $stmt = $conn->query("
        SELECT 
            payment_status,
            COUNT(*) as count,
            COALESCE(SUM(total_amount), 0) as amount
        FROM bookings
        WHERE status != 'cancelled'
        GROUP BY payment_status
    ");
    $response['payment_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== UPCOMING EVENTS (Next 7 days) =====
    
    $stmt = $conn->query("
        SELECT 
            b.id,
            b.event_type,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.guest_count,
            b.status,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            v.name as venue_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN venues v ON b.venue_id = v.id
        WHERE b.booking_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
        AND b.status IN ('confirmed', 'pending')
        ORDER BY b.booking_date ASC, b.start_time ASC
        LIMIT 10
    ");
    $response['upcoming_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== RECENT ACTIVITIES =====
    
    $stmt = $conn->query("
        SELECT 
            b.id,
            b.event_type,
            b.booking_date,
            b.status,
            b.total_amount,
            b.created_at,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $response['recent_bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== CUSTOMER STATISTICS =====
    
    $stmt = $conn->query("
        SELECT 
            c.id,
            CONCAT(c.first_name, ' ', c.last_name) as name,
            COUNT(b.id) as booking_count,
            COALESCE(SUM(b.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN bookings b ON c.id = b.customer_id AND b.status != 'cancelled'
        GROUP BY c.id
        HAVING booking_count > 0
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $response['top_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== CONVERSION METRICS =====
    
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    ");
    $conversion_data = $stmt->fetch();
    $total = intval($conversion_data['total']);
    $confirmed = intval($conversion_data['confirmed']);
    $response['metrics']['conversion_rate'] = $total > 0 ? round(($confirmed / $total) * 100, 1) : 0;
    $response['metrics']['cancellation_rate'] = $total > 0 ? round((intval($conversion_data['cancelled']) / $total) * 100, 1) : 0;
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
