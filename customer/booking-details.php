<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$booking_id) {
    header('Location: dashboard.php');
    exit();
}

try {
    $conn = getDBConnection();
    
    // Fetch booking details with related data
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            v.name as venue_name,
            v.description as venue_description,
            v.capacity as venue_capacity,
            v.images as venue_images,
            p.name as package_name,
            p.description as package_description,
            p.inclusions as package_inclusions,
            c.first_name,
            c.last_name,
            c.email,
            c.phone
        FROM bookings b
        INNER JOIN venues v ON b.venue_id = v.id
        LEFT JOIN packages p ON b.package_id = p.id
        INNER JOIN customers c ON b.customer_id = c.id
        WHERE b.id = ? AND b.customer_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Fetch payments for this booking
    $stmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE booking_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll();
    
    // Decode JSON fields
    $venue_images = json_decode($booking['venue_images'], true);
    $package_inclusions = $booking['package_inclusions'] ? json_decode($booking['package_inclusions'], true) : [];
    
} catch (PDOException $e) {
    error_log("Error fetching booking details: " . $e->getMessage());
    die("Error loading booking details. Please try again later.");
}

$pageTitle = "Booking Details #" . $booking['id'];
$pageDescription = "View your booking details and payment information";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/booking-details.css'];
$pageJSFiles = ['js/booking-details.js'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Booking Header -->
    <section class="booking-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <h1>Booking #<?php echo $booking['id']; ?></h1>
                    <div class="booking-meta">
                        <span class="booking-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                        </span>
                        <span class="booking-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                            <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                        </span>
                    </div>
                </div>
                <div class="header-right">
                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                    <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Details Content -->
    <section class="booking-details-section">
        <div class="container">
            <div class="details-layout">
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Event Details Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-info-circle"></i> Event Details</h2>
                        </div>
                        <div class="card-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Event Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['event_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Number of Guests</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['guest_count']); ?> guests</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value">
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Duration</span>
                                    <span class="detail-value">
                                        <?php 
                                        $start = new DateTime($booking['start_time']);
                                        $end = new DateTime($booking['end_time']);
                                        $diff = $start->diff($end);
                                        echo $diff->h . ' hours';
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Booking Date</span>
                                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($booking['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($booking['special_requests']): ?>
                            <div class="special-requests">
                                <h3>Special Requests</h3>
                                <p><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Venue Details Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-building"></i> Venue Information</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($venue_images && count($venue_images) > 0): ?>
                            <div class="venue-image">
                                <img src="../admin/images/venues/<?php echo htmlspecialchars($venue_images[0]); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['venue_name']); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($booking['venue_name']); ?></h3>
                            <p class="venue-description"><?php echo htmlspecialchars($booking['venue_description']); ?></p>
                            
                            <div class="venue-info">
                                <div class="info-item">
                                    <i class="fas fa-users"></i>
                                    <span>Capacity: <?php echo htmlspecialchars($booking['venue_capacity']); ?> guests</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($booking['package_id']): ?>
                    <!-- Package Details Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-box"></i> Package Details</h2>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($booking['package_name']); ?></h3>
                            <p><?php echo htmlspecialchars($booking['package_description']); ?></p>
                            
                            <?php if (!empty($package_inclusions)): ?>
                            <div class="package-inclusions">
                                <h4>Package Inclusions</h4>
                                <ul>
                                    <?php foreach ($package_inclusions as $inclusion): ?>
                                    <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($inclusion); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Payment History Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-receipt"></i> Payment History</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($payments) > 0): ?>
                            <div class="payment-history-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                            <td>
                                                <span class="payment-status status-<?php echo $payment['payment_status']; ?>">
                                                    <?php echo ucfirst($payment['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="no-payments">No payments recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Details Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-user"></i> Contact Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Name</span>
                                    <span class="detail-value">
                                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Payment Summary Card -->
                    <div class="summary-card">
                        <h3>Payment Summary</h3>
                        <div class="summary-items">
                            <div class="summary-item">
                                <span>Total Amount</span>
                                <span class="amount">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Down Payment</span>
                                <span class="amount">₱<?php echo number_format($booking['down_payment'], 2); ?></span>
                            </div>
                            <div class="summary-item total">
                                <span>Balance</span>
                                <span class="amount">₱<?php echo number_format($booking['balance'], 2); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($booking['payment_status'] != 'paid' && $booking['status'] != 'cancelled'): ?>
                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-credit-card"></i> Make Payment
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Actions Card -->
                    <div class="actions-card">
                        <h3>Actions</h3>
                        <div class="action-buttons">
                            <button onclick="window.print()" class="btn btn-outline btn-block">
                                <i class="fas fa-print"></i> Print Details
                            </button>
                            <button onclick="downloadReceipt(<?php echo $booking['id']; ?>)" class="btn btn-outline btn-block">
                                <i class="fas fa-download"></i> Download Receipt
                            </button>
                            <?php if ($booking['status'] == 'pending'): ?>
                            <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="btn btn-danger btn-block">
                                <i class="fas fa-times-circle"></i> Cancel Booking
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="help-card">
                        <h3>Need Help?</h3>
                        <p>If you have any questions about your booking, feel free to contact us.</p>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>+63 961 306 7957</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>info@mavicsresort.com</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Pass booking data to JavaScript
window.bookingData = {
    id: <?php echo $booking['id']; ?>,
    status: '<?php echo $booking['status']; ?>',
    paymentStatus: '<?php echo $booking['payment_status']; ?>'
};
</script>

<?php include 'includes/footer.php'; ?>
