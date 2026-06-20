<?php
// Sidebar Include - Mavic's Resort (with notification counts)

// Ensure we have a database connection
if (!isset($conn)) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $conn = getDBConnection();
    } catch (Exception $e) {
        error_log("Sidebar DB connection error: " . $e->getMessage());
        $conn = null;
    }
}

// Helper functions for notification counts
function getBookingNotificationCount($conn) {
    if (!$conn) return '';
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            return '<span class="badge">' . $count . '</span>';
        }
    } catch (PDOException $e) {
        error_log("Booking notification count error: " . $e->getMessage());
    }
    return '';
}

function getPaymentNotificationCount($conn) {
    if (!$conn) return '';
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'pending'");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            return '<span class="badge">' . $count . '</span>';
        }
    } catch (PDOException $e) {
        error_log("Payment notification count error: " . $e->getMessage());
    }
    return '';
}

function getContactNotificationCount($conn) {
    if (!$conn) return '';
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            return '<span class="badge">' . $count . '</span>';
        }
    } catch (PDOException $e) {
        error_log("Contact notification count error: " . $e->getMessage());
    }
    return '';
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="images/logo.jpg" alt="Mavics Resort Logo" class="sidebar-logo">
        <h3>Mavics Resort</h3>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : ''; ?>">
                <a href="admin-dashboard.php" class="nav-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>">
                <a href="manage-bookings.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Manage Bookings</span>
                    <?php echo getBookingNotificationCount($conn); ?>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-venues.php' ? 'active' : ''; ?>">
                <a href="manage-venues.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>Venues & Packages</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-gallery.php' ? 'active' : ''; ?>">
                <a href="manage-gallery.php" class="nav-link">
                    <i class="fas fa-images"></i>
                    <span>Gallery</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-about.php' ? 'active' : ''; ?>">
                <a href="manage-about.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>About Page</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-footer.php' ? 'active' : ''; ?>">
                <a href="manage-footer.php" class="nav-link">
                    <i class="fas fa-shoe-prints"></i>
                    <span>Footer Settings</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-contact.php' ? 'active' : ''; ?>">
                <a href="manage-contact.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                    <?php echo getContactNotificationCount($conn); ?>
                </a>
            </li>            
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>">
                <a href="manage-users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'payment-management.php' ? 'active' : ''; ?>">
                <a href="payment-management.php" class="nav-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment Management</span>
                    <?php echo getPaymentNotificationCount($conn); ?>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Add JavaScript for sidebar functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-open');
            }
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 1024;
        if (isMobile && sidebar.classList.contains('open')) {
            if (!sidebar.contains(event.target)) {
                sidebar.classList.remove('open');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-open');
                }
            }
        }
    });
    
    // Auto-update notification counts every 60 seconds
    setInterval(updateNotificationCounts, 60000);
});

function updateNotificationCounts() {
    fetch('includes/get-notification-counts.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            
            // Update booking count
            const bookingBadge = document.querySelector('.nav-item:nth-child(2) .badge');
            if (data.bookings > 0) {
                if (bookingBadge) {
                    bookingBadge.textContent = data.bookings;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge';
                    newBadge.textContent = data.bookings;
                    document.querySelector('.nav-item:nth-child(2) .nav-link').appendChild(newBadge);
                }
            } else if (bookingBadge) {
                bookingBadge.remove();
            }
            
            // Update payment count
            const paymentBadge = document.querySelector('.nav-item:nth-child(6) .badge');
            if (data.payments > 0) {
                if (paymentBadge) {
                    paymentBadge.textContent = data.payments;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge';
                    newBadge.textContent = data.payments;
                    document.querySelector('.nav-item:nth-child(6) .nav-link').appendChild(newBadge);
                }
            } else if (paymentBadge) {
                paymentBadge.remove();
            }
        })
        .catch(error => console.error('Error updating notification counts:', error));
}
</script>
