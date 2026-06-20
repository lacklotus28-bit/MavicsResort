<?php
// booking.php - Main booking page with form and processing
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    require_once 'config/database.php';
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Fetch payment settings from database
        $settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE is_active = 1");
        $settingsStmt->execute();
        $settingsRaw = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentSettings = [];
        foreach ($settingsRaw as $setting) {
            $paymentSettings[$setting['setting_key']] = $setting['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        $paymentSettings = [];
    }
} else {
    $paymentSettings = [];
}

$pageTitle = "Book Your Event - Mavic's Resort";
$pageDescription = "Complete your booking at Mavic's Resort";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/booking.css'];
$pageJSFiles = ['js/booking.js'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Booking Header -->
    <section class="booking-header">
        <div class="container">
            <div class="booking-header-content">
                <h1>Complete Your Booking</h1>
                <p>Just a few steps to secure your perfect event</p>
                <div class="booking-steps">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-text">Event Details</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-text">Guest Information</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-text">Payment</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-text">Confirmation</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Form Section -->
    <section class="booking-form-section">
        <div class="container">
            <div class="booking-layout">
                <!-- Main Form -->
                <div class="booking-form-container">
                    <?php if (!$isLoggedIn): ?>
                    <!-- Login/Register Prompt -->
                    <div class="login-prompt">
                        <div class="login-prompt-content">
                            <i class="fas fa-user-circle"></i>
                            <h3>Login Required</h3>
                            <p>Please login or create an account to proceed with your booking</p>
                            <div class="login-actions">
                                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                                <a href="register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <!-- Step 1: Event Details -->
                    <div class="form-step active" id="step1">
                        <div class="step-header">
                            <h2>Event Details</h2>
                            <p>Tell us about your event</p>
                        </div>
                        
                        <form id="bookingForm" class="booking-form">
                            <!-- Venue Selection -->
                            <div class="form-group">
                                <label>Selected Venue</label>
                                <div class="venue-preview" id="venuePreview">
                                    <div class="venue-loading">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        Loading venue information...
                                    </div>
                                </div>
                                <button type="button" class="btn-change-venue" onclick="changeVenue()">
                                    <i class="fas fa-edit"></i> Change Venue
                                </button>
                            </div>

                            <!-- Package Selection (if applicable) -->
                            <div class="form-group" id="packageGroup" style="display: none;">
                                <label>Package</label>
                                <div class="package-preview" id="packagePreview">
                                    <!-- Package info will be loaded here -->
                                </div>
                                <button type="button" class="btn-change-package" onclick="changePackage()">
                                    <i class="fas fa-edit"></i> Change Package
                                </button>
                            </div>

                            <!-- Event Type -->
                            <div class="form-group">
                                <label for="eventType">Event Type *</label>
                                <select id="eventType" name="eventType" class="form-control" required>
                                    <option value="">Select event type</option>
                                    <option value="Wedding Reception">Wedding Reception</option>
                                    <option value="Birthday Party">Birthday Party</option>
                                    <option value="Corporate Event">Corporate Event</option>
                                    <option value="Conference">Conference</option>
                                    <option value="Social Gathering">Social Gathering</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Event Date -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="eventDate">Event Date *</label>
                                    <input type="date" id="eventDate" name="eventDate" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="guestCount">Number of Guests *</label>
                                    <input type="number" id="guestCount" name="guestCount" class="form-control" min="1" required>
                                </div>
                            </div>

                            <!-- Event Time -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="startTime">Start Time *</label>
                                    <input type="time" id="startTime" name="startTime" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="endTime">End Time *</label>
                                    <input type="time" id="endTime" name="endTime" class="form-control" required>
                                </div>
                            </div>

                            <!-- Special Requests -->
                            <div class="form-group">
                                <label for="specialRequests">Special Requests</label>
                                <textarea id="specialRequests" name="specialRequests" class="form-control" rows="3" 
                                         placeholder="Any special requirements or requests for your event..."></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" onclick="nextStep()">
                                    Continue <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 2: Guest Information -->
                    <div class="form-step" id="step2">
                        <div class="step-header">
                            <h2>Guest Information</h2>
                            <p>Confirm your contact details</p>
                        </div>

                        <div class="guest-info-form">
                            <!-- Primary Contact (Pre-filled from user account) -->
                            <div class="contact-section">
                                <h3>Primary Contact</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="firstName">First Name *</label>
                                        <input type="text" id="firstName" name="firstName" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="lastName">Last Name *</label>
                                        <input type="text" id="lastName" name="lastName" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number *</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="contact-section">
                                <h3>Emergency Contact</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergencyName">Name</label>
                                        <input type="text" id="emergencyName" name="emergencyName" class="form-control"
                                               value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="emergencyPhone">Phone</label>
                                        <input type="tel" id="emergencyPhone" name="emergencyPhone" class="form-control"
                                               value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="prevStep()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div class="form-step" id="step3">
                        <div class="step-header">
                            <h2>Payment Details</h2>
                            <p>Secure your booking with payment</p>
                        </div>

                        <div class="payment-form">
                            <!-- Payment Method -->
                            <div class="form-group">
                                <label>Payment Method</label>
                                <div class="payment-methods">
                                    <div class="payment-method">
                                        <input type="radio" id="cash" name="paymentMethod" value="cash" checked>
                                        <label for="cash" class="payment-method-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Cash Payment</span>
                                            <small>Pay on arrival or pickup</small>
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" id="bank" name="paymentMethod" value="bank_transfer">
                                        <label for="bank" class="payment-method-label">
                                            <i class="fas fa-university"></i>
                                            <span>Bank Transfer</span>
                                            <small>Direct bank deposit</small>
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" id="gcash" name="paymentMethod" value="gcash">
                                        <label for="gcash" class="payment-method-label">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span>GCash</span>
                                            <small>Mobile payment</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Amount Options -->
                            <div class="payment-options" id="paymentOptions">
                                <h3>Payment Amount</h3>
                                <div class="payment-option">
                                    <input type="radio" id="downPayment" name="paymentAmount" value="down" checked>
                                    <label for="downPayment" class="payment-option-label">
                                        <div class="payment-option-info">
                                            <span class="payment-title">Down Payment</span>
                                            <span class="payment-amount" id="downPaymentAmount">₱0.00</span>
                                        </div>
                                        <small>Reserve your date with minimum payment</small>
                                    </label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" id="fullPayment" name="paymentAmount" value="full">
                                    <label for="fullPayment" class="payment-option-label">
                                        <div class="payment-option-info">
                                            <span class="payment-title">Full Payment</span>
                                            <span class="payment-amount" id="fullPaymentAmount">₱0.00</span>
                                        </div>
                                        <small>Pay the complete amount now</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Instructions -->
                            <div class="payment-instructions" id="paymentInstructions">
                                <!-- Instructions will be loaded based on selected payment method -->
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="prevStep()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" onclick="processBooking()">
                                <i class="fas fa-credit-card"></i> Complete Booking
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Confirmation -->
                    <div class="form-step" id="step4">
                        <div class="confirmation-content">
                            <div class="confirmation-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2>Booking Confirmed!</h2>
                            <p>Your booking has been successfully submitted</p>
                            
                            <div class="booking-reference">
                                <span class="reference-label">Booking Reference:</span>
                                <span class="reference-number" id="bookingReference">#</span>
                            </div>

                            <div class="confirmation-actions">
                                <button class="btn btn-primary" onclick="viewBookingDetails()">
                                    <i class="fas fa-eye"></i> View Booking Details
                                </button>
                                <button class="btn btn-outline" onclick="downloadReceipt()">
                                    <i class="fas fa-download"></i> Download Receipt
                                </button>
                            </div>

                            <div class="next-steps">
                                <h3>What's Next?</h3>
                                <ul>
                                    <li>You will receive a confirmation email shortly</li>
                                    <li>Our team will contact you within 24 hours</li>
                                    <li>Complete payment instructions have been sent to your email</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Booking Summary Sidebar -->
                <div class="booking-summary">
                    <div class="summary-card">
                        <h3>Booking Summary</h3>
                        <div class="summary-content" id="bookingSummary">
                            <div class="summary-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Loading...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Change Venue Modal -->
<div id="changeVenueModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Different Venue</h3>
                <button class="modal-close" onclick="closeModal('changeVenueModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="venues-list" id="venuesList">
                    <!-- Venues will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JavaScript
window.bookingData = {
    isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
    user: <?php echo $isLoggedIn ? json_encode($user) : 'null'; ?>,
    venueId: <?php echo isset($_GET['venue']) ? intval($_GET['venue']) : 'null'; ?>,
    packageId: <?php echo isset($_GET['package']) ? intval($_GET['package']) : 'null'; ?>,
    selectedDate: <?php echo isset($_GET['date']) ? '"' . htmlspecialchars($_GET['date']) . '"' : 'null'; ?>
};

// Pass payment settings to JavaScript
window.paymentSettings = <?php echo json_encode($paymentSettings); ?>;
</script>

<?php include 'includes/footer.php'; ?>