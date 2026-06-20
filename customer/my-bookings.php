<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];

// Get filter and sort parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $conn = getDBConnection();
    
    // Build query based on filters
    $sql = "
        SELECT 
            b.*,
            v.name as venue_name,
            v.images as venue_images,
            p.name as package_name
        FROM bookings b
        INNER JOIN venues v ON b.venue_id = v.id
        LEFT JOIN packages p ON b.package_id = p.id
        WHERE b.customer_id = ?
    ";
    
    $params = [$userId];
    
    // Apply status filter
    if ($statusFilter !== 'all') {
        $sql .= " AND b.status = ?";
        $params[] = $statusFilter;
    }
    
    // Apply search filter
    if (!empty($searchQuery)) {
        $sql .= " AND (v.name LIKE ? OR b.event_type LIKE ? OR b.id LIKE ?)";
        $searchParam = "%{$searchQuery}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Apply sorting
    switch ($sortBy) {
        case 'oldest':
            $sql .= " ORDER BY b.booking_date ASC, b.created_at ASC";
            break;
        case 'date_asc':
            $sql .= " ORDER BY b.booking_date ASC";
            break;
        case 'date_desc':
            $sql .= " ORDER BY b.booking_date DESC";
            break;
        case 'amount_high':
            $sql .= " ORDER BY b.total_amount DESC";
            break;
        case 'amount_low':
            $sql .= " ORDER BY b.total_amount ASC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY b.created_at DESC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics for filter counts
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings 
        WHERE customer_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching bookings: " . $e->getMessage());
    $bookings = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
}

$pageTitle = "My Bookings";
$pageDescription = "View and manage all your bookings";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/my-bookings.css'];
$pageJSFiles = ['js/my-bookings.js'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-calendar-alt"></i> My Bookings</h1>
                    <p>View and manage all your past and upcoming bookings</p>
                </div>
                <div class="header-right">
                    <a href="venues.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Bookings Section -->
    <section class="bookings-section">
        <div class="container">
            <!-- Filter and Search Bar -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <a href="?status=all&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                        All Bookings
                        <span class="count"><?php echo $stats['total']; ?></span>
                    </a>
                    <a href="?status=pending&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        Pending
                        <span class="count"><?php echo $stats['pending']; ?></span>
                    </a>
                    <a href="?status=confirmed&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">
                        Confirmed
                        <span class="count"><?php echo $stats['confirmed']; ?></span>
                    </a>
                    <a href="?status=completed&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                        Completed
                        <span class="count"><?php echo $stats['completed']; ?></span>
                    </a>
                    <a href="?status=cancelled&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                        Cancelled
                        <span class="count"><?php echo $stats['cancelled']; ?></span>
                    </a>
                </div>

                <div class="search-sort-bar">
                    <form method="GET" class="search-form">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search by venue, event type, or ID..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <?php if (!empty($searchQuery)): ?>
                                <button type="button" class="clear-search" onclick="clearSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-outline">Search</button>
                    </form>

                    <div class="sort-dropdown">
                        <select name="sort" onchange="changeSortOrder(this.value)" class="sort-select">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="date_asc" <?php echo $sortBy === 'date_asc' ? 'selected' : ''; ?>>Event Date (Earliest)</option>
                            <option value="date_desc" <?php echo $sortBy === 'date_desc' ? 'selected' : ''; ?>>Event Date (Latest)</option>
                            <option value="amount_high" <?php echo $sortBy === 'amount_high' ? 'selected' : ''; ?>>Amount (High to Low)</option>
                            <option value="amount_low" <?php echo $sortBy === 'amount_low' ? 'selected' : ''; ?>>Amount (Low to High)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>No Bookings Found</h3>
                    <p>
                        <?php if (!empty($searchQuery)): ?>
                            No bookings match your search criteria.
                        <?php elseif ($statusFilter !== 'all'): ?>
                            You don't have any <?php echo $statusFilter; ?> bookings.
                        <?php else: ?>
                            You haven't made any bookings yet. Start by exploring our beautiful venues!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchQuery) || $statusFilter !== 'all'): ?>
                        <a href="my-bookings.php" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Clear Filters
                        </a>
                    <?php else: ?>
                        <a href="venues.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Make a Booking
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bookings-list">
                    <?php foreach ($bookings as $booking): 
                        $venueImages = json_decode($booking['venue_images'], true);
                        $firstImage = !empty($venueImages) ? $venueImages[0] : 'default-venue.jpg';
                    ?>
                        <div class="booking-item" data-booking-id="<?php echo $booking['id']; ?>">
                            <div class="booking-image">
                                <img src="../admin/images/venues/<?php echo htmlspecialchars($firstImage); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['venue_name']); ?>">
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>

                            <div class="booking-info">
                                <div class="booking-header-info">
                                    <div>
                                        <h3><?php echo htmlspecialchars($booking['venue_name']); ?></h3>
                                        <p class="booking-id">Booking #<?php echo $booking['id']; ?></p>
                                    </div>
                                    <div class="booking-amount">
                                        <span class="amount-label">Total Amount</span>
                                        <span class="amount-value">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                </div>

                                <div class="booking-details-grid">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <span class="detail-label">Event Date</span>
                                            <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <span class="detail-label">Time</span>
                                            <span class="detail-value">
                                                <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <div>
                                            <span class="detail-label">Event Type</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($booking['event_type']); ?></span>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <div>
                                            <span class="detail-label">Guests</span>
                                            <span class="detail-value"><?php echo $booking['guest_count']; ?> people</span>
                                        </div>
                                    </div>

                                    <?php if ($booking['package_name']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-box"></i>
                                        <div>
                                            <span class="detail-label">Package</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($booking['package_name']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="detail-item">
                                        <i class="fas fa-credit-card"></i>
                                        <div>
                                            <span class="detail-label">Payment</span>
                                            <span class="detail-value payment-<?php echo $booking['payment_status']; ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if ($booking['payment_status'] !== 'paid' && $booking['status'] !== 'cancelled'): ?>
                                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-credit-card"></i> Make Payment
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function changeSortOrder(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortValue);
    window.location.href = '?' + urlParams.toString();
}

function clearSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('search');
    window.location.href = '?' + urlParams.toString();
}
</script>

<?php include 'includes/footer.php'; ?>