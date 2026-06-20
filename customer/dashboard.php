<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=dashboard.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get user's bookings
try {
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            v.name as venue_name,
            v.images as venue_images,
            c.first_name,
            c.last_name,
            c.email
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        JOIN customers c ON b.customer_id = c.id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll();

    // Get booking statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(total_amount) as total_spent
        FROM bookings 
        WHERE customer_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $bookings = [];
    $stats = ['total_bookings' => 0, 'pending_bookings' => 0, 'confirmed_bookings' => 0, 'completed_bookings' => 0, 'cancelled_bookings' => 0, 'total_spent' => 0];
}

// Get customer's message statistics
$customerEmail = $_SESSION['email'] ?? '';
$messageStats = ['total' => 0, 'replied' => 0, 'unread_replies' => 0];

try {
    $msgStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
            SUM(CASE WHEN status = 'replied' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_replies
        FROM contact_messages 
        WHERE email = ?
    ");
    $msgStmt->execute([$customerEmail]);
    $messageStats = $msgStmt->fetch(PDO::FETCH_ASSOC);
    if (!$messageStats) {
        $messageStats = ['total' => 0, 'replied' => 0, 'unread_replies' => 0];
    }
} catch (PDOException $e) {
    error_log("Error fetching message stats: " . $e->getMessage());
}

// Page configuration
$pageTitle = "Dashboard";
$pageDescription = "Manage your bookings and account settings at Mavic's Resort.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/dashboard.css'];
$pageJSFiles = ['js/dashboard.js'];

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main id="main-content">
    <div class="dashboard-container">
        <div class="container">
            <!-- New Reply Notification Banner -->
            <?php if ($messageStats['unread_replies'] > 0): ?>
            <div class="notification-banner">
                <div class="banner-content">
                    <div class="banner-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="banner-text">
                        <strong>New Reply!</strong>
                        <span>You have <?php echo $messageStats['unread_replies']; ?> new <?php echo $messageStats['unread_replies'] == 1 ? 'reply' : 'replies'; ?> from our team.</span>
                    </div>
                    <a href="my-messages.php" class="banner-button">
                        View Messages
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <!-- Welcome Section -->
            <div class="dashboard-header">
                <div class="welcome-section">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                    <p>Manage your bookings and account from your personal dashboard.</p>
                </div>
                
                <div class="quick-actions">
                    <a href="venues.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> New Booking
                    </a>
                    <a href="venues.php" class="btn btn-outline">
                        <i class="fas fa-eye"></i> Browse Venues
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_bookings']; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_bookings']; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon confirmed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['completed_bookings']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="bookings-section">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="my-bookings.php" class="btn btn-outline">View All</a>
                </div>

                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>No Bookings Yet</h3>
                        <p>You haven't made any bookings yet. Start by exploring our beautiful venues!</p>
                        <a href="venues.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Browse Venues
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bookings-grid">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-venue">
                                        <h4><?php echo htmlspecialchars($booking['venue_name']); ?></h4>
                                        <span class="booking-id">#<?php echo $booking['id']; ?></span>
                                    </div>
                                    <div class="booking-status status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo htmlspecialchars($booking['event_type']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $booking['guest_count']; ?> guests</span>
                                    </div>
                                </div>
                                
                                <div class="booking-footer">
                                    <div class="booking-amount">
                                        <strong>₱<?php echo number_format($booking['total_amount'], 2); ?></strong>
                                    </div>
                                    <div class="booking-actions">
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div class="quick-links-section">
                <h2>Quick Links</h2>
                <div class="quick-links-grid">
                    <a href="profile.php" class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <h4>My Profile</h4>
                        <p>Update your personal information and preferences</p>
                    </a>
                    
                    <a href="my-bookings.php" class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h4>All Bookings</h4>
                        <p>View and manage all your past and upcoming bookings</p>
                    </a>
                    
                    <a href="venues.php" class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h4>New Booking</h4>
                        <p>Book a new venue for your upcoming event</p>
                    </a>
                    
                    <a href="my-messages.php" class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-envelope"></i>
                            <?php if ($messageStats['unread_replies'] > 0): ?>
                                <span class="notification-badge"><?php echo $messageStats['unread_replies']; ?></span>
                            <?php endif; ?>
                        </div>
                        <h4>My Messages</h4>
                        <p>View your inquiries and admin replies</p>
                        <?php if ($messageStats['replied'] > 0): ?>
                            <span class="badge-info">
                                <i class="fas fa-reply"></i> <?php echo $messageStats['replied']; ?> replied
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="contact.php" class="quick-link-card">
                        <div class="quick-link-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4>Support</h4>
                        <p>Get help or contact our support team</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>