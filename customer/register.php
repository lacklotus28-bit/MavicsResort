<?php
// Page configuration - Set BEFORE including header
$pageTitle = "Register - Create Your Account";
$pageDescription = "Create your Mavic's Resort account to book venues and manage your events.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/register.css'];
$pageJSFiles = ['js/register.js'];

// Include header (this will use the variables above)
include 'includes/header.php';

// Check if user is already logged in (after header sets $isLoggedIn)
if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}
?>

<!-- Main Content -->
<main id="main-content">
    <section class="register-section">
        <div class="register-container">
            <!-- Left Side - Brand & Info -->
            <div class="register-brand">
                <div class="brand-content">
                    <div class="brand-logo">
                        <img src="images/bg1.jpg" alt="Mavic's Resort">
                        <h1>Mavic's Resort</h1>
                    </div>
                    <div class="brand-text">
                        <h2>Join Us Today</h2>
                        <p>Create your account to access exclusive venues, manage bookings, and enjoy personalized service for your special events.</p>
                        
                        <div class="benefits-list">
                            <div class="benefit-item">
                                <i class="fas fa-star"></i>
                                <span>Exclusive Venue Access</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Easy Booking Management</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-bell"></i>
                                <span>Event Reminders</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-gift"></i>
                                <span>Special Offers</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="decorative-elements">
                        <div class="floating-leaf leaf-1"></div>
                        <div class="floating-leaf leaf-2"></div>
                        <div class="floating-leaf leaf-3"></div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="register-form-container">
                <div class="form-wrapper">
                    <div class="form-header">
                        <h2>Create Account</h2>
                        <p>Fill in the details below to get started</p>
                    </div>
                    
                    <!-- Alert Messages -->
                    <div id="alert-container" class="alert-container"></div>
                    
                    <form id="registerForm" class="register-form" method="POST" action="process/register_process.php">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3 class="section-title">Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user"></i>
                                        First Name
                                    </label>
                                    <input 
                                        type="text" 
                                        id="first_name" 
                                        name="first_name" 
                                        class="form-control" 
                                        placeholder="Enter your first name"
                                        required
                                        autocomplete="given-name"
                                    >
                                    <div class="field-error" id="first_name-error"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user"></i>
                                        Last Name
                                    </label>
                                    <input 
                                        type="text" 
                                        id="last_name" 
                                        name="last_name" 
                                        class="form-control" 
                                        placeholder="Enter your last name"
                                        required
                                        autocomplete="family-name"
                                    >
                                    <div class="field-error" id="last_name-error"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-control" 
                                    placeholder="Enter your email address"
                                    required
                                    autocomplete="email"
                                >
                                <div class="field-error" id="email-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    class="form-control" 
                                    placeholder="e.g., 09123456789"
                                    required
                                    autocomplete="tel"
                                >
                                <div class="field-error" id="phone-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">
                                    <i class="fas fa-birthday-cake"></i>
                                    Date of Birth
                                </label>
                                <input 
                                    type="date" 
                                    id="date_of_birth" 
                                    name="date_of_birth" 
                                    class="form-control"
                                    required
                                    autocomplete="bday"
                                >
                                <div class="field-error" id="date_of_birth-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Address
                                </label>
                                <textarea 
                                    id="address" 
                                    name="address" 
                                    class="form-control" 
                                    placeholder="Enter your complete address"
                                    rows="3"
                                    required
                                    autocomplete="street-address"
                                ></textarea>
                                <div class="field-error" id="address-error"></div>
                            </div>
                        </div>
                        
                        <!-- Account Security -->
                        <div class="form-section">
                            <h3 class="section-title">Account Security</h3>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="password-input-container">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        class="form-control" 
                                        placeholder="Create a strong password"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="password-toggle" id="passwordToggle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-meter">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span class="strength-text" id="strengthText">Enter password</span>
                                </div>
                                <div class="field-error" id="password-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Confirm Password
                                </label>
                                <div class="password-input-container">
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        class="form-control" 
                                        placeholder="Confirm your password"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="password-toggle" id="confirmPasswordToggle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="field-error" id="confirm_password-error"></div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="terms" name="terms" value="1" required>
                                <span class="checkmark"></span>
                                <span class="checkbox-text">
                                    I agree to the 
                                    <a href="terms.php" target="_blank" class="link">Terms of Service</a> 
                                    and 
                                    <a href="privacy.php" target="_blank" class="link">Privacy Policy</a>
                                </span>
                            </label>
                            <div class="field-error" id="terms-error"></div>
                        </div>
                        
                        
                        <button type="submit" class="register-btn" id="registerBtn">
                            <span class="btn-text">Create Account</span>
                            <span class="btn-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Creating Account...
                            </span>
                        </button>
                    </form>
                    
                    <div class="form-footer">
                        <p>Already have an account? 
                            <a href="login.php" class="login-link">Sign In</a>
                        </p>
                    </div>
                </div>
                
                <!-- Quick Access Links -->
                <div class="quick-access">
                    <h4>Need Help?</h4>
                    <div class="quick-links">
                        <a href="index.php" class="quick-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                        <a href="venues.php" class="quick-link">
                            <i class="fas fa-building"></i>
                            <span>Browse Venues</span>
                        </a>
                        <a href="contact.php" class="quick-link">
                            <i class="fas fa-phone"></i>
                            <span>Contact Support</span>
                        </a>
                        <a href="faq.php" class="quick-link">
                            <i class="fas fa-question-circle"></i>
                            <span>FAQ</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Background Elements -->
        <div class="bg-decorations">
            <div class="bg-circle circle-1"></div>
            <div class="bg-circle circle-2"></div>
            <div class="bg-wave wave-1"></div>
            <div class="bg-wave wave-2"></div>
        </div>
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>
