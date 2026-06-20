<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    $_SESSION['error'] = 'Invalid booking ID';
    header('Location: dashboard.php');
    exit();
}

try {
    $conn = getDBConnection();
    
    // Fetch payment settings from database
    $settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE is_active = 1");
    $settingsStmt->execute();
    $settingsRaw = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $paymentSettings = [];
    foreach ($settingsRaw as $setting) {
        $paymentSettings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Helper function to get setting with fallback
    function getSetting($settings, $key, $default = '') {
        return isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : $default;
    }
    
    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            v.name as venue_name,
            p.name as package_name,
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
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $_SESSION['error'] = 'Booking not found or you do not have permission to access it';
        header('Location: dashboard.php');
        exit();
    }
    
    // Check if booking can accept payments
    if ($booking['status'] === 'cancelled') {
        $_SESSION['error'] = 'Cannot make payment for a cancelled booking.';
        header('Location: booking-details.php?id=' . $booking_id);
        exit();
    }
    
    if ($booking['payment_status'] === 'paid') {
        $_SESSION['info'] = 'This booking is already fully paid.';
        header('Location: booking-details.php?id=' . $booking_id);
        exit();
    }
    
    // Fetch previous payments
    $stmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE booking_id = ? AND payment_status = 'verified'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total paid and remaining balance
    $total_paid = 0;
    foreach ($payments as $payment) {
        $total_paid += $payment['amount'];
    }
    
    $remaining_balance = $booking['balance'];
    
} catch (PDOException $e) {
    error_log("Error fetching booking for payment: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $paymentSettings = [];
    die("Error loading payment page: " . $e->getMessage());
} catch (Exception $e) {
    error_log("General error in payment.php: " . $e->getMessage());
    $paymentSettings = [];
    die("Error loading payment page: " . $e->getMessage());
}

$pageTitle = "Make Payment - Booking #" . $booking['id'];
$pageDescription = "Complete your payment for booking #" . $booking['id'];
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/payment.css'];
$pageJSFiles = ['js/payment.js'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Payment Header -->
    <section class="payment-header">
        <div class="container">
            <a href="booking-details.php?id=<?php echo $booking_id; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Booking Details
            </a>
            <h1>Make Payment</h1>
            <p class="subtitle">Complete your payment securely for Booking #<?php echo $booking['id']; ?></p>
        </div>
    </section>

    <!-- Payment Content -->
    <section class="payment-section">
        <div class="container">
            <div class="payment-layout">
                <!-- Payment Form -->
                <div class="payment-form-container">
                    <!-- Payment Amount Selection -->
                    <div class="payment-card">
                        <div class="card-header">
                            <h2><i class="fas fa-money-bill-wave"></i> Payment Amount</h2>
                        </div>
                        <div class="card-body">
                            <div class="amount-options">
                                <div class="amount-option" data-amount="<?php echo $remaining_balance; ?>">
                                    <input type="radio" id="full-payment" name="payment-type" value="full" checked>
                                    <label for="full-payment">
                                        <div class="option-header">
                                            <span class="option-title">Full Payment</span>
                                            <span class="recommended-badge">Recommended</span>
                                        </div>
                                        <div class="option-amount">₱<?php echo number_format($remaining_balance, 2); ?></div>
                                        <div class="option-description">Pay the complete remaining balance</div>
                                    </label>
                                </div>
                                
                                <div class="amount-option" data-amount="<?php echo $remaining_balance / 2; ?>">
                                    <input type="radio" id="partial-payment" name="payment-type" value="partial">
                                    <label for="partial-payment">
                                        <div class="option-header">
                                            <span class="option-title">Partial Payment</span>
                                        </div>
                                        <div class="option-amount">₱<?php echo number_format($remaining_balance / 2, 2); ?></div>
                                        <div class="option-description">Pay 50% of remaining balance</div>
                                    </label>
                                </div>
                                
                                <div class="amount-option" data-amount="custom">
                                    <input type="radio" id="custom-payment" name="payment-type" value="custom">
                                    <label for="custom-payment">
                                        <div class="option-header">
                                            <span class="option-title">Custom Amount</span>
                                        </div>
                                        <div class="custom-input-wrapper">
                                            <span class="currency-symbol">₱</span>
                                            <input type="number" 
                                                   id="custom-amount" 
                                                   placeholder="0.00" 
                                                   min="500" 
                                                   max="<?php echo $remaining_balance; ?>"
                                                   step="0.01"
                                                   disabled>
                                        </div>
                                        <div class="option-description">Enter your preferred amount (min ₱500)</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="payment-card">
                        <div class="card-header">
                            <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                        </div>
                        <div class="card-body">
                            <div class="payment-methods">
                                <!-- GCash -->
                                <div class="payment-method-option" data-method="gcash">
                                    <input type="radio" id="method-gcash" name="payment-method" value="gcash" checked>
                                    <label for="method-gcash">
                                        <div class="method-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="method-info">
                                            <span class="method-name">GCash</span>
                                            <span class="method-description">Pay via GCash e-wallet</span>
                                        </div>
                                        <div class="method-check">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </label>
                                </div>

                                <!-- Bank Transfer -->
                                <div class="payment-method-option" data-method="bank">
                                    <input type="radio" id="method-bank" name="payment-method" value="bank_transfer">
                                    <label for="method-bank">
                                        <div class="method-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="method-info">
                                            <span class="method-name">Bank Transfer</span>
                                            <span class="method-description">Direct bank deposit</span>
                                        </div>
                                        <div class="method-check">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </label>
                                </div>

                                <!-- PayMaya -->
                                <div class="payment-method-option" data-method="paymaya">
                                    <input type="radio" id="method-paymaya" name="payment-method" value="paymaya">
                                    <label for="method-paymaya">
                                        <div class="method-icon">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="method-info">
                                            <span class="method-name">PayMaya</span>
                                            <span class="method-description">Pay via PayMaya wallet</span>
                                        </div>
                                        <div class="method-check">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </label>
                                </div>

                                <!-- Cash -->
                                <div class="payment-method-option" data-method="cash">
                                    <input type="radio" id="method-cash" name="payment-method" value="cash">
                                    <label for="method-cash">
                                        <div class="method-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="method-info">
                                            <span class="method-name">Cash Payment</span>
                                            <span class="method-description">Pay in person at our office</span>
                                        </div>
                                        <div class="method-check">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Details Section -->
                            <div id="payment-details" class="payment-details">
                                <!-- GCash Details -->
                                <div class="payment-details-content" id="gcash-details">
                                    <h3>GCash Payment Instructions</h3>
                                    <div class="instruction-box">
                                        <?php if (getSetting($paymentSettings, 'gcash_qr_code')): ?>
                                        <div class="gcash-qr-section" style="text-align: center; margin-bottom: 20px;">
                                            <img src="<?php echo htmlspecialchars('../admin/' . getSetting($paymentSettings, 'gcash_qr_code')); ?>" 
                                                 alt="GCash QR Code" 
                                                 class="gcash-qr-image" 
                                                 style="max-width: 250px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                                 onerror="this.parentElement.innerHTML='<p style=\"color: #999;\">QR Code not available</p>';">
                                            <p style="font-size: 14px; color: #666; margin-top: 10px;">Scan this QR code with your GCash app</p>
                                        </div>
                                        <?php endif; ?>
                                        <div class="account-details">
                                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'gcash_account_name', "Mavic's Resort")); ?></p>
                                            <p><strong>Mobile Number:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'gcash_number', '0961 306 7957')); ?></p>
                                        </div>
                                        <ol>
                                            <li><strong>Option 1:</strong> Scan the QR code above with your GCash app</li>
                                            <li><strong>Option 2:</strong> Open your GCash app and select "Send Money"</li>
                                            <li>Enter the amount: <strong id="gcash-amount">₱<?php echo number_format($remaining_balance, 2); ?></strong></li>
                                            <li>Complete the transaction</li>
                                            <li>Take a screenshot of the receipt</li>
                                            <li>Upload the receipt below</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Bank Transfer Details -->
                                <div class="payment-details-content" id="bank-details" style="display: none;">
                                    <h3>Bank Transfer Instructions</h3>
                                    <div class="instruction-box">
                                        <div class="account-details">
                                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'bank_name', 'BDO Unibank')); ?></p>
                                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'bank_account_name', "Mavic's Resort")); ?></p>
                                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'bank_account_number', '1234-5678-9012')); ?></p>
                                            <p><strong>Branch:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'bank_branch', 'San Nicolas Branch')); ?></p>
                                        </div>
                                        <ol>
                                            <li>Go to your bank or use online banking</li>
                                            <li>Transfer the amount: <strong id="bank-amount">₱<?php echo number_format($remaining_balance, 2); ?></strong></li>
                                            <li>Use booking ID #<?php echo $booking['id']; ?> as reference</li>
                                            <li>Keep the deposit slip or transaction receipt</li>
                                            <li>Upload the receipt below</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- PayMaya Details -->
                                <div class="payment-details-content" id="paymaya-details" style="display: none;">
                                    <h3>PayMaya Payment Instructions</h3>
                                    <div class="instruction-box">
                                        <div class="account-details">
                                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'paymaya_account_name', "Mavic's Resort")); ?></p>
                                            <p><strong>Mobile Number:</strong> <?php echo htmlspecialchars(getSetting($paymentSettings, 'paymaya_number', '0961 306 7957')); ?></p>
                                        </div>
                                        <ol>
                                            <li>Open your PayMaya app</li>
                                            <li>Select "Send Money"</li>
                                            <li>Enter the amount: <strong id="paymaya-amount">₱<?php echo number_format($remaining_balance, 2); ?></strong></li>
                                            <li>Complete the transaction</li>
                                            <li>Take a screenshot of the receipt</li>
                                            <li>Upload the receipt below</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Cash Payment Details -->
                                <div class="payment-details-content" id="cash-details" style="display: none;">
                                    <h3>Cash Payment Instructions</h3>
                                    <div class="instruction-box">
                                        <div class="account-details">
                                            <p><strong>Office Address:</strong></p>
                                            <p>Purok 5 Sitio Labac Calangay 4207<br>San Nicolas, Batangas, Philippines</p>
                                            <p><strong>Office Hours:</strong> Mon-Sun: 8:00 AM - 6:00 PM</p>
                                            <p><strong>Contact:</strong> +63 961 306 7957</p>
                                        </div>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <p>Please bring your booking ID: <strong>#<?php echo $booking['id']; ?></strong></p>
                                        </div>
                                        <p>You can pay in cash at our office during business hours. Please inform us in advance of your visit.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Proof of Payment Upload (only for non-cash) -->
                            <div id="upload-section" class="upload-section">
                                <h3>Upload Proof of Payment</h3>
                                <div class="upload-area" id="upload-area">
                                    <input type="file" id="payment-proof" accept="image/*" hidden>
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Click to upload or drag and drop</p>
                                        <span>PNG, JPG up to 5MB</span>
                                    </div>
                                    <div class="upload-preview" id="upload-preview" style="display: none;">
                                        <img id="preview-image" src="" alt="Preview">
                                        <button type="button" class="remove-upload" id="remove-upload">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Reference Number -->
                            <div class="form-group">
                                <label for="reference-number">Reference/Transaction Number</label>
                                <input type="text" 
                                       id="reference-number" 
                                       class="form-control" 
                                       placeholder="Enter your transaction reference number"
                                       required>
                                <small class="form-text">Enter the reference number from your payment receipt</small>
                            </div>

                            <!-- Notes -->
                            <div class="form-group">
                                <label for="payment-notes">Additional Notes (Optional)</label>
                                <textarea id="payment-notes" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Any additional information about your payment"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="payment-actions">
                        <button type="button" class="btn btn-outline" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" id="submit-payment" class="btn btn-primary">
                            <i class="fas fa-check"></i> Submit Payment
                        </button>
                    </div>
                </div>

                <!-- Payment Summary Sidebar -->
                <div class="payment-sidebar">
                    <!-- Booking Summary -->
                    <div class="summary-card">
                        <h3>Booking Summary</h3>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Booking ID</span>
                                <span>#<?php echo $booking['id']; ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Venue</span>
                                <span><?php echo htmlspecialchars($booking['venue_name']); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Event Date</span>
                                <span><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Time</span>
                                <span>
                                    <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="summary-card">
                        <h3>Payment Summary</h3>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Total Amount</span>
                                <span>₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Already Paid</span>
                                <span class="text-success">₱<?php echo number_format($total_paid, 2); ?></span>
                            </div>
                            <div class="summary-row highlight">
                                <span>Remaining Balance</span>
                                <span>₱<?php echo number_format($remaining_balance, 2); ?></span>
                            </div>
                            <div class="divider"></div>
                            <div class="summary-row total">
                                <span>Amount to Pay</span>
                                <span id="amount-to-pay">₱<?php echo number_format($remaining_balance, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="security-notice">
                        <div class="notice-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="notice-content">
                            <h4>Secure Payment</h4>
                            <p>Your payment information is secure. All transactions are verified by our team.</p>
                        </div>
                    </div>

                    <!-- Help -->
                    <div class="help-box">
                        <h4>Need Help?</h4>
                        <p>If you encounter any issues, please contact us:</p>
                        <a href="tel:+639613067957" class="help-link">
                            <i class="fas fa-phone"></i> +63 961 306 7957
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Pass data to JavaScript
window.paymentData = {
    bookingId: <?php echo $booking['id']; ?>,
    remainingBalance: <?php echo $remaining_balance; ?>,
    customerId: <?php echo $_SESSION['user_id']; ?>
};
</script>

<?php include 'includes/footer.php'; ?>