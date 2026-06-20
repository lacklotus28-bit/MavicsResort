<?php
// Reports Page - Mavic's Resort
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
$page_title = 'Reports & Analytics';

// Handle logout
if (isset($_POST['logout'])) {
    $auth->adminLogout();
    header("Location: admin-login.php");
    exit;
}

// Initialize variables with default values to prevent undefined variable warnings
$booking_stats = [];
$booking_trends = [];
$event_popularity = [];
$venue_popularity = [];
$payment_methods = [];
$monthly_revenue = [];
$customer_stats = ['total_customers' => 0, 'active_customers' => 0, 'repeat_customers' => 0];
$venue_utilization = [];
$cancellation_stats = ['total_cancelled' => 0, 'revenue_lost' => 0, 'avg_days_before_event' => 0];
$payment_status = [];

// Initialize summary statistics
$total_bookings = 0;
$total_revenue = 0;
$total_customers = 0;
$repeat_rate = 0;
$cancellation_rate = 0;

// Get date range from request or set defaults
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
$filter_venue = $_GET['venue_id'] ?? '';
$filter_event_type = $_GET['event_type'] ?? '';

try {
    $conn = getDBConnection();
    
    // Get venues for filter dropdown
    $venues_stmt = $conn->query("SELECT id, name FROM venues WHERE status = 'available' ORDER BY name");
    $venues = $venues_stmt->fetchAll();
    
    // Get event types for filter dropdown
    $event_types_stmt = $conn->query("SELECT DISTINCT event_type FROM bookings ORDER BY event_type");
    $event_types = $event_types_stmt->fetchAll();
    
    // Build WHERE clause for filters
    $where_clause = "WHERE b.created_at >= :start_date AND b.created_at <= :end_date";
    $params = ['start_date' => $start_date . ' 00:00:00', 'end_date' => $end_date . ' 23:59:59'];
    
    if ($filter_venue) {
        $where_clause .= " AND b.venue_id = :venue_id";
        $params['venue_id'] = $filter_venue;
    }
    
    if ($filter_event_type) {
        $where_clause .= " AND b.event_type = :event_type";
        $params['event_type'] = $filter_event_type;
    }
    
    // 1. Booking Reports
    // Total bookings by status
    $booking_stats_stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM bookings b 
        $where_clause
        GROUP BY status
    ");
    $booking_stats_stmt->execute($params);
    $booking_stats = $booking_stats_stmt->fetchAll();
    
    // Booking trends (last 12 months)
    $booking_trends_stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM bookings b
        WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $booking_trends_stmt->execute();
    $booking_trends = $booking_trends_stmt->fetchAll();
    
    // Most popular event types
    $event_popularity_stmt = $conn->prepare("
        SELECT 
            event_type,
            COUNT(*) as count,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_revenue
        FROM bookings b 
        $where_clause
        GROUP BY event_type
        ORDER BY count DESC
        LIMIT 10
    ");
    $event_popularity_stmt->execute($params);
    $event_popularity = $event_popularity_stmt->fetchAll();
    
    // Most booked venues
    $venue_popularity_stmt = $conn->prepare("
        SELECT 
            v.name,
            COUNT(*) as bookings,
            SUM(b.total_amount) as revenue,
            AVG(b.total_amount) as avg_revenue
        FROM bookings b 
        JOIN venues v ON b.venue_id = v.id
        $where_clause
        GROUP BY v.id, v.name
        ORDER BY bookings DESC
    ");
    $venue_popularity_stmt->execute($params);
    $venue_popularity = $venue_popularity_stmt->fetchAll();
    
    // 2. Revenue Reports
    // Payment method analysis
    $payment_methods_stmt = $conn->prepare("
        SELECT 
            p.payment_method,
            COUNT(*) as count,
            SUM(p.amount) as total_amount
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        $where_clause
        GROUP BY p.payment_method
        ORDER BY total_amount DESC
    ");
    $payment_methods_stmt->execute($params);
    $payment_methods = $payment_methods_stmt->fetchAll();
    
    // Monthly revenue
    $monthly_revenue_stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            SUM(down_payment) as down_payments,
            COUNT(*) as bookings
        FROM bookings b
        $where_clause AND status != 'cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthly_revenue_stmt->execute($params);
    $monthly_revenue = $monthly_revenue_stmt->fetchAll();
    
    // 3. Customer Reports
    $customer_stats_stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_customers,
            COUNT(CASE WHEN c.status = 'active' THEN 1 END) as active_customers,
            COUNT(CASE WHEN customer_bookings.booking_count > 1 THEN 1 END) as repeat_customers
        FROM customers c
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as booking_count
            FROM bookings b
            $where_clause
            GROUP BY customer_id
        ) customer_bookings ON c.id = customer_bookings.customer_id
        WHERE customer_bookings.customer_id IS NOT NULL
    ");
    $customer_stats_stmt->execute($params);
    $customer_stats = $customer_stats_stmt->fetch();
    
    // 4. Venue Utilization
    $venue_utilization_stmt = $conn->prepare("
        SELECT 
            v.name,
            v.capacity,
            COUNT(b.id) as total_bookings,
            SUM(CASE WHEN b.status IN ('confirmed', 'completed') THEN 1 ELSE 0 END) as confirmed_bookings,
            AVG(b.guest_count) as avg_guests,
            SUM(b.total_amount) as total_revenue
        FROM venues v
        LEFT JOIN bookings b ON v.id = b.venue_id AND b.created_at >= :start_date AND b.created_at <= :end_date
        WHERE v.status = 'available'
        GROUP BY v.id, v.name, v.capacity
        ORDER BY total_bookings DESC
    ");
    $venue_utilization_stmt->execute($params);
    $venue_utilization = $venue_utilization_stmt->fetchAll();
    
    // 5. Cancellation Reports
    $cancellation_stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_cancelled,
            SUM(total_amount) as revenue_lost,
            AVG(DATEDIFF(booking_date, updated_at)) as avg_days_before_event
        FROM bookings b
        $where_clause AND status = 'cancelled'
    ");
    $cancellation_stats_stmt->execute($params);
    $cancellation_stats = $cancellation_stats_stmt->fetch();
    
    // 6. Payment Status Reports
    $payment_status_stmt = $conn->prepare("
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            SUM(balance) as outstanding_balance
        FROM bookings b
        $where_clause AND status != 'cancelled'
        GROUP BY payment_status
    ");
    $payment_status_stmt->execute($params);
    $payment_status = $payment_status_stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Reports error: " . $e->getMessage());
    // Variables already initialized with default values above
}

// Calculate summary statistics (this should run regardless of whether there was an exception)
$total_bookings = array_sum(array_column($booking_stats, 'count'));
$total_revenue = array_sum(array_column($booking_stats, 'revenue'));
$total_customers = $customer_stats['total_customers'] ?? 0;
$repeat_customers = $customer_stats['repeat_customers'] ?? 0;
$repeat_rate = $total_customers > 0 ? round(($repeat_customers / $total_customers) * 100, 1) : 0;
$total_cancelled = $cancellation_stats['total_cancelled'] ?? 0;
$cancellation_rate = $total_bookings > 0 ? round(($total_cancelled / $total_bookings) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Reports Content -->
        <div class="reports-content">
            <!-- Filters Section -->
            <section class="filters-section">
                <div class="filters-card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Report Filters</h3>
                    </div>
                    <form method="GET" class="filters-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                            </div>
                            <div class="filter-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                            </div>
                            <div class="filter-group">
                                <label for="venue_id">Venue</label>
                                <select id="venue_id" name="venue_id" class="form-control">
                                    <option value="">All Venues</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?php echo $venue['id']; ?>" <?php echo $filter_venue == $venue['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($venue['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="event_type">Event Type</label>
                                <select id="event_type" name="event_type" class="form-control">
                                    <option value="">All Event Types</option>
                                    <?php foreach ($event_types as $type): ?>
                                        <option value="<?php echo $type['event_type']; ?>" <?php echo $filter_event_type == $type['event_type'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['event_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div class="quick-filters">
                            <button type="button" class="btn btn-outline" onclick="setQuickFilter('today')">Today</button>
                            <button type="button" class="btn btn-outline" onclick="setQuickFilter('week')">This Week</button>
                            <button type="button" class="btn btn-outline" onclick="setQuickFilter('month')">This Month</button>
                            <button type="button" class="btn btn-outline" onclick="setQuickFilter('quarter')">This Quarter</button>
                            <button type="button" class="btn btn-outline" onclick="setQuickFilter('year')">This Year</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Summary Statistics -->
            <section class="summary-stats">
                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Revenue</h3>
                        <p class="stat-number"><?php echo formatCurrency($total_revenue); ?></p>
                        <span class="stat-period">Selected Period</span>
                    </div>
                </div>
                <div class="stat-card bookings">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Bookings</h3>
                        <p class="stat-number"><?php echo $total_bookings; ?></p>
                        <span class="stat-period">Selected Period</span>
                    </div>
                </div>
                <div class="stat-card customers">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Customers</h3>
                        <p class="stat-number"><?php echo $total_customers; ?></p>
                        <span class="stat-period"><?php echo $repeat_rate; ?>% Repeat Rate</span>
                    </div>
                </div>
                <div class="stat-card cancellation">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Cancellation Rate</h3>
                        <p class="stat-number"><?php echo $cancellation_rate; ?>%</p>
                        <span class="stat-period">Selected Period</span>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="row">
                    <!-- Revenue Trends Chart -->
                    <div class="col-8">
                        <div class="chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-line"></i> Revenue Trends (Last 12 Months)</h3>
                                <div class="chart-actions">
                                    <button class="btn btn-sm btn-outline" onclick="exportChart('revenueChart', 'revenue-trends')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Event Types Chart -->
                    <div class="col-4">
                        <div class="chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-pie"></i> Event Types</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="eventTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Venue Performance Chart -->
                    <div class="col-6">
                        <div class="chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-bar"></i> Venue Performance</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="venueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Methods Chart -->
                    <div class="col-6">
                        <div class="chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tables Section -->
            <section class="tables-section">
                <div class="row">
                    <!-- Venue Utilization Table -->
                    <div class="col-6">
                        <div class="report-table-card">
                            <div class="card-header">
                                <h3><i class="fas fa-building"></i> Venue Utilization</h3>
                                <button class="btn btn-sm btn-outline" onclick="exportTable('venueUtilization', 'venue-utilization')">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                            </div>
                            <div class="table-container">
                                <table class="table" id="venueUtilization">
                                    <thead>
                                        <tr>
                                            <th>Venue</th>
                                            <th>Bookings</th>
                                            <th>Avg Occupancy</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($venue_utilization as $venue): ?>
                                            <?php $occupancy_rate = $venue['capacity'] > 0 ? round(($venue['avg_guests'] / $venue['capacity']) * 100, 1) : 0; ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($venue['name']); ?></td>
                                                <td><?php echo $venue['total_bookings']; ?></td>
                                                <td>
                                                    <div class="occupancy-bar">
                                                        <div class="occupancy-fill" style="width: <?php echo $occupancy_rate; ?>%"></div>
                                                        <span><?php echo $occupancy_rate; ?>%</span>
                                                    </div>
                                                </td>
                                                <td><?php echo formatCurrency($venue['total_revenue']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Status Table -->
                    <div class="col-6">
                        <div class="report-table-card">
                            <div class="card-header">
                                <h3><i class="fas fa-credit-card"></i> Payment Status</h3>
                                <button class="btn btn-sm btn-outline" onclick="exportTable('paymentStatus', 'payment-status')">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                            </div>
                            <div class="table-container">
                                <table class="table" id="paymentStatus">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Total Amount</th>
                                            <th>Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_status as $status): ?>
                                            <tr>
                                                <td>
                                                    <span class="payment-status <?php echo $status['payment_status']; ?>">
                                                        <?php echo ucfirst($status['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $status['count']; ?></td>
                                                <td><?php echo formatCurrency($status['total_amount']); ?></td>
                                                <td><?php echo formatCurrency($status['outstanding_balance']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Export Actions -->
            <section class="export-section">
                <div class="export-card">
                    <div class="card-header">
                        <h3><i class="fas fa-download"></i> Export Reports</h3>
                    </div>
                    <div class="export-actions">
                        <button class="btn btn-primary" onclick="exportFullReport('pdf')">
                            <i class="fas fa-file-pdf"></i> Export Full Report (PDF)
                        </button>
                        <button class="btn btn-secondary" onclick="exportFullReport('csv')">
                            <i class="fas fa-file-csv"></i> Export Data (CSV)
                        </button>
                        <button class="btn btn-outline" onclick="printReport()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include 'includes/logout-modal.php'; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="spinner"></div>
        <p>Generating report...</p>
    </div>

    <script src="js/admin-main.js"></script>
    <script src="js/reports.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const reportData = {
            bookingTrends: <?php echo json_encode($booking_trends); ?>,
            eventPopularity: <?php echo json_encode($event_popularity); ?>,
            venuePopularity: <?php echo json_encode($venue_popularity); ?>,
            paymentMethods: <?php echo json_encode($payment_methods); ?>,
            monthlyRevenue: <?php echo json_encode($monthly_revenue); ?>,
            venueUtilization: <?php echo json_encode($venue_utilization); ?>,
            paymentStatus: <?php echo json_encode($payment_status); ?>
        };
    </script>
</body>
</html>