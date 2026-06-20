<?php
require_once 'includes/config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch footer settings (for contact information)
$footerSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM footer_settings WHERE is_active = 1");
    while ($row = $stmt->fetch()) {
        $footerSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching footer settings: " . $e->getMessage());
}

// Fetch contact page settings (for page-specific content)
$contactSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM contact_page_settings");
    while ($row = $stmt->fetch()) {
        $contactSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching contact settings: " . $e->getMessage());
}

// Helper function for footer settings (contact info)
if (!function_exists('getFooterSetting')) {
    function getFooterSetting($key, $default = '') {
        global $footerSettings;
        return $footerSettings[$key] ?? $default;
    }
}

// Helper function for contact page settings
if (!function_exists('getContactSetting')) {
    function getContactSetting($key, $default = '') {
        global $contactSettings;
        return $contactSettings[$key] ?? $default;
    }
}

// Page-specific CSS and JavaScript files
$pageCSSFiles = ['styles/header.css', 'styles/footer.css','styles/contact.css'];
$pageJSFiles = ['js/contact.js'];

// Include header
include 'includes/header.php';
?>

<!-- Contact Page -->
<div class="contact-page">
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo htmlspecialchars(getContactSetting('page_title', 'Get in Touch')); ?></h1>
                <p><?php echo htmlspecialchars(getContactSetting('page_subtitle', 'We\'d love to hear from you.')); ?></p>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-content">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-7 col-md-6">
                    <div class="contact-form-wrapper">
                        <h2><i class="fas fa-envelope"></i> Send Us a Message</h2>
                        
                        <!-- Success Message -->
                        <div id="successMessage" class="alert alert-success" style="display: none;">
                            <i class="fas fa-check-circle"></i>
                            <span id="successText"></span>
                        </div>
                        
                        <!-- Error Message -->
                        <div id="errorMessage" class="alert alert-error" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="errorText"></span>
                        </div>
                        
                        <?php if (getContactSetting('enable_contact_form', '1') == '1'): ?>
                        
                        <?php if (!$isLoggedIn): ?>
                        <!-- Login Required Message -->
                        <div class="login-required-notice">
                            <div class="notice-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="notice-content">
                                <h3>Login Required</h3>
                                <p>You need to be logged in to send us a message. Please login or create an account to continue.</p>
                                <div class="notice-actions">
                                    <a href="login.php?redirect=contact.php" class="btn-login">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Login Now
                                    </a>
                                    <a href="register.php?redirect=contact.php" class="btn-register">
                                        <i class="fas fa-user-plus"></i>
                                        Create Account
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form id="contactForm" class="contact-form" novalidate <?php echo !$isLoggedIn ? 'style="display:none;"' : ''; ?>>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-user"></i>
                                        Full Name <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        placeholder="Enter your full name" 
                                        required
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i>
                                        Email Address <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        placeholder="your.email@example.com" 
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i>
                                        Phone Number
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        placeholder="+63 912 345 6789"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">
                                        <i class="fas fa-tag"></i>
                                        Subject <span class="required">*</span>
                                    </label>
                                    <select id="subject" name="subject" required>
                                        <option value="">Select a subject</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Booking Information">Booking Information</option>
                                        <option value="Venue Question">Venue Question</option>
                                        <option value="Package Inquiry">Package Inquiry</option>
                                        <option value="Complaint">Complaint</option>
                                        <option value="Feedback">Feedback</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">
                                    <i class="fas fa-comment-alt"></i>
                                    Message <span class="required">*</span>
                                </label>
                                <textarea 
                                    id="message" 
                                    name="message" 
                                    rows="6" 
                                    placeholder="Tell us what's on your mind..." 
                                    required
                                ></textarea>
                            </div>
                            
                            <button type="submit" class="btn-submit" id="submitBtn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Send Message</span>
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="form-disabled">
                            <i class="fas fa-info-circle"></i>
                            <p>Contact form is currently disabled. Please use the contact information provided to reach us.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-lg-5 col-md-6">
                    <div class="contact-info-wrapper">
                        <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
                        
                        <div class="info-items">
                            <!-- Address -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <h3>Address</h3>
                                    <p><?php echo nl2br(htmlspecialchars(getFooterSetting('contact_address', 'Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines'))); ?></p>
                                </div>
                            </div>
                            
                            <!-- Phone -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-content">
                                    <h3>Phone</h3>
                                    <p>
                                        <a href="tel:<?php echo str_replace(' ', '', getFooterSetting('contact_phone_1')); ?>">
                                            <?php echo htmlspecialchars(getFooterSetting('contact_phone_1', '+63 961 306 7957')); ?>
                                        </a>
                                    </p>
                                    <?php if (!empty(getFooterSetting('contact_phone_2'))): ?>
                                    <p>
                                        <a href="tel:<?php echo str_replace(' ', '', getFooterSetting('contact_phone_2')); ?>">
                                            <?php echo htmlspecialchars(getFooterSetting('contact_phone_2')); ?>
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <h3>Email</h3>
                                    <p>
                                        <a href="mailto:<?php echo getFooterSetting('contact_email'); ?>">
                                            <?php echo htmlspecialchars(getFooterSetting('contact_email', 'info@mavicsresort.com')); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Business Hours -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <h3>Business Hours</h3>
                                    <p><?php echo nl2br(htmlspecialchars(getFooterSetting('contact_hours', 'Monday - Sunday: 8:00 AM - 10:00 PM'))); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <?php if (!empty(getFooterSetting('social_facebook')) || !empty(getFooterSetting('social_instagram'))): ?>
                        <div class="social-media">
                            <h3>Follow Us</h3>
                            <div class="social-links">
                                <?php if (!empty(getFooterSetting('social_facebook'))): ?>
                                <a href="<?php echo htmlspecialchars(getFooterSetting('social_facebook')); ?>" target="_blank" rel="noopener" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty(getFooterSetting('social_instagram'))): ?>
                                <a href="<?php echo htmlspecialchars(getFooterSetting('social_instagram')); ?>" target="_blank" rel="noopener" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty(getFooterSetting('social_twitter'))): ?>
                                <a href="<?php echo htmlspecialchars(getFooterSetting('social_twitter')); ?>" target="_blank" rel="noopener" title="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Map Section -->
    <?php if (!empty(getContactSetting('map_embed_url'))): ?>
    <section class="contact-map">
        <div class="container-fluid">
            <div class="map-wrapper">
                <iframe 
                    src="<?php echo htmlspecialchars(getContactSetting('map_embed_url')); ?>" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
