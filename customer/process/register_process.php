<?php
// FILE: process/register_process.php

// Suppress all output except our JSON response
ob_start();

session_start();

// Include configuration and database
require_once '../config/database.php';

// Clean any previous output
ob_end_clean();

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
    'redirect' => null,
    'field' => null
];

try {
    // DEBUG: Log all POST data
    error_log("Registration attempt - POST data: " . print_r($_POST, true));
    
    // Get database connection
    $conn = getDBConnection();
    
    // Rate limiting check
    checkRegistrationRateLimit($conn, $_SERVER['REMOTE_ADDR']);
    
    // Get and sanitize input data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = sanitizePhoneNumber(trim($_POST['phone'] ?? ''));
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Comprehensive validation
    validateRegistrationData($first_name, $last_name, $email, $phone, $date_of_birth, $address, $password, $confirm_password, $terms);
    
    // Check if email already exists
    if (emailExists($conn, $email)) {
        $response['message'] = 'An account with this email address already exists. Please use a different email or try logging in.';
        $response['field'] = 'email';
        throw new Exception($response['message']);
    }
    
    // Check if phone number already exists (if provided)
    if (!empty($phone) && phoneExists($conn, $phone)) {
        $response['message'] = 'An account with this phone number already exists. Please use a different phone number.';
        $response['field'] = 'phone';
        throw new Exception($response['message']);
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Insert new customer (no email verification needed)
        $stmt = $conn->prepare("
            INSERT INTO customers (
                first_name, 
                last_name, 
                email, 
                phone, 
                date_of_birth,
                address,
                password_hash, 
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            !empty($phone) ? $phone : null,
            !empty($date_of_birth) ? $date_of_birth : null,
            !empty($address) ? $address : null,
            $password_hash
        ]);
        
        $customer_id = $conn->lastInsertId();
        
        // Log the registration
        logRegistrationActivity($conn, $customer_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Commit transaction
        $conn->commit();
        
        // Auto-login: Set session variables
        $_SESSION['user_id'] = $customer_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['login_time'] = time();
        
        // Prepare success response
        $response['success'] = true;
        $response['message'] = 'Account created successfully! Redirecting to your dashboard...';
        $response['redirect'] = '../login.php';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Registration error: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
exit;

/**
 * Validate registration data
 */
function validateRegistrationData($first_name, $last_name, $email, $phone, $date_of_birth, $address, $password, $confirm_password, $terms) {
    // First name validation
    if (empty($first_name)) {
        throw new Exception('First name is required.');
    }
    if (strlen($first_name) < 2) {
        throw new Exception('First name must be at least 2 characters long.');
    }
    if (strlen($first_name) > 50) {
        throw new Exception('First name is too long.');
    }
    if (!preg_match("/^[a-zA-Z\s\-'\.]+$/", $first_name)) {
        throw new Exception('First name contains invalid characters.');
    }
    
    // Last name validation
    if (empty($last_name)) {
        throw new Exception('Last name is required.');
    }
    if (strlen($last_name) < 2) {
        throw new Exception('Last name must be at least 2 characters long.');
    }
    if (strlen($last_name) > 50) {
        throw new Exception('Last name is too long.');
    }
    if (!preg_match("/^[a-zA-Z\s\-'\.]+$/", $last_name)) {
        throw new Exception('Last name contains invalid characters.');
    }
    
    // Email validation
    if (empty($email)) {
        throw new Exception('Email address is required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    if (strlen($email) > 100) {
        throw new Exception('Email address is too long.');
    }
    
    // Phone validation (required field)
    if (empty($phone)) {
        throw new Exception('Phone number is required.');
    }
    $clean_phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (!preg_match('/^(\+63|63|0)?9[0-9]{9}$/', $clean_phone)) {
        throw new Exception('Please enter a valid Philippine phone number (e.g., 09123456789).');
    }
    
    // Date of birth validation
    if (empty($date_of_birth)) {
        throw new Exception('Date of birth is required.');
    }
    $birth_date = new DateTime($date_of_birth);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
    
    if ($birth_date > $today) {
        throw new Exception('Date of birth cannot be in the future.');
    }
    if ($age < 18) {
        throw new Exception('You must be at least 18 years old to register.');
    }
    if ($age > 120) {
        throw new Exception('Please enter a valid date of birth.');
    }
    
    // Address validation
    if (empty($address)) {
        throw new Exception('Address is required.');
    }
    if (strlen($address) < 10) {
        throw new Exception('Please enter a complete address.');
    }
    if (strlen($address) > 500) {
        throw new Exception('Address is too long.');
    }
    
    // Password validation
    if (empty($password)) {
        throw new Exception('Password is required.');
    }
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }
    if (strlen($password) > 255) {
        throw new Exception('Password is too long.');
    }
    
    // Password strength validation
    $strength_score = 0;
    if (preg_match('/[a-z]/', $password)) $strength_score++;
    if (preg_match('/[A-Z]/', $password)) $strength_score++;
    if (preg_match('/[0-9]/', $password)) $strength_score++;
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $strength_score++;
    
    if ($strength_score < 3 && strlen($password) < 12) {
        throw new Exception('Password is too weak. Please include a combination of uppercase letters, lowercase letters, numbers, and symbols.');
    }
    
    // Confirm password validation
    if (empty($confirm_password)) {
        throw new Exception('Please confirm your password.');
    }
    if ($password !== $confirm_password) {
        throw new Exception('Passwords do not match.');
    }
    
    // Terms acceptance validation
    if (!$terms) {
        throw new Exception('You must agree to the Terms of Service and Privacy Policy.');
    }
}

/**
 * Check if email already exists
 */
function emailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Check if phone number already exists
 */
function phoneExists($conn, $phone) {
    if (empty($phone)) return false;
    
    $clean_phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    $stmt = $conn->prepare("
        SELECT id FROM customers 
        WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') = ?
    ");
    $stmt->execute([$clean_phone]);
    return $stmt->fetch() !== false;
}

/**
 * Log registration activity for security and analytics
 */
function logRegistrationActivity($conn, $customer_id, $ip_address, $user_agent) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO registration_logs (customer_id, ip_address, user_agent) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$customer_id, $ip_address, $user_agent]);
        
    } catch (Exception $e) {
        // Don't let logging errors affect registration process
        error_log("Failed to log registration activity: " . $e->getMessage());
    }
}

