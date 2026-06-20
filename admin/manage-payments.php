<?php
session_start();
require_once 'config/database.php';
require_once 'includes/admin-helpers.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$conn = getDBConnection();

// Get payment settings
try {
    $stmt = $conn->prepare("SELECT * FROM payment_settings ORDER BY setting_group, display_order");
    $stmt->execute();
    $allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group settings
    $settings = [
        'payment_methods' => [],
        'bank_details' => [],
        'gcash_details' => [],
        'general' => []
    ];
    
    foreach ($allSettings as $setting) {
        $settings[$setting['setting_group']][] = $setting;
    }
    
    // Get payment statistics
    $statsStmt = $conn->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount
        FROM payments
        WHERE payment_status = 'completed'
        GROUP BY payment_method
        ORDER BY count DESC
    ");
    $statsStmt->execute();
    $paymentStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total payments
    $totalStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount
        FROM payments
        WHERE payment_status = 'completed'
    ");
    $totalStmt->execute();
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent payment methods used
    $recentStmt = $conn->prepare("
        SELECT 
            p.payment_method,
            p.amount,
            p.created_at,
            b.id as booking_id,
            b.event_type,
            c.first_name,
            c.last_name
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        INNER JOIN customers c ON b.customer_id = c.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $recentStmt->execute();
    $recentPayments = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching payment settings: " . $e->getMessage());
    $settings = ['payment_methods' => [], 'bank_details' => [], 'gcash_details' => [], 'general' => []];
    $paymentStats = [];
    $totals = ['total_count' => 0, 'total_amount' => 0];
    $recentPayments = [];
}

$pageTitle = "Payment Management";
include 'includes/topbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Mavic's Resort Admin</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">    
    <link rel="stylesheet" href="css/payment-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1><i class="fas fa-money-check-alt"></i> Payment Management</h1>
                    <p>Manage payment methods and view payment statistics</p>
                </div>
                <button class="btn btn-primary" onclick="saveAllSettings()">
                    <i class="fas fa-save"></i> Save All Changes
                </button>
            </div>

            <!-- Payment Statistics -->
            <section class="stats-section">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Payment Statistics
                </h2>

                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Payments</h3>
                            <p class="stat-value"><?php echo number_format($totals['total_count']); ?></p>
                            <span class="stat-label">Completed transactions</span>
                        </div>
                    </div>

                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Revenue</h3>
                            <p class="stat-value">₱<?php echo number_format($totals['total_amount'], 2); ?></p>
                            <span class="stat-label">From all payments</span>
                        </div>
                    </div>

                    <div class="stat-card average">
                        <div class="stat-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Average Payment</h3>
                            <p class="stat-value">₱<?php echo $totals['total_count'] > 0 ? number_format($totals['total_amount'] / $totals['total_count'], 2) : '0.00'; ?></p>
                            <span class="stat-label">Per transaction</span>
                        </div>
                    </div>

                    <div class="stat-card methods">
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Payment Methods</h3>
                            <p class="stat-value"><?php echo count($paymentStats); ?></p>
                            <span class="stat-label">Methods in use</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Statistics -->
                <div class="payment-stats-table">
                    <h3>Payment Method Breakdown</h3>
                    <?php if (!empty($paymentStats)): ?>
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Transactions</th>
                                    <th>Total Amount</th>
                                    <th>Average Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentStats as $stat): 
                                    $percentage = ($totals['total_count'] > 0) ? ($stat['count'] / $totals['total_count'] * 100) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="method-badge method-<?php echo $stat['payment_method']; ?>">
                                                <i class="fas fa-<?php 
                                                    echo $stat['payment_method'] === 'cash' ? 'money-bill-wave' : 
                                                        ($stat['payment_method'] === 'bank_transfer' ? 'university' : 
                                                        ($stat['payment_method'] === 'gcash' ? 'mobile-alt' : 
                                                        ($stat['payment_method'] === 'credit_card' ? 'credit-card' : 'wallet'))); 
                                                ?>"></i>
                                                <?php echo ucwords(str_replace('_', ' ', $stat['payment_method'])); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($stat['count']); ?></strong></td>
                                        <td><strong>₱<?php echo number_format($stat['total_amount'], 2); ?></strong></td>
                                        <td>₱<?php echo number_format($stat['avg_amount'], 2); ?></td>
                                        <td>
                                            <div class="percentage-bar">
                                                <div class="percentage-fill" style="width: <?php echo $percentage; ?>%"></div>
                                                <span class="percentage-text"><?php echo number_format($percentage, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="fas fa-chart-line"></i>
                            <p>No payment statistics available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Payment Methods Management -->
            <section class="payment-methods-section">
                <h2 class="section-title">
                    <i class="fas fa-toggle-on"></i>
                    Payment Methods
                </h2>

                <div class="settings-card">
                    <p class="section-description">Enable or disable payment methods available to customers</p>
                    
                    <div class="payment-methods-grid">
                        <?php foreach ($settings['payment_methods'] as $setting): ?>
                            <div class="payment-method-toggle">
                                <div class="method-info">
                                    <div class="method-icon-badge <?php echo str_replace('_enabled', '', $setting['setting_key']); ?>">
                                        <i class="fas fa-<?php 
                                            $key = str_replace('_enabled', '', $setting['setting_key']);
                                            echo $key === 'cash' ? 'money-bill-wave' : 
                                                ($key === 'bank_transfer' ? 'university' : 
                                                ($key === 'gcash' ? 'mobile-alt' : 
                                                ($key === 'credit_card' ? 'credit-card' : 'wallet'))); 
                                        ?>"></i>
                                    </div>
                                    <div>
                                        <h4><?php echo ucwords(str_replace(['_enabled', '_'], ['', ' '], $setting['setting_key'])); ?></h4>
                                        <p>
                                            <?php 
                                            $key = str_replace('_enabled', '', $setting['setting_key']);
                                            echo $key === 'cash' ? 'Cash payment on arrival' : 
                                                ($key === 'bank_transfer' ? 'Bank transfer payments' : 
                                                ($key === 'gcash' ? 'GCash mobile payments' : 
                                                ($key === 'credit_card' ? 'Credit/Debit card payments' : 'PayMaya payments'))); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           name="<?php echo $setting['setting_key']; ?>" 
                                           <?php echo $setting['setting_value'] == '1' ? 'checked' : ''; ?>
                                           onchange="markAsChanged(this)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Bank Transfer Details -->
            <section class="bank-details-section">
                <h2 class="section-title">
                    <i class="fas fa-university"></i>
                    Bank Transfer Details
                </h2>

                <div class="settings-card">
                    <p class="section-description">Manage bank account information displayed to customers</p>
                    
                    <div class="form-grid">
                        <?php foreach ($settings['bank_details'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace(['bank_', '_'], ['', ' '], $setting['setting_key'])); ?>
                                </label>
                                <input type="text" 
                                       id="<?php echo $setting['setting_key']; ?>"
                                       name="<?php echo $setting['setting_key']; ?>"
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       class="form-control"
                                       onchange="markAsChanged(this)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- GCash Details -->
            <section class="gcash-details-section">
                <h2 class="section-title">
                    <i class="fas fa-mobile-alt"></i>
                    GCash Payment Details
                </h2>

                <div class="settings-card">
                    <p class="section-description">Manage GCash payment information displayed to customers</p>
                    
                    <div class="form-grid">
                        <?php foreach ($settings['gcash_details'] as $setting): ?>
                            <div class="form-group">
                                <label for="<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace(['gcash_', '_'], ['', ' '], $setting['setting_key'])); ?>
                                </label>
                                <?php if ($setting['setting_key'] === 'gcash_qr_code'): ?>
                                    <div class="qr-upload-wrapper">
                                        <input type="file" 
                                               id="<?php echo $setting['setting_key']; ?>_file"
                                               accept="image/*"
                                               class="file-input"
                                               onchange="handleQRUpload(this)">
                                        <input type="hidden" 
                                               id="<?php echo $setting['setting_key']; ?>"
                                               name="<?php echo $setting['setting_key']; ?>"
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <button type="button" class="btn btn-outline" onclick="document.getElementById('<?php echo $setting['setting_key']; ?>_file').click()">
                                            <i class="fas fa-upload"></i> Upload QR Code
                                        </button>
                                        <?php if (!empty($setting['setting_value'])): ?>
                                            <div class="qr-preview">
                                                <img src="<?php echo htmlspecialchars($setting['setting_value']); ?>" alt="GCash QR Code">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="text" 
                                           id="<?php echo $setting['setting_key']; ?>"
                                           name="<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           class="form-control"
                                           onchange="markAsChanged(this)">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Payment Instructions -->
            <section class="instructions-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Payment Instructions
                </h2>

                <div class="settings-card">
                    <p class="section-description">General payment instructions shown to customers</p>
                    
                    <?php foreach ($settings['general'] as $setting): ?>
                        <div class="form-group">
                            <label for="<?php echo $setting['setting_key']; ?>">Instructions</label>
                            <textarea id="<?php echo $setting['setting_key']; ?>"
                                      name="<?php echo $setting['setting_key']; ?>"
                                      class="form-control"
                                      rows="4"
                                      onchange="markAsChanged(this)"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Recent Payments -->
            <section class="recent-payments-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Payments
                </h2>

                <div class="settings-card">
                    <?php if (!empty($recentPayments)): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Booking ID</th>
                                        <th>Event Type</th>
                                        <th>Payment Method</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                            <td><a href="manage-bookings.php?id=<?php echo $payment['booking_id']; ?>">#<?php echo $payment['booking_id']; ?></a></td>
                                            <td><?php echo htmlspecialchars($payment['event_type']); ?></td>
                                            <td>
                                                <span class="method-badge method-<?php echo $payment['payment_method']; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                                </span>
                                            </td>
                                            <td><strong>₱<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="fas fa-receipt"></i>
                            <p>No recent payments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="js/payment-management.js"></script>
</body>
</html>
