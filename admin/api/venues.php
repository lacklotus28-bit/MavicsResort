<?php
// API endpoint for venue management - api/venues.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session and check authentication
session_start();
$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin = $auth->getCurrentAdmin();
$conn = getDBConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetVenue($conn);
            break;
            
        case 'POST':
            handleCreateOrUpdateVenue($conn, $admin);
            break;
            
        case 'DELETE':
            handleDeleteVenue($conn, $admin);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Venues API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetVenue($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Venue ID required']);
        return;
    }
    
    $venue_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$venue_id]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venue) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Venue not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'data' => $venue]);
}

function handleCreateOrUpdateVenue($conn, $admin) {
    // Handle JSON requests (for duplicate action)
    $json_input = file_get_contents('php://input');
    if (!empty($json_input)) {
        $data = json_decode($json_input, true);
        
        if (isset($data['action']) && $data['action'] === 'duplicate') {
            handleDuplicateVenue($conn, $admin, $data['id']);
            return;
        }
    }
    
    // Handle form data for create/update
    $venue_id = $_POST['venue_id'] ?? null;
    $is_update = !empty($venue_id);
    
    // Validate required fields
    $required_fields = ['name', 'capacity', 'price_per_hour'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            return;
        }
    }
    
    // Sanitize and validate data
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $status = $_POST['status'] ?? 'available';
    $description = trim($_POST['description'] ?? '');
    
    // Validate capacity and price
    if ($capacity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Capacity must be greater than 0']);
        return;
    }
    
    if ($price_per_hour < 0) {
        echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
        return;
    }
    
    // Handle amenities
    $amenities = [];
    if (!empty($_POST['amenities'])) {
        $amenities_data = json_decode($_POST['amenities'], true);
        if (is_array($amenities_data)) {
            $amenities = $amenities_data;
        }
    }
    $amenities_json = json_encode($amenities);
    
    // Handle image uploads
    $existing_images = [];
    if ($is_update) {
        // Get existing images
        $stmt = $conn->prepare("SELECT images FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($venue && $venue['images']) {
            $existing_images = json_decode($venue['images'], true) ?: [];
        }
    }
    
    $uploaded_images = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploaded_images = handleImageUploads($_FILES['images']);
    }
    
    // Combine existing and new images
    $all_images = array_merge($existing_images, $uploaded_images);
    $images_json = json_encode($all_images);
    
    try {
        $conn->beginTransaction();
        
        if ($is_update) {
            // Update venue
            $stmt = $conn->prepare("
                UPDATE venues 
                SET name = ?, description = ?, capacity = ?, price_per_hour = ?, 
                    status = ?, amenities = ?, images = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $capacity, $price_per_hour, $status, 
                           $amenities_json, $images_json, $venue_id]);
            
            logActivity($conn, $admin['id'], 'update_venue', 'venues', $venue_id);
            $message = 'Venue updated successfully!';
            
        } else {
            // Create new venue
            $stmt = $conn->prepare("
                INSERT INTO venues (name, description, capacity, price_per_hour, status, amenities, images)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $capacity, $price_per_hour, $status, 
                           $amenities_json, $images_json]);
            
            $venue_id = $conn->lastInsertId();
            logActivity($conn, $admin['id'], 'create_venue', 'venues', $venue_id);
            $message = 'Venue created successfully!';
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message, 'venue_id' => $venue_id]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Venue save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to save venue']);
    }
}

