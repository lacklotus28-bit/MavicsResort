<?php
// Get Gallery Images API - Mavic's Resort
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get limit parameter
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    // Build query
    $where_clause = $category !== 'all' ? "WHERE category = :category" : "";
    
    $sql = "SELECT id, title, description, image_path as image, category, tags, alt_text, is_featured, created_at 
            FROM gallery_images 
            {$where_clause}
            ORDER BY is_featured DESC, created_at DESC 
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    
    if ($category !== 'all') {
        $stmt->bindParam(':category', $category);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format image paths for frontend
    foreach ($images as &$image) {
        // Convert relative path to full path from customer perspective
        $image['image'] = '../admin/uploads/gallery/' . $image['image'];
        $image['thumbnail'] = $image['image']; // Can add thumbnail logic later
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);
    
} catch (PDOException $e) {
    error_log("Gallery API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'images' => []
    ]);
}
?>
