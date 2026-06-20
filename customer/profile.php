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
    
    // Fetch user information
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, phone, address, date_of_birth, 
               emergency_contact_name, emergency_contact_phone, password_hash, 
               last_login, email_verified, email_verification_token, 
               password_reset_token, password_reset_expires, status, 
               created_at, updated_at, login_attempts, locked_until, 
               two_factor_enabled, two_factor_secret
        FROM customers 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Fetch user preferences
    $prefStmt = $conn->prepare("
        SELECT * FROM user_preferences 
        WHERE customer_id = ?
    ");
    $prefStmt->execute([$userId]);
    $preferences = $prefStmt->fetch(PDO::FETCH_ASSOC);
    
    // If no preferences exist, create default ones
    if (!$preferences) {
        $insertPref = $conn->prepare("
            INSERT INTO user_preferences (customer_id) VALUES (?)
        ");
        $insertPref->execute([$userId]);
        
        // Fetch the newly created preferences
        $prefStmt->execute([$userId]);
        $preferences = $prefStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get booking statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(total_amount) as total_spent,
            MIN(booking_date) as first_booking,
            MAX(booking_date) as last_booking
        FROM bookings 
        WHERE customer_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching profile: " . $e->getMessage());
    die("Error loading profile. Please try again later.");
}

$pageTitle = "My Profile";
$pageDescription = "Manage your account settings and personal information";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/profile.css'];
$pageJSFiles = ['js/profile.js'];

include 'includes/header.php';
?>

<main id="main-content">
    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="header-content">
                <div class="profile-avatar-section">
                    <div class="avatar-wrapper">
                        <div class="avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="avatar-status online"></div>
                    </div>
                    <div class="profile-header-info">
                        <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                        <p class="member-since">
                            <i class="fas fa-calendar-alt"></i> 
                            Member since <?php echo isset($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'Recently'; ?>
                        </p>
                        <?php if (isset($user['email_verified']) && $user['email_verified']): ?>
                            <span class="verified-badge">
                                <i class="fas fa-check-circle"></i> Verified Account
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₱<?php echo number_format($stats['total_spent'] ?? 0, 0); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-layout">
                <!-- Sidebar Navigation -->
                <div class="profile-sidebar">
                    <nav class="profile-nav">
                        <a href="#personal-info" class="nav-item active" data-tab="personal-info">
                            <i class="fas fa-user"></i>
                            <span>Personal Information</span>
                        </a>
                        <a href="#security" class="nav-item" data-tab="security">
                            <i class="fas fa-lock"></i>
                            <span>Security</span>
                        </a>

                        <a href="#activity" class="nav-item" data-tab="activity">
                            <i class="fas fa-history"></i>
                            <span>Activity Log</span>
                        </a>
                    </nav>

                    <div class="quick-actions-sidebar">
                        <h3>Quick Actions</h3>
                        <a href="my-bookings.php" class="quick-action">
                            <i class="fas fa-calendar"></i> My Bookings
                        </a>
                        <a href="venues.php" class="quick-action">
                            <i class="fas fa-plus"></i> New Booking
                        </a>
                        <a href="my-messages.php" class="quick-action">
                            <i class="fas fa-envelope"></i> Messages
                        </a>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="profile-content">
                    <!-- Personal Information Tab -->
                    <div id="personal-info-tab" class="tab-content active">
                        <div class="content-header">
                            <h2><i class="fas fa-user"></i> Personal Information</h2>
                            <p>Update your personal details and contact information</p>
                        </div>

                        <form id="personal-info-form" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" 
                                           id="first_name" 
                                           name="first_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                           required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" 
                                           id="last_name" 
                                           name="last_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           required>
                                    <small class="form-text">We'll send booking confirmations to this email</small>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="+63 912 345 6789">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" 
                                          name="address" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" 
                                           id="date_of_birth" 
                                           name="date_of_birth" 
                                           class="form-control" 
                                           value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                                </div>
                            </div>

                            <div class="form-divider">
                                <h3>Emergency Contact</h3>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="emergency_contact_name">Contact Name</label>
                                    <input type="text" 
                                           id="emergency_contact_name" 
                                           name="emergency_contact_name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" 
                                           placeholder="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="emergency_contact_phone">Contact Phone</label>
                                    <input type="tel" 
                                           id="emergency_contact_phone" 
                                           name="emergency_contact_phone" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" 
                                           placeholder="+63 912 345 6789">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" onclick="resetForm('personal-info-form')">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div id="security-tab" class="tab-content">
                        <div class="content-header">
                            <h2><i class="fas fa-lock"></i> Security Settings</h2>
                            <p>Manage your password and security preferences</p>
                        </div>

                        <div class="security-overview">
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="security-info">
                                    <h4>Account Status</h4>
                                    <p>Your account is <strong class="text-success">Active and Secure</strong></p>
                                </div>
                            </div>
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="security-info">
                                    <h4>Last Login</h4>
                                    <p><?php echo (isset($user['last_login']) && $user['last_login']) ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>

                        <form id="change-password-form" class="profile-form">
                            <h3>Change Password</h3>
                            
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <div class="password-input-wrapper">
                                    <input type="password" 
                                           id="current_password" 
                                           name="current_password" 
                                           class="form-control" 
                                           required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <div class="password-input-wrapper">
                                    <input type="password" 
                                           id="new_password" 
                                           name="new_password" 
                                           class="form-control" 
                                           required 
                                           minlength="8">
                                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="password-strength">
                                    <div class="strength-bar"></div>
                                </div>
                                <small class="form-text">Password must be at least 8 characters long</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <div class="password-input-wrapper">
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           class="form-control" 
                                           required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>


                    <!-- Activity Log Tab -->
                    <div id="activity-tab" class="tab-content">
                        <div class="content-header">
                            <h2><i class="fas fa-history"></i> Activity Log</h2>
                            <p>View your recent account activity</p>
                        </div>

                        <div class="activity-list" id="activity-list">
                            <!-- Activity items will be loaded here via JavaScript -->
                            <div class="loading-state">
                                <div class="spinner"></div>
                                <p>Loading activity...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Pass user data to JavaScript
window.userData = {
    userId: <?php echo $userId; ?>,
    firstName: '<?php echo addslashes($user['first_name']); ?>',
    lastName: '<?php echo addslashes($user['last_name']); ?>',
    email: '<?php echo addslashes($user['email']); ?>'
};
</script>

<?php include 'includes/footer.php'; ?>
