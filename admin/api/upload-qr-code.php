<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$adminId = $_SESSION['admin_id'];

// Check if file was uploaded
if (!isset($_FILES['qr_code']) || $_FILES['qr_code']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['qr_code'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
    exit();
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
    exit();
}

try {
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/payment/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'gcash_qr_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit();
    }
    
    // Return relative path for database storage
    $relativePath = 'uploads/payment/' . $filename;
    
    // Log activity
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, ip_address, user_agent)
        VALUES (?, 'qr_code_upload', 'payment_settings', ?, ?)
    ");
    $stmt->execute([
        $adminId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'QR code uploaded successfully',
        'file_path' => $relativePath,
        'filename' => $filename
    ]);
    
} catch (Exception $e) {
    error_log("Error uploading QR code: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to upload QR code']);
}
?>
