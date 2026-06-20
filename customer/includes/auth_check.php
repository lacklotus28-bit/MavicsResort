<?php
// FILE: customer/includes/auth_check.php
?>
<?php
// Check if user is logged in, redirect if not
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please log in to access this page.';
    $_SESSION['message_type'] = 'warning';
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Verify user exists in database
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, status FROM customers WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found or inactive, clear session
        session_destroy();
        $_SESSION['message'] = 'Your account is no longer active. Please contact support.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
    
    // Update session with current user data
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    
} catch (PDOException $e) {
    error_log("Auth check error: " . $e->getMessage());
    $_SESSION['message'] = 'A system error occurred. Please try again.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}
?>