/**
 * Rate limiting for registration attempts
 */
function checkRegistrationRateLimit($conn, $ip_address) {
    try {
        $time_limit = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM registration_logs 
            WHERE ip_address = ? 
            AND registration_time > ?
        ");
        
        $stmt->execute([$ip_address, $time_limit]);
        $attempts = $stmt->fetch();
        
        // Allow maximum 5 registration attempts per hour per IP
        if ($attempts && $attempts['attempt_count'] >= 5) {
            throw new Exception('Too many registration attempts. Please try again later.');
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Too many registration attempts') !== false) {
            throw $e;
        }
        // Don't let rate limiting errors affect registration
        error_log("Rate limiting check failed: " . $e->getMessage());
    }
}

/**
 * Sanitize phone number to standard format
 */
function sanitizePhoneNumber($phone) {
    if (empty($phone)) return null;
    
    // Remove all non-numeric characters
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to standard Philippine format (09XXXXXXXXX)
    if (preg_match('/^639([0-9]{9})$/', $clean_phone, $matches)) {
        // +63 format
        return '09' . $matches[1];
    } elseif (preg_match('/^09([0-9]{9})$/', $clean_phone)) {
        // Already in correct format
        return $clean_phone;
    } elseif (preg_match('/^9([0-9]{9})$/', $clean_phone, $matches)) {
        // Missing 0
        return '09' . $matches[1];
    }
    
    return $phone; // Return original if no pattern matches
}
?>
