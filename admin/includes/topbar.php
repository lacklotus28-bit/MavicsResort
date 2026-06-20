<?php
// Topbar Include - Mavic's Resort
// Always fetch fresh admin data from database to get profile photo
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If database fetch fails, use session data as fallback
    if (!$admin) {
        $admin = [
            'id' => $_SESSION['admin_id'] ?? 0,
            'full_name' => $_SESSION['admin_name'] ?? 'Admin',
            'email' => $_SESSION['admin_email'] ?? 'admin@mavicsresort.com',
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'profile_photo' => null
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching admin data: " . $e->getMessage());
    $admin = [
        'id' => $_SESSION['admin_id'] ?? 0,
        'full_name' => $_SESSION['admin_name'] ?? 'Admin',
        'email' => $_SESSION['admin_email'] ?? 'admin@mavicsresort.com',
        'role' => $_SESSION['admin_role'] ?? 'admin',
        'profile_photo' => null
    ];
}

// Helper function for initials
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}
?>
<!-- Main Header -->
<header class="main-header">
    <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h1><?php echo $page_title ?? 'Dashboard Overview'; ?></h1>
    </div>
    
    <div class="header-right">
        <!-- Notifications Dropdown -->
        <div class="notifications">
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <?php 
                $pending_count = getPendingNotificationsCount($conn);
                if ($pending_count > 0): ?>
                    <span class="notification-count"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h4>Notifications</h4>
                    <span class="notifications-count"><?php echo $pending_count; ?> Unread</span>
                </div>
                <div class="notifications-list">
                    <?php echo getNotificationsList($conn); ?>
                </div>
            </div>
        </div>
        
        <!-- Admin Profile Dropdown -->
        <div class="admin-profile-dropdown">
            <button class="profile-btn" id="profileBtn">
                <div class="admin-avatar-sm">
                    <?php if (!empty($admin['profile_photo']) && file_exists($admin['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($admin['profile_photo']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?php echo getInitials($admin['full_name']); ?>
                    <?php endif; ?>
                </div>
                <div class="admin-info-sm">
                    <span class="admin-name"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                    <span class="admin-role"><?php echo ucfirst($admin['role']); ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="profile-dropdown-menu" id="profileDropdown">
                <div class="dropdown-header">
                    <div class="admin-avatar-md">
                        <?php if (!empty($admin['profile_photo']) && file_exists($admin['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($admin['profile_photo']); ?>" alt="Profile Photo">
                        <?php else: ?>
                            <?php echo getInitials($admin['full_name']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="admin-info-md">
                        <span class="admin-name"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                        <span class="admin-email"><?php echo htmlspecialchars($admin['email']); ?></span>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profile-settings.php" class="dropdown-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile Settings</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item logout-btn" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>
