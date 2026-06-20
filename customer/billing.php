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
$dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $conn = getDBConnection();
    
    // Build query for payments with booking details
    $sql = "
        SELECT 
            p.*,
            b.id as booking_id,
            b.booking_date,
            b.event_type,
            b.total_amount as booking_total,
            b.down_payment,
            b.balance,
            b.payment_status as booking_payment_status,
            b.status as booking_status,
            v.name as venue_name,
            v.images as venue_images
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        INNER JOIN venues v ON b.venue_id = v.id
        WHERE b.customer_id = ?
    ";
    
    $params = [$userId];
    
    // Apply status filter
    if ($statusFilter !== 'all') {
        $sql .= " AND p.payment_status = ?";
        $params[] = $statusFilter;
    }
    
    // Apply date filter
    if ($dateFilter !== 'all') {
        switch ($dateFilter) {
            case 'today':
                $sql .= " AND DATE(p.created_at) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())";
                break;
            case 'year':
                $sql .= " AND YEAR(p.created_at) = YEAR(CURDATE())";
                break;
        }
    }
    
    // Apply search filter
    if (!empty($searchQuery)) {
        $sql .= " AND (v.name LIKE ? OR b.event_type LIKE ? OR p.reference_number LIKE ? OR p.id LIKE ?)";
        $searchParam = "%{$searchQuery}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Apply sorting
    switch ($sortBy) {
        case 'oldest':
            $sql .= " ORDER BY p.created_at ASC";
            break;
        case 'amount_high':
            $sql .= " ORDER BY p.amount DESC";
            break;
        case 'amount_low':
            $sql .= " ORDER BY p.amount ASC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY p.created_at DESC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(p.amount) as total_paid,
            SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END) as completed_amount,
            SUM(CASE WHEN p.payment_status = 'pending' THEN p.amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN p.payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN p.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN p.payment_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN p.payment_status = 'refunded' THEN 1 ELSE 0 END) as refunded_count
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        WHERE b.customer_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get outstanding balance from all bookings
    $balanceStmt = $conn->prepare("
        SELECT SUM(balance) as total_balance
        FROM bookings
        WHERE customer_id = ? AND payment_status IN ('unpaid', 'partial') AND status != 'cancelled'
    ");
    $balanceStmt->execute([$userId]);
    $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $outstandingBalance = $balanceData['total_balance'] ?? 0;
    
} catch (PDOException $e) {
    error_log("Error fetching billing history: " . $e->getMessage());
    $payments = [];
    $stats = [
        'total_transactions' => 0,
        'total_paid' => 0,
        'completed_amount' => 0,
        'pending_amount' => 0,
        'completed_count' => 0,
        'pending_count' => 0,
        'failed_count' => 0,
        'refunded_count' => 0
    ];
    $outstandingBalance = 0;
}

$pageTitle = "Billing History";
$pageDescription = "View your payment history and transactions";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/billing.css'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-file-invoice-dollar"></i> Billing History</h1>
                    <p>View all your payment transactions and billing records</p>
                </div>
                <div class="header-right">
                    <a href="my-bookings.php" class="btn btn-outline">
                        <i class="fas fa-calendar-alt"></i> My Bookings
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Summary Cards -->
    <section class="summary-section">
        <div class="container">
            <div class="summary-cards">
                <div class="summary-card total">
                    <div class="card-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="card-content">
                        <h3>Total Paid</h3>
                        <p class="card-amount">₱<?php echo number_format($stats['total_paid'] ?? 0, 2); ?></p>
                        <span class="card-detail"><?php echo $stats['total_transactions'] ?? 0; ?> transactions</span>
                    </div>
                </div>

                <div class="summary-card completed">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>Completed</h3>
                        <p class="card-amount">₱<?php echo number_format($stats['completed_amount'] ?? 0, 2); ?></p>
                        <span class="card-detail"><?php echo $stats['completed_count'] ?? 0; ?> payments</span>
                    </div>
                </div>

                <div class="summary-card pending">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-content">
                        <h3>Pending</h3>
                        <p class="card-amount">₱<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></p>
                        <span class="card-detail"><?php echo $stats['pending_count'] ?? 0; ?> payments</span>
                    </div>
                </div>

                <div class="summary-card balance">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h3>Outstanding Balance</h3>
                        <p class="card-amount">₱<?php echo number_format($outstandingBalance, 2); ?></p>
                        <span class="card-detail">from all bookings</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Billing History Section -->
    <section class="billing-section">
        <div class="container">
            <!-- Filter and Search Bar -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <a href="?status=all&date=<?php echo $dateFilter; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                        All Payments
                        <span class="count"><?php echo $stats['total_transactions']; ?></span>
                    </a>
                    <a href="?status=completed&date=<?php echo $dateFilter; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                        Completed
                        <span class="count"><?php echo $stats['completed_count']; ?></span>
                    </a>
                    <a href="?status=pending&date=<?php echo $dateFilter; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        Pending
                        <span class="count"><?php echo $stats['pending_count']; ?></span>
                    </a>
                    <?php if ($stats['failed_count'] > 0): ?>
                    <a href="?status=failed&date=<?php echo $dateFilter; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'failed' ? 'active' : ''; ?>">
                        Failed
                        <span class="count"><?php echo $stats['failed_count']; ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($stats['refunded_count'] > 0): ?>
                    <a href="?status=refunded&date=<?php echo $dateFilter; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                       class="filter-tab <?php echo $statusFilter === 'refunded' ? 'active' : ''; ?>">
                        Refunded
                        <span class="count"><?php echo $stats['refunded_count']; ?></span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="search-sort-bar">
                    <form method="GET" class="search-form">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search by venue, reference, or ID..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <?php if (!empty($searchQuery)): ?>
                                <button type="button" class="clear-search" onclick="clearSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-outline">Search</button>
                    </form>

                    <div class="filter-dropdowns">
                        <select name="date" onchange="changeDateFilter(this.value)" class="filter-select">
                            <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="year" <?php echo $dateFilter === 'year' ? 'selected' : ''; ?>>This Year</option>
                        </select>

                        <select name="sort" onchange="changeSortOrder(this.value)" class="sort-select">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="amount_high" <?php echo $sortBy === 'amount_high' ? 'selected' : ''; ?>>Amount (High to Low)</option>
                            <option value="amount_low" <?php echo $sortBy === 'amount_low' ? 'selected' : ''; ?>>Amount (Low to High)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payments List -->
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>No Payment Records Found</h3>
                    <p>
                        <?php if (!empty($searchQuery)): ?>
                            No payments match your search criteria.
                        <?php elseif ($statusFilter !== 'all' || $dateFilter !== 'all'): ?>
                            No payments found for the selected filters.
                        <?php else: ?>
                            You haven't made any payments yet. Your payment history will appear here once you make a booking.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchQuery) || $statusFilter !== 'all' || $dateFilter !== 'all'): ?>
                        <a href="billing.php" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Clear Filters
                        </a>
                    <?php else: ?>
                        <a href="venues.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Make a Booking
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="payments-list">
                    <?php foreach ($payments as $payment): ?>
                        <div class="payment-item" data-payment-id="<?php echo $payment['id']; ?>">
                            <div class="payment-header">
                                <div class="payment-title">
                                    <h3>
                                        <i class="fas fa-receipt"></i>
                                        Payment #<?php echo $payment['id']; ?>
                                    </h3>
                                    <span class="payment-date">
                                        <?php echo date('F j, Y • g:i A', strtotime($payment['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="payment-status-amount">
                                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                    <span class="payment-amount">₱<?php echo number_format($payment['amount'], 2); ?></span>
                                </div>
                            </div>

                            <div class="payment-details-table-wrapper">
                                <table class="payment-info-table">
                                    <tbody>
                                        <tr>
                                            <td class="label-cell">Booking ID:</td>
                                            <td class="value-cell">
                                                <a href="booking-details.php?id=<?php echo $payment['booking_id']; ?>">
                                                    #<?php echo $payment['booking_id']; ?>
                                                </a>
                                            </td>
                                            <td class="label-cell">Venue:</td>
                                            <td class="value-cell"><?php echo htmlspecialchars($payment['venue_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label-cell">Event Type:</td>
                                            <td class="value-cell"><?php echo htmlspecialchars($payment['event_type']); ?></td>
                                            <td class="label-cell">Event Date:</td>
                                            <td class="value-cell"><?php echo date('F j, Y', strtotime($payment['booking_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="label-cell">Payment Method:</td>
                                            <td class="value-cell">
                                                <i class="fas fa-<?php 
                                                    echo $payment['payment_method'] === 'cash' ? 'money-bill-wave' : 
                                                        ($payment['payment_method'] === 'credit_card' ? 'credit-card' : 
                                                        ($payment['payment_method'] === 'bank_transfer' ? 'university' : 
                                                        ($payment['payment_method'] === 'gcash' ? 'mobile-alt' : 'wallet'))); 
                                                ?>"></i>
                                                <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                            </td>
                                            <?php if ($payment['reference_number']): ?>
                                            <td class="label-cell">Reference No:</td>
                                            <td class="value-cell reference-number"><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                            <?php else: ?>
                                            <td class="label-cell">Payment Date:</td>
                                            <td class="value-cell"><?php echo $payment['payment_date'] ? date('M j, Y • g:i A', strtotime($payment['payment_date'])) : 'Pending'; ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php if ($payment['reference_number'] && $payment['payment_date']): ?>
                                        <tr>
                                            <td class="label-cell">Payment Date:</td>
                                            <td class="value-cell" colspan="3"><?php echo date('F j, Y • g:i A', strtotime($payment['payment_date'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="payment-summary">
                                <div class="summary-row">
                                    <span>Booking Total:</span>
                                    <span>₱<?php echo number_format($payment['booking_total'], 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>This Payment:</span>
                                    <span class="highlight">₱<?php echo number_format($payment['amount'], 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Remaining Balance:</span>
                                    <span class="balance-amount">₱<?php echo number_format($payment['balance'], 2); ?></span>
                                </div>
                            </div>

                            <div class="payment-actions">
                                <a href="booking-details.php?id=<?php echo $payment['booking_id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-eye"></i> View Booking
                                </a>
                                <?php if ($payment['payment_status'] === 'completed'): ?>
                                    <button class="btn btn-outline btn-sm" onclick="printReceipt(<?php echo $payment['id']; ?>)">
                                        <i class="fas fa-print"></i> Print Receipt
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="downloadReceipt(<?php echo $payment['id']; ?>)">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                <?php endif; ?>
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

function changeDateFilter(dateValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('date', dateValue);
    window.location.href = '?' + urlParams.toString();
}

function clearSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('search');
    window.location.href = '?' + urlParams.toString();
}

function printReceipt(paymentId) {
    window.open(`api/generate-receipt.php?payment_id=${paymentId}&action=print`, '_blank');
}

function downloadReceipt(paymentId) {
    window.location.href = `api/generate-receipt.php?payment_id=${paymentId}&action=download`;
}
</script>

<?php include 'includes/footer.php'; ?>
