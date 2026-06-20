<?php
// Page configuration - Set BEFORE including header
$pageTitle = "Login - Access Your Account";
$pageDescription = "Login to your Mavic's Resort account to manage bookings and access exclusive features.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/login.css'];
$pageJSFiles = ['js/login.js'];

// Include header
include 'includes/header.php';

// Check if user is already logged in
if ($isLoggedIn) {
    header('Location: index.php');
    exit;
}
?>

<!-- Main Content -->
<main id="main-content">
    <section class="login-section">
        <div class="login-container">
            <!-- Left Side - Brand & Info -->
            <div class="login-brand">
                <div class="brand-content">
                    <div class="brand-logo">
                        <img src="images/bg1.jpg" alt="Mavic's Resort">
                        <h1>Mavic's Resort</h1>
                    </div>
                    <div class="brand-text">
                        <h2>Welcome Back</h2>
                        <p>Access your account to manage bookings, view your event history, and discover exclusive offers.</p>
                        
                        <div class="features-list">
                            <div class="feature-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Manage Your Bookings</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-clock"></i>
                                <span>Real-time Availability</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-user-shield"></i>
                                <span>Secure Account</span>
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
            
            <!-- Right Side - Login Form -->
            <div class="login-form-container">
                <div class="form-wrapper">
                    <div class="form-header">
                        <h2>Sign In</h2>
                        <p>Enter your credentials to access your account</p>
                    </div>
                    
                    <!-- Alert Messages -->
                    <div id="alert-container" class="alert-container"></div>
                    
                    <form id="loginForm" class="login-form" method="POST" action="process/login_process.php">
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
                                placeholder="Enter your email"
                                required
                                autocomplete="email"
                            >
                            <div class="field-error" id="email-error"></div>
                        </div>
                        
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
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="password-toggle" id="passwordToggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="field-error" id="password-error"></div>
                        </div>
                        
                        <button type="submit" class="login-btn" id="loginBtn">
                            <span class="btn-text">Sign In</span>
                            <span class="btn-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Signing In...
                            </span>
                        </button>
                    </form>
                    
                    <div class="form-footer">
                        <p>Don't have an account? 
                            <a href="register.php" class="signup-link">Create Account</a>
                        </p>
                    </div>
                </div>
                
                <!-- Quick Access Links -->
                <div class="quick-access">
                    <h4>Quick Access</h4>
                    <div class="quick-links">
                        <a href="index.php" class="quick-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                        <a href="venues.php" class="quick-link">
                            <i class="fas fa-building"></i>
                            <span>Venues</span>
                        </a>
                        <a href="contact.php" class="quick-link">
                            <i class="fas fa-phone"></i>
                            <span>Contact</span>
                        </a>
                        <a href="about.php" class="quick-link">
                            <i class="fas fa-info-circle"></i>
                            <span>About</span>
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
