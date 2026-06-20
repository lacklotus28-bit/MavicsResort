<?php
// FILE: process/login_process.php
session_start();

// Include configuration and database
require_once '../config/database.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'user' => null,
    'redirect' => null
];

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Validate input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email)) {
        throw new Exception('Email address is required.');
    }
    
    if (empty($password)) {
        throw new Exception('Password is required.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    
    // Rate limiting check (simple implementation)
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > ? 
        AND success = 0
    ");
    
    if ($stmt) {
        $stmt->execute([$ip_address, $time_limit]);
        $attempts = $stmt->fetch();
        
        if ($attempts['attempt_count'] >= 5) {
            throw new Exception('Too many failed login attempts. Please try again in 15 minutes.');
        }
    }
    
    // Find user by email
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, password_hash, status 
        FROM customers 
        WHERE email = ? AND status = 'active'
    ");
    
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Check if user exists and verify password
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Log failed attempt
        logLoginAttempt($conn, $ip_address, $email, false);
        
        throw new Exception('Invalid email or password.');
    }
    
    // Login successful - log attempt
    logLoginAttempt($conn, $ip_address, $email, true);
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['login_time'] = time();
    
    // Update user's last login
    $stmt = $conn->prepare("
        UPDATE customers 
        SET last_login = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Prepare response
    $response['success'] = true;
    $response['message'] = 'Login successful!';
    $response['user'] = [
        'id' => $user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ];
    
    // Determine redirect URL
    if (isset($_SESSION['redirect_url'])) {
        $response['redirect'] = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
    } else {
        $response['redirect'] = 'dashboard.php';
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // Log error for debugging (but not user passwords)
    error_log("Login error for email {$email}: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response);

/**
 * Log login attempts for security monitoring
 */
function logLoginAttempt($conn, $ip_address, $email, $success) {
    try {
        // Create login_attempts table if it doesn't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(255) NOT NULL,
                success TINYINT(1) DEFAULT 0,
                attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_time (ip_address, attempt_time),
                INDEX idx_email_time (email, attempt_time)
            )
        ");
        
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (ip_address, email, success) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip_address, $email, $success ? 1 : 0]);
        
        // Clean up old attempts (older than 24 hours)
        $conn->exec("
            DELETE FROM login_attempts 
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
    } catch (Exception $e) {
        // Don't let logging errors affect login process
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

/**
 * Create user_sessions table if it doesn't exist
 */
function createUserSessionsTable($conn) {
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                remember_token VARCHAR(64) UNIQUE,
                ip_address VARCHAR(45),
                user_agent TEXT,
                expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE,
                INDEX idx_token (remember_token),
                INDEX idx_user_expires (user_id, expires_at)
            )
        ");
    } catch (Exception $e) {
        error_log("Failed to create user_sessions table: " . $e->getMessage());
    }
}

// Ensure tables exist
createUserSessionsTable($conn);
?>