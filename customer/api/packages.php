<?php
// api/packages.php - Packages API endpoint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get request parameters
    $venueId = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : null;
    $packageId = isset($_GET['id']) ? intval($_GET['id']) : null;
    $eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
    $guestCount = isset($_GET['guest_count']) ? intval($_GET['guest_count']) : null;
    
    if ($packageId) {
        // Get specific package details
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                v.name as venue_name,
                v.capacity as venue_capacity,
                COUNT(DISTINCT b.id) as package_bookings
            FROM packages p
            JOIN venues v ON p.venue_id = v.id
            LEFT JOIN bookings b ON p.id = b.package_id AND b.status IN ('confirmed', 'completed')
            WHERE p.id = ? AND p.status = 'active'
            GROUP BY p.id
        ");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if ($package) {
            // Process package data
            if (!empty($package['inclusions'])) {
                $inclusions = json_decode($package['inclusions'], true);
                $package['inclusions'] = is_array($inclusions) ? $inclusions : [];
            } else {
                $package['inclusions'] = [];
            }
            
            if (!empty($package['available_days'])) {
                $availableDays = json_decode($package['available_days'], true);
                $package['available_days'] = is_array($availableDays) ? $availableDays : [];
            } else {
                $package['available_days'] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            }
            
            if (!empty($package['images'])) {
                $images = json_decode($package['images'], true);
                $package['images'] = is_array($images) ? $images : [];
            } else {
                $package['images'] = [];
            }
            
            // Ensure numeric values
            $package['price'] = floatval($package['price']);
            $package['duration_hours'] = intval($package['duration_hours']);
            $package['max_guests'] = intval($package['max_guests']);
            $package['min_guests'] = intval($package['min_guests']);
            $package['advance_notice_days'] = intval($package['advance_notice_days']);
            $package['package_bookings'] = intval($package['package_bookings']);
            
            echo json_encode([
                'success' => true,
                'package' => $package
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Package not found'
            ]);
        }
        
    } elseif ($venueId) {
        // Get packages for specific venue
        $whereConditions = ["p.venue_id = ?", "p.status = 'active'"];
        $params = [$venueId];
        
        // Apply filters
        if ($guestCount) {
            $whereConditions[] = "(p.min_guests IS NULL OR p.min_guests <= ?)";
            $whereConditions[] = "(p.max_guests IS NULL OR p.max_guests >= ?)";
            $params[] = $guestCount;
            $params[] = $guestCount;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                v.name as venue_name,
                v.capacity as venue_capacity,
                COUNT(DISTINCT b.id) as package_bookings
            FROM packages p
            JOIN venues v ON p.venue_id = v.id
            LEFT JOIN bookings b ON p.id = b.package_id AND b.status IN ('confirmed', 'completed')
            WHERE {$whereClause}
            GROUP BY p.id
            ORDER BY p.price ASC
        ");
        $stmt->execute($params);
        $packages = $stmt->fetchAll();
        
        // Process package data
        foreach ($packages as &$package) {
            if (!empty($package['inclusions'])) {
                $inclusions = json_decode($package['inclusions'], true);
                $package['inclusions'] = is_array($inclusions) ? $inclusions : [];
            } else {
                $package['inclusions'] = [];
            }
            
            if (!empty($package['available_days'])) {
                $availableDays = json_decode($package['available_days'], true);
                $package['available_days'] = is_array($availableDays) ? $availableDays : [];
            } else {
                $package['available_days'] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            }
            
            if (!empty($package['images'])) {
                $images = json_decode($package['images'], true);
                $package['images'] = is_array($images) ? $images : [];
            } else {
                $package['images'] = [];
            }
            
            // Ensure numeric values
            $package['price'] = floatval($package['price']);
            $package['duration_hours'] = intval($package['duration_hours']);
            $package['max_guests'] = intval($package['max_guests']);
            $package['min_guests'] = intval($package['min_guests']);
            $package['advance_notice_days'] = intval($package['advance_notice_days']);
            $package['package_bookings'] = intval($package['package_bookings']);
        }
        
        echo json_encode([
            'success' => true,
            'packages' => $packages,
            'count' => count($packages),
            'venue_id' => $venueId,
            'filters_applied' => [
                'guest_count' => $guestCount,
                'event_type' => $eventType
            ]
        ]);
        
    } else {
        // Get all packages (for general browsing)
        $whereConditions = ["p.status = 'active'", "v.status IN ('available', 'maintenance')"];
        $params = [];
        
        if ($guestCount) {
            $whereConditions[] = "(p.min_guests IS NULL OR p.min_guests <= ?)";
            $whereConditions[] = "(p.max_guests IS NULL OR p.max_guests >= ?)";
            $params[] = $guestCount;
            $params[] = $guestCount;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                v.name as venue_name,
                v.capacity as venue_capacity,
                v.images as venue_images,
                COUNT(DISTINCT b.id) as package_bookings
            FROM packages p
            JOIN venues v ON p.venue_id = v.id
            LEFT JOIN bookings b ON p.id = b.package_id AND b.status IN ('confirmed', 'completed')
            WHERE {$whereClause}
            GROUP BY p.id
            ORDER BY package_bookings DESC, p.price ASC
        ");
        $stmt->execute($params);
        $packages = $stmt->fetchAll();
        
        // Process package data
        foreach ($packages as &$package) {
            if (!empty($package['inclusions'])) {
                $inclusions = json_decode($package['inclusions'], true);
                $package['inclusions'] = is_array($inclusions) ? $inclusions : [];
            } else {
                $package['inclusions'] = [];
            }
            
            if (!empty($package['available_days'])) {
                $availableDays = json_decode($package['available_days'], true);
                $package['available_days'] = is_array($availableDays) ? $availableDays : [];
            } else {
                $package['available_days'] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            }
            
            if (!empty($package['images'])) {
                $images = json_decode($package['images'], true);
                $package['images'] = is_array($images) ? $images : [];
            } else {
                $package['images'] = [];
            }
            
            // Process venue images for fallback
            if (!empty($package['venue_images'])) {
                $venueImages = json_decode($package['venue_images'], true);
                $package['venue_images'] = is_array($venueImages) ? $venueImages : [];
            } else {
                $package['venue_images'] = [];
            }
            
            // Ensure numeric values
            $package['price'] = floatval($package['price']);
            $package['duration_hours'] = intval($package['duration_hours']);
            $package['max_guests'] = intval($package['max_guests']);
            $package['min_guests'] = intval($package['min_guests']);
            $package['advance_notice_days'] = intval($package['advance_notice_days']);
            $package['package_bookings'] = intval($package['package_bookings']);
        }
        
        echo json_encode([
            'success' => true,
            'packages' => $packages,
            'count' => count($packages),
            'filters_applied' => [
                'guest_count' => $guestCount,
                'event_type' => $eventType
            ]
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in packages API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in packages API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
}
?>