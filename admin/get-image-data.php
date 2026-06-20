<?php
// get-image-data.php - Fetch image data for editing
require_once 'config/database.php';
require_once 'classes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check authentication
if (!$auth->isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
    exit;
}

$image_id = (int)$_GET['id'];

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM gallery_images WHERE id = :id");
    $stmt->execute([':id' => $image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'image' => $image
    ]);
    
} catch(PDOException $e) {
    error_log("Get image data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>