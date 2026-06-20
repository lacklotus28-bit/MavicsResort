<?php
// Admin Dashboard - Mavic's Resort
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
$page_title = 'Dashboard Overview'; // For topbar

// Handle logout
if (isset($_POST['logout'])) {
    $auth->adminLogout();
    header("Location: admin-login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            
            <!-- Period Selector -->
            <div class="period-selector">
                <button class="period-btn active" data-period="week">This Week</button>
                <button class="period-btn" data-period="month">This Month</button>
                <button class="period-btn" data-period="year">This Year</button>
            </div>
            
            <!-- Stats Cards -->
            <section class="stats-grid">
                <div class="stat-card" data-metric="revenue">
                    <div class="stat-icon total-revenue">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Revenue</h3>
                        <p class="stat-number" id="totalRevenue">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change" id="revenueGrowth">
                            <i class="fas fa-arrow-up"></i> 0%
                        </span>
                    </div>
                </div>
                
                <div class="stat-card" data-metric="bookings">
                    <div class="stat-icon total-bookings">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Bookings</h3>
                        <p class="stat-number" id="totalBookings">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change neutral" id="bookingsStatus">
                            <i class="fas fa-info-circle"></i> All time
                        </span>
                    </div>
                </div>
                
                <div class="stat-card" data-metric="pending">
                    <div class="stat-icon pending-bookings">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Pending Bookings</h3>
                        <p class="stat-number" id="pendingBookings">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change neutral" id="pendingStatus">
                            Need attention
                        </span>
                    </div>
                </div>
                
                <div class="stat-card" data-metric="customers">
                    <div class="stat-icon active-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Active Customers</h3>
                        <p class="stat-number" id="activeCustomers">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change" id="newCustomers">
                            <i class="fas fa-user-plus"></i> +0 this month
                        </span>
                    </div>
                </div>
                
                <div class="stat-card" data-metric="avgBooking">
                    <div class="stat-icon avg-booking">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Avg. Booking Value</h3>
                        <p class="stat-number" id="avgBookingValue">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change neutral">
                            <i class="fas fa-equals"></i> Per booking
                        </span>
                    </div>
                </div>
                
                <div class="stat-card" data-metric="conversion">
                    <div class="stat-icon conversion-rate">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Conversion Rate</h3>
                        <p class="stat-number" id="conversionRate">
                            <span class="loading-shimmer">Loading...</span>
                        </p>
                        <span class="stat-change" id="conversionStatus">
                            <i class="fas fa-info-circle"></i> Last 30 days
                        </span>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="row">
                    <!-- Revenue Chart -->
                    <div class="col-8">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-area"></i> Revenue Analytics</h3>
                                <div class="chart-controls">
                                    <select id="chartPeriod" class="form-select">
                                        <option value="week">Last 7 Days</option>
                                        <option value="month" selected>Last 4 Weeks</option>
                                        <option value="year">Last 12 Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bookings Trend Chart -->
                    <div class="col-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-line"></i> Booking Trends</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="bookingsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Analytics Grid -->
            <section class="analytics-grid">
                <div class="row">
                    <!-- Popular Venues -->
                    <div class="col-6">
                        <div class="card analytics-card">
                            <div class="card-header">
                                <h3><i class="fas fa-star"></i> Popular Venues</h3>
                                <a href="manage-venues.php" class="view-all-link">View All</a>
                            </div>
                            <div class="analytics-content" id="popularVenues">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event Types -->
                    <div class="col-6">
                        <div class="card analytics-card">
                            <div class="card-header">
                                <h3><i class="fas fa-calendar-alt"></i> Event Types</h3>
                            </div>
                            <div class="analytics-content">
                                <canvas id="eventTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Activity Section -->
            <section class="activity-section">
                <div class="row">
                    <!-- Recent Bookings -->
                    <div class="col-6">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h3><i class="fas fa-history"></i> Recent Bookings</h3>
                                <a href="manage-bookings.php" class="view-all-link">View All</a>
                            </div>
                            <div class="activity-list" id="recentBookings">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="col-6">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h3><i class="fas fa-calendar-day"></i> Upcoming Events (Next 7 Days)</h3>
                                <a href="manage-bookings.php?filter=upcoming" class="view-all-link">View All</a>
                            </div>
                            <div class="activity-list upcoming-events" id="upcomingEvents">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Top Customers -->
            <section class="customers-section">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Top Customers</h3>
                        <a href="manage-users.php" class="view-all-link">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table" id="topCustomersTable">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Customer</th>
                                    <th>Total Bookings</th>
                                    <th>Total Spent</th>
                                    <th>Avg. Booking</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="topCustomersBody">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <?php include 'includes/logout-modal.php'; ?>

    <script src="js/admin-main.js"></script>
    <script src="js/admin-dashboard.js"></script>
</body>
</html>
