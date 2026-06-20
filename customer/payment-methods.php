<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $conn = getDBConnection();
    
    // Get customer's saved payment methods
    $stmt = $conn->prepare("
        SELECT *
        FROM customer_payment_methods
        WHERE customer_id = ?
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get customer information
    $userStmt = $conn->prepare("SELECT first_name, last_name, email FROM customers WHERE id = ?");
    $userStmt->execute([$userId]);
    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment settings from admin
    $settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE is_active = 1");
    $settingsStmt->execute();
    $settingsRaw = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easy access
    $settings = [];
    foreach ($settingsRaw as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
} catch (PDOException $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    $paymentMethods = [];
    $userInfo = ['first_name' => '', 'last_name' => '', 'email' => ''];
    $settings = [];
}

// Helper function to check if payment method is enabled
function isPaymentEnabled($settings, $method) {
    $key = $method . '_enabled';
    return isset($settings[$key]) && $settings[$key] == '1';
}

// Helper function to get setting value with fallback
function getSetting($settings, $key, $default = '') {
    return isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : $default;
}

$pageTitle = "Payment Methods";
$pageDescription = "Manage your payment methods";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/payment-methods.css'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-wallet"></i> Payment Methods</h1>
                    <p>Manage your saved payment methods for faster checkout</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openAddPaymentModal()">
                        <i class="fas fa-plus"></i> Add Payment Method
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Methods Section -->
    <section class="payment-methods-section">
        <div class="container">
            <!-- Info Banner -->
            <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <div class="info-content">
                    <h4>Secure Payment Information</h4>
                    <p>Your payment information is encrypted and stored securely. We never store your full card details.</p>
                </div>
            </div>

            <!-- Available Payment Options -->
            <div class="payment-options-grid">
                <?php if (isPaymentEnabled($settings, 'cash')): ?>
                <div class="payment-option-card">
                    <div class="option-icon cash">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Cash Payment</h3>
                    <p>Pay in cash upon arrival or during your event</p>
                    <span class="option-badge recommended">Most Popular</span>
                </div>
                <?php endif; ?>

                <?php if (isPaymentEnabled($settings, 'bank_transfer')): ?>
                <div class="payment-option-card">
                    <div class="option-icon bank">
                        <i class="fas fa-university"></i>
                    </div>
                    <h3>Bank Transfer</h3>
                    <p>Transfer directly to our bank account</p>
                    <span class="option-badge">Available</span>
                </div>
                <?php endif; ?>

                <?php if (isPaymentEnabled($settings, 'gcash')): ?>
                <div class="payment-option-card">
                    <div class="option-icon gcash">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>GCash</h3>
                    <p>Quick and convenient mobile payment</p>
                    <span class="option-badge">Available</span>
                </div>
                <?php endif; ?>

                <?php if (isPaymentEnabled($settings, 'credit_card')): ?>
                <div class="payment-option-card">
                    <div class="option-icon card">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Credit/Debit Card</h3>
                    <p>Pay securely with your card</p>
                    <span class="option-badge">Available</span>
                </div>
                <?php else: ?>
                <div class="payment-option-card">
                    <div class="option-icon card">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Credit/Debit Card</h3>
                    <p>Pay securely with your card</p>
                    <span class="option-badge coming-soon">Coming Soon</span>
                </div>
                <?php endif; ?>

                <?php if (isPaymentEnabled($settings, 'paymaya')): ?>
                <div class="payment-option-card">
                    <div class="option-icon paymaya">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>PayMaya</h3>
                    <p>Pay using PayMaya digital wallet</p>
                    <span class="option-badge">Available</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Saved Payment Methods -->
            <?php if (!empty($paymentMethods)): ?>
            <div class="saved-methods-section">
                <h2 class="section-title">
                    <i class="fas fa-bookmark"></i>
                    Saved Payment Methods
                </h2>

                <div class="payment-methods-list">
                    <?php foreach ($paymentMethods as $method): ?>
                        <div class="payment-method-card" data-method-id="<?php echo $method['id']; ?>">
                            <div class="method-icon-wrapper">
                                <div class="method-icon <?php echo $method['payment_type']; ?>">
                                    <i class="fas fa-<?php 
                                        echo $method['payment_type'] === 'credit_card' ? 'credit-card' : 
                                            ($method['payment_type'] === 'debit_card' ? 'credit-card' : 
                                            ($method['payment_type'] === 'bank_account' ? 'university' : 'wallet')); 
                                    ?>"></i>
                                </div>
                            </div>

                            <div class="method-details">
                                <div class="method-header">
                                    <h3>
                                        <?php echo ucwords(str_replace('_', ' ', $method['payment_type'])); ?>
                                        <?php if ($method['is_default']): ?>
                                            <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                    </h3>
                                    <?php if ($method['provider']): ?>
                                        <span class="provider-name"><?php echo htmlspecialchars($method['provider']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($method['payment_type'] === 'credit_card' || $method['payment_type'] === 'debit_card'): ?>
                                    <div class="method-info">
                                        <p class="card-number">•••• •••• •••• <?php echo htmlspecialchars($method['last_four_digits']); ?></p>
                                        <?php if ($method['cardholder_name']): ?>
                                            <p class="cardholder-name"><?php echo htmlspecialchars($method['cardholder_name']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($method['expiry_month'] && $method['expiry_year']): ?>
                                            <p class="expiry-date">
                                                <i class="fas fa-calendar"></i>
                                                Expires: <?php echo sprintf('%02d/%d', $method['expiry_month'], $method['expiry_year']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="method-info">
                                        <p class="account-info">
                                            <?php echo $method['payment_type'] === 'bank_account' ? 'Bank Account' : 'Digital Wallet'; ?>
                                            <?php if ($method['last_four_digits']): ?>
                                                ending in <?php echo htmlspecialchars($method['last_four_digits']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="method-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        Added <?php echo date('M j, Y', strtotime($method['created_at'])); ?>
                                    </span>
                                    <?php if ($method['is_active']): ?>
                                        <span class="meta-item status-active">
                                            <i class="fas fa-check-circle"></i>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="meta-item status-inactive">
                                            <i class="fas fa-times-circle"></i>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="method-actions">
                                <?php if (!$method['is_default']): ?>
                                    <button class="btn btn-outline btn-sm" onclick="setDefaultMethod(<?php echo $method['id']; ?>)">
                                        <i class="fas fa-star"></i> Set as Default
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-outline btn-sm" onclick="editMethod(<?php echo $method['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-outline btn-sm btn-danger" onclick="deleteMethod(<?php echo $method['id']; ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>No Saved Payment Methods</h3>
                <p>You haven't added any payment methods yet. Add a payment method for faster checkout when booking venues.</p>
                <button class="btn btn-primary" onclick="openAddPaymentModal()">
                    <i class="fas fa-plus"></i> Add Payment Method
                </button>
            </div>
            <?php endif; ?>

            <!-- Bank Transfer Information -->
            <?php if (isPaymentEnabled($settings, 'bank_transfer')): ?>
            <div class="bank-info-section">
                <h2 class="section-title">
                    <i class="fas fa-university"></i>
                    Bank Transfer Information
                </h2>

                <div class="bank-info-card">
                    <div class="bank-info-content">
                        <div class="info-row">
                            <span class="info-label">Bank Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars(getSetting($settings, 'bank_name', 'BDO Unibank')); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars(getSetting($settings, 'bank_account_name', 'Mavic\'s Resort and Events Place')); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account Number:</span>
                            <span class="info-value account-number"><?php echo htmlspecialchars(getSetting($settings, 'bank_account_number', '0123-4567-8901')); ?></span>
                            <button class="copy-btn" onclick="copyAccountNumber('<?php echo htmlspecialchars(getSetting($settings, 'bank_account_number', '0123-4567-8901')); ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Branch:</span>
                            <span class="info-value"><?php echo htmlspecialchars(getSetting($settings, 'bank_branch', 'Tanza, Cavite')); ?></span>
                        </div>
                    </div>
                    <div class="bank-info-note">
                        <i class="fas fa-info-circle"></i>
                        <p><?php echo htmlspecialchars(getSetting($settings, 'payment_instructions', 'Please use your booking ID as reference when making a bank transfer. Send the payment confirmation to our email or WhatsApp.')); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- GCash Information -->
            <?php if (isPaymentEnabled($settings, 'gcash')): ?>
            <div class="gcash-info-section">
                <h2 class="section-title">
                    <i class="fas fa-mobile-alt"></i>
                    GCash Payment Information
                </h2>

                <div class="gcash-info-card">
                    <?php 
                    $qrCode = getSetting($settings, 'gcash_qr_code');
                    if (!empty($qrCode) && file_exists(__DIR__ . '/../admin/' . $qrCode)): 
                    ?>
                    <div class="gcash-qr-section">
                        <img src="<?php echo htmlspecialchars('../admin/' . $qrCode); ?>" alt="GCash QR Code" class="gcash-qr-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <p style="display:none; text-align: center; color: #999; padding: 20px;">QR Code could not be loaded</p>
                    </div>
                    <?php else: ?>
                    <div class="gcash-qr-section">
                        <div class="qr-placeholder">
                            <i class="fas fa-qrcode"></i>
                            <p>QR Code not available</p>
                            <small style="color: #999;">Send payment to the number below</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="gcash-info-content">
                        <div class="info-row">
                            <span class="info-label">GCash Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars(getSetting($settings, 'gcash_number', '+63 961 306 7957')); ?></span>
                            <button class="copy-btn" onclick="copyGCashNumber('<?php echo htmlspecialchars(getSetting($settings, 'gcash_number', '+63 961 306 7957')); ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars(getSetting($settings, 'gcash_account_name', 'Mavic\'s Resort')); ?></span>
                        </div>
                        <div class="gcash-info-note">
                            <i class="fas fa-info-circle"></i>
                            <p><?php echo htmlspecialchars(getSetting($settings, 'payment_instructions', 'After payment, please send a screenshot of your GCash receipt with your booking ID to confirm your payment.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Add Payment Method Modal -->
<div id="addPaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add Payment Method</h2>
            <button class="close-modal" onclick="closeAddPaymentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <p>Currently, saved payment methods are for reference only. All payments are processed manually via cash, bank transfer, or GCash.</p>
            </div>

            <form id="addPaymentForm" onsubmit="handleAddPayment(event)">
                <div class="form-group">
                    <label for="paymentType">Payment Type</label>
                    <select id="paymentType" name="payment_type" class="form-control" required>
                        <option value="">Select payment type</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="bank_account">Bank Account</option>
                        <option value="digital_wallet">Digital Wallet (GCash, PayMaya)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="provider">Provider/Bank Name</label>
                    <input type="text" id="provider" name="provider" class="form-control" placeholder="e.g., BDO, BPI, GCash">
                </div>

                <div class="form-group">
                    <label for="lastFourDigits">Last 4 Digits</label>
                    <input type="text" id="lastFourDigits" name="last_four_digits" class="form-control" maxlength="4" pattern="[0-9]{4}" placeholder="1234">
                </div>

                <div class="form-group">
                    <label for="cardholderName">Account/Cardholder Name</label>
                    <input type="text" id="cardholderName" name="cardholder_name" class="form-control" placeholder="Name on card/account">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="expiryMonth">Expiry Month</label>
                        <select id="expiryMonth" name="expiry_month" class="form-control">
                            <option value="">MM</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expiryYear">Expiry Year</label>
                        <select id="expiryYear" name="expiry_year" class="form-control">
                            <option value="">YYYY</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_default" value="1">
                        <span>Set as default payment method</span>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddPaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Payment Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/payment-methods.js"></script>

<script>
function copyAccountNumber(accountNumber) {
    navigator.clipboard.writeText(accountNumber).then(() => {
        showNotification('Account number copied to clipboard!', 'success');
    });
}

function copyGCashNumber(gcashNumber) {
    navigator.clipboard.writeText(gcashNumber).then(() => {
        showNotification('GCash number copied to clipboard!', 'success');
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function openAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.add('active');
}

function closeAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.remove('active');
    document.getElementById('addPaymentForm').reset();
}

function handleAddPayment(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('api/add-payment-method.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Payment method added successfully!', 'success');
            closeAddPaymentModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Failed to add payment method', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function setDefaultMethod(methodId) {
    if (confirm('Set this as your default payment method?')) {
        fetch('api/set-default-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ method_id: methodId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Default payment method updated!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Failed to update default method', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
}

function editMethod(methodId) {
    showNotification('Edit functionality coming soon!', 'info');
}

function deleteMethod(methodId) {
    if (confirm('Are you sure you want to remove this payment method?')) {
        fetch('api/delete-payment-method.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ method_id: methodId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Payment method removed successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Failed to remove payment method', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
}

// Close modal when clicking outside
document.getElementById('addPaymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddPaymentModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
