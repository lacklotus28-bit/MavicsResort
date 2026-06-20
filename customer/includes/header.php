<?php
// Check if session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$conn = getDBConnection();

// Check if user is logged in and get user data from database
$isLoggedIn = false;
$userData = null;
$unreadNotificationsCount = 0;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, status FROM customers WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $headerUser = $stmt->fetch();
        
        if ($headerUser) {
            $isLoggedIn = true;
            $userData = $headerUser;
            // Update session with fresh data
            $_SESSION['first_name'] = $headerUser['first_name'];
            $_SESSION['last_name'] = $headerUser['last_name'];
            $_SESSION['email'] = $headerUser['email'];
            
            // Get counts for badge indicators
            try {
                // Count pending bookings
                $stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM bookings WHERE customer_id = ? AND status = 'pending'");
                $stmt->execute([$headerUser['id']]);
                $pendingBookings = $stmt->fetch()['pending_count'];
                
                // Count unpaid/partial payments
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as unpaid_count 
                    FROM bookings 
                    WHERE customer_id = ? AND payment_status IN ('unpaid', 'partial') AND status != 'cancelled'
                ");
                $stmt->execute([$headerUser['id']]);
                $unpaidBookings = $stmt->fetch()['unpaid_count'];
                
                // Count unread messages
                $unreadMessages = 0;
                $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE customer_id = ? AND status = 'unread'");
                $stmt->execute([$headerUser['id']]);
                $result = $stmt->fetch();
                if ($result) {
                    $unreadMessages = $result['unread_count'];
                }
                
                // Separate counts for different notifications
                $bookingNotifications = $pendingBookings + $unpaidBookings;
                $unreadNotificationsCount = $bookingNotifications + $unreadMessages;
            } catch (PDOException $e) {
                error_log("Count query error: " . $e->getMessage());
            }
            
        } else {
            // User not found or inactive, clear session
            session_destroy();
            session_start();
        }
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        // Don't destroy session on DB error, just continue without user data
    }
}

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Mavic's Resort</title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Mavic\'s Resort - Premium venue booking for your special events'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="styles/global.css">
    
    <!-- Page-specific CSS -->
    <?php if(isset($pageCSSFiles) && is_array($pageCSSFiles)): ?>
        <?php foreach($pageCSSFiles as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $isLoggedIn ? 'user-logged-in' : 'user-not-logged-in'; ?>">
    <!-- Header -->
    <header id="main-header">        
        <!-- Main Navigation -->
        <nav class="main-nav">
            <div class="container">
                <div class="nav-content">
                    <!-- Logo -->
                    <div class="logo">
                        <a href="index.php">
                            <img src="images/bg1.jpg" alt="Mavic's Resort">
                            <span class="logo-text">Mavic's Resort</span>
                        </a>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <ul class="nav-menu">
                        <li><a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'current-page' : ''; ?>">Home</a></li>
                        <li><a href="venues.php" class="nav-link <?php echo ($currentPage == 'venues.php') ? 'current-page' : ''; ?>">Venues & Packages</a></li>
                        <li><a href="gallery.php" class="nav-link <?php echo ($currentPage == 'gallery.php') ? 'current-page' : ''; ?>">Gallery</a></li>
                        <li><a href="about.php" class="nav-link <?php echo ($currentPage == 'about.php') ? 'current-page' : ''; ?>">About Us</a></li>
                        <li><a href="contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'current-page' : ''; ?>">Contact</a></li>
                        
                        <?php if ($isLoggedIn && $userData): ?>
                            <!-- Logged in user menu -->
                            <li class="dropdown login-required">
                                <a href="#" class="nav-link dropdown-toggle <?php echo (in_array($currentPage, ['dashboard.php', 'my-bookings.php', 'profile.php', 'billing.php', 'payment-methods.php'])) ? 'current-page' : ''; ?>">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($userData['first_name']); ?>
                                    <?php if ($unreadNotificationsCount > 0): ?>
                                        <span class="notification-badge"><?php echo $unreadNotificationsCount; ?></span>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a></li>
                                    <li><a href="my-bookings.php" class="<?php echo ($currentPage == 'my-bookings.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-alt"></i> My Bookings
                                        <?php if ($unreadNotificationsCount > 0): ?>
                                            <span class="menu-badge"><?php echo $unreadNotificationsCount; ?></span>
                                        <?php endif; ?>
                                    </a></li>
                                    <li><a href="booking.php" class="<?php echo ($currentPage == 'booking.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a></li>
                                    <li><a href="my-messages.php" class="<?php echo ($currentPage == 'my-messages.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-envelope"></i> Messages
                                        <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                                            <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                                        <?php endif; ?>
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a href="profile.php" class="<?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-user-edit"></i> My Profile
                                    </a></li>
                                    <li><a href="billing.php" class="<?php echo ($currentPage == 'billing.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-credit-card"></i> Billing History
                                    </a></li>
                                    <li><a href="payment-methods.php" class="<?php echo ($currentPage == 'payment-methods.php') ? 'active' : ''; ?>">
                                        <i class="fas fa-wallet"></i> Payment Methods
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a href="logout.php" class="logout-link">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Guest user menu -->
                            <li class="guest-only">
                                <a href="login.php" class="btn btn-outline <?php echo ($currentPage == 'login.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </li>
                            <li class="guest-only">
                                <a href="register.php" class="btn btn-primary <?php echo ($currentPage == 'register.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-user-plus"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Mobile Menu Toggle -->
                    <div class="mobile-menu-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <div class="mobile-nav-content">
                <ul class="mobile-nav-menu">
                    <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'current-page' : ''; ?>">Home</a></li>
                    <li><a href="venues.php" class="<?php echo ($currentPage == 'venues.php') ? 'current-page' : ''; ?>">Venues & Packages</a></li>
                    <li><a href="gallery.php" class="<?php echo ($currentPage == 'gallery.php') ? 'current-page' : ''; ?>">Gallery</a></li>
                    <li><a href="about.php" class="<?php echo ($currentPage == 'about.php') ? 'current-page' : ''; ?>">About Us</a></li>
                    <li><a href="contact.php" class="<?php echo ($currentPage == 'contact.php') ? 'current-page' : ''; ?>">Contact</a></li>
                    
                    <?php if ($isLoggedIn && $userData): ?>
                        <li class="mobile-user-menu">
                            <div class="mobile-user-info">
                                <i class="fas fa-user"></i>
                                <span>Welcome, <?php echo htmlspecialchars($userData['first_name']); ?></span>
                                <?php if ($unreadNotificationsCount > 0): ?>
                                    <span class="notification-badge mobile"><?php echo $unreadNotificationsCount; ?></span>
                                <?php endif; ?>
                            </div>
                            <ul class="mobile-submenu">
                                <li><a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><a href="my-bookings.php" class="<?php echo ($currentPage == 'my-bookings.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-calendar-alt"></i> My Bookings
                                    <?php if ($unreadNotificationsCount > 0): ?>
                                        <span class="menu-badge"><?php echo $unreadNotificationsCount; ?></span>
                                    <?php endif; ?>
                                </a></li>
                                <li><a href="booking.php" class="<?php echo ($currentPage == 'booking.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-calendar-plus"></i> Book Now
                                </a></li>
                                <li><a href="my-messages.php" class="<?php echo ($currentPage == 'my-messages.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-envelope"></i> Messages
                                    <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                                        <span class="menu-badge"><?php echo $unreadMessages; ?></span>
                                    <?php endif; ?>
                                </a></li>
                                <li><a href="profile.php" class="<?php echo ($currentPage == 'profile.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-user-edit"></i> My Profile
                                </a></li>
                                <li><a href="billing.php" class="<?php echo ($currentPage == 'billing.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-credit-card"></i> Billing History
                                </a></li>
                                <li><a href="payment-methods.php" class="<?php echo ($currentPage == 'payment-methods.php') ? 'current-page' : ''; ?>">
                                    <i class="fas fa-wallet"></i> Payment Methods
                                </a></li>
                                <li><a href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php" class="mobile-auth-link <?php echo ($currentPage == 'login.php') ? 'current-page' : ''; ?>">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a></li>
                        <li><a href="register.php" class="mobile-auth-link <?php echo ($currentPage == 'register.php') ? 'current-page' : ''; ?>">
                            <i class="fas fa-user-plus"></i> Register
                        </a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Header script initialized');
    console.log('User logged in:', <?php echo $isLoggedIn ? 'true' : 'false'; ?>);
    
    // Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const body = document.body;
    
    if (mobileToggle && mobileNav) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            mobileToggle.classList.toggle('active');
            mobileNav.classList.toggle('active');
            body.classList.toggle('mobile-menu-open');
        });
        
        // Close mobile menu when clicking on links
        const mobileLinks = document.querySelectorAll('.mobile-nav-menu a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileToggle.classList.remove('active');
                mobileNav.classList.remove('active');
                body.classList.remove('mobile-menu-open');
            });
        });
    }
    
    // Desktop Dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown');
    console.log('Found dropdowns:', dropdowns.length);
    console.log('Dropdown elements:', dropdowns);
    
    dropdowns.forEach((dropdown, index) => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        console.log(`Dropdown ${index}:`, {
            dropdown: dropdown,
            toggle: toggle,
            menu: menu,
            menuItems: menu ? menu.querySelectorAll('li').length : 0
        });
        
        if (toggle && menu) {
            console.log('Setting up dropdown', index);
            
            // Click event for dropdown toggle
            toggle.addEventListener('click', function(e) {
                console.log('Dropdown toggle clicked!');
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns first
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                const wasActive = dropdown.classList.contains('active');
                dropdown.classList.toggle('active');
                console.log('Dropdown active:', dropdown.classList.contains('active'));
                console.log('Menu display:', window.getComputedStyle(menu).display);
                console.log('Menu visibility:', window.getComputedStyle(menu).visibility);
                console.log('Menu opacity:', window.getComputedStyle(menu).opacity);
            });
        } else {
            console.warn('Dropdown', index, 'missing toggle or menu');
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const clickedInsideDropdown = e.target.closest('.dropdown');
        if (!clickedInsideDropdown) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Close dropdowns on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Navbar scroll effect
    let lastScrollTop = 0;
    let ticking = false;
    
    function updateHeader() {
        const header = document.getElementById('main-header');
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            header.classList.add('header-hidden');
        } else {
            header.classList.remove('header-hidden');
        }
        
        if (scrollTop > 50) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }
        
        lastScrollTop = scrollTop;
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    });
});
</script>