function handleDeleteVenue($conn, $admin) {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Venue ID required']);
        return;
    }
    
    $venue_id = intval($data['id']);
    
    try {
        $conn->beginTransaction();
        
        // Check if venue exists
        $stmt = $conn->prepare("SELECT name, images FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venue) {
            $conn->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Venue not found']);
            return;
        }
        
        // Check for existing bookings
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE venue_id = ? AND status IN ('confirmed', 'pending')");
        $stmt->execute([$venue_id]);
        $booking_count = $stmt->fetch(PDO::FETCH_ASSOC)['booking_count'];
        
        if ($booking_count > 0) {
            $conn->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot delete venue with active bookings. Please cancel or complete existing bookings first.'
            ]);
            return;
        }
        
        // Delete related records first (due to foreign key constraints)
        
        // Delete booking notes for this venue's bookings
        $stmt = $conn->prepare("DELETE bn FROM booking_notes bn INNER JOIN bookings b ON bn.booking_id = b.id WHERE b.venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete payments for this venue's bookings
        $stmt = $conn->prepare("DELETE p FROM payments p INNER JOIN bookings b ON p.booking_id = b.id WHERE b.venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete all bookings for this venue (including completed/cancelled ones)
        $stmt = $conn->prepare("DELETE FROM bookings WHERE venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete venue blocks
        $stmt = $conn->prepare("DELETE FROM venue_blocks WHERE venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete packages
        $stmt = $conn->prepare("DELETE FROM packages WHERE venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete payment terms
        $stmt = $conn->prepare("DELETE FROM payment_terms WHERE venue_id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete the venue itself
        $stmt = $conn->prepare("DELETE FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        
        // Delete associated images from filesystem
        if ($venue['images']) {
            $images = json_decode($venue['images'], true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    $image_path = __DIR__ . "/../images/venues/" . $image;
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
        }
        
        // Log the deletion
        logActivity($conn, $admin['id'], 'delete_venue', 'venues', $venue_id, $venue);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Venue deleted successfully!']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Venue deletion error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete venue: ' . $e->getMessage()]);
    }
}

function handleDuplicateVenue($conn, $admin, $venue_id) {
    try {
        $conn->beginTransaction();
        
        // Get original venue
        $stmt = $conn->prepare("SELECT * FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$original) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Original venue not found']);
            return;
        }
        
        // Create duplicate with modified name
        $new_name = $original['name'] . ' (Copy)';
        
        $stmt = $conn->prepare("
            INSERT INTO venues (name, description, capacity, price_per_hour, status, amenities, images)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $new_name,
            $original['description'],
            $original['capacity'],
            $original['price_per_hour'],
            'available', // Reset status to available
            $original['amenities'],
            $original['images']
        ]);
        
        $new_venue_id = $conn->lastInsertId();
        
        // Duplicate packages if any exist
        $stmt = $conn->prepare("SELECT * FROM packages WHERE venue_id = ? AND status = 'active'");
        $stmt->execute([$venue_id]);
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($packages as $package) {
            $stmt = $conn->prepare("
                INSERT INTO packages (venue_id, name, description, price, duration_hours, max_guests, inclusions, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $new_venue_id,
                $package['name'] . ' (Copy)',
                $package['description'],
                $package['price'],
                $package['duration_hours'],
                $package['max_guests'],
                $package['inclusions'],
                'active'
            ]);
        }
        
        logActivity($conn, $admin['id'], 'duplicate_venue', 'venues', $new_venue_id, null, $original);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Venue duplicated successfully!']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Venue duplication error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to duplicate venue']);
    }
}

function handleImageUploads($files) {
    $uploaded_images = [];
    $upload_dir = __DIR__ . '/../images/venues/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $file_name = $files['name'][$i];
        $file_type = $files['type'][$i];
        $file_size = $files['size'][$i];
        $temp_file = $files['tmp_name'][$i];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }
        
        // Validate file size
        if ($file_size > $max_size) {
            continue;
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = 'venue_' . uniqid() . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($temp_file, $destination)) {
            $uploaded_images[] = $new_filename;
        }
    }
    
    return $uploaded_images;
}

function logActivity($conn, $admin_id, $action, $table, $record_id, $old_values = null, $new_values = null) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $old_values_json = $old_values ? json_encode($old_values) : null;
        $new_values_json = $new_values ? json_encode($new_values) : null;
        
        $stmt->execute([
            $admin_id, $action, $table, $record_id, 
            $old_values_json, $new_values_json, $ip_address, $user_agent
        ]);
        
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}
?>