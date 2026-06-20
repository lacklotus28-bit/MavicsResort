<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to send a message. Please login first.']);
    exit;
}

// Validate and sanitize inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Get IP address and user agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    // Insert contact message
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $email, $phone, $subject, $message, $ip_address, $user_agent]);
    
    // Check if auto-reply is enabled
    $stmt = $pdo->query("SELECT setting_value FROM contact_page_settings WHERE setting_key = 'auto_reply_enabled'");
    $autoReplyEnabled = $stmt->fetchColumn();
    
    if ($autoReplyEnabled == '1') {
        // Get auto-reply message
        $stmt = $pdo->query("SELECT setting_value FROM contact_page_settings WHERE setting_key = 'auto_reply_message'");
        $autoReplyMessage = $stmt->fetchColumn();
        
        // Send auto-reply email (optional - requires mail configuration)
        // mail($email, 'Thank you for contacting us', $autoReplyMessage, 'From: ' . ADMIN_EMAIL);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
    
} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while sending your message. Please try again later.'
    ]);
}
