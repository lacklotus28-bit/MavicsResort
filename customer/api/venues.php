<?php
// api/venues.php - Enhanced venues API with filtering and detailed information

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

// Function to create correct image path
function getCorrectImagePath($imageName) {
    if (empty($imageName)) {
        return '../admin/images/venues/default.jpg';
    }
    
    $imageName = trim($imageName);
    
    // If it already has a protocol, return as is
    if (strpos($imageName, 'http') === 0) {
        return $imageName;
    }
    
    // If it already includes admin/ path, return as is
    if (strpos($imageName, 'admin/') === 0) {
        return '../' . $imageName;
    }
    
    // Default path
    return '../admin/images/venues/' . $imageName;
}

try {
    $conn = getDBConnection();
    
    // Get request parameters
    $venueId = isset($_GET['id']) ? intval($_GET['id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
    $capacity = isset($_GET['capacity']) ? $_GET['capacity'] : '';
    $priceRange = isset($_GET['price_range']) ? $_GET['price_range'] : '';
    
    if ($venueId) {
        // Get specific venue with full details
        $stmt = $conn->prepare("
            SELECT 
                v.*,
                COUNT(DISTINCT b.id) as total_bookings
            FROM venues v 
            LEFT JOIN bookings b ON v.id = b.venue_id AND b.status IN ('confirmed', 'completed')
            WHERE v.id = ? AND v.status IN ('available', 'maintenance')
            GROUP BY v.id
        ");
        $stmt->execute([$venueId]);
        $venue = $stmt->fetch();
        
        if ($venue) {
            // Process images
            if (!empty($venue['images'])) {
                $images = json_decode($venue['images'], true);
                if (is_array($images)) {
                    $venue['images'] = array_map('getCorrectImagePath', $images);
                    $venue['featured_image'] = $venue['images'][0];
                } else {
                    $venue['images'] = [getCorrectImagePath($venue['images'])];
                    $venue['featured_image'] = $venue['images'][0];
                }
            } else {
                $venue['images'] = [getCorrectImagePath('default.jpg')];
                $venue['featured_image'] = $venue['images'][0];
            }
            
            // Process amenities
            if (!empty($venue['amenities'])) {
                $amenities = json_decode($venue['amenities'], true);
                $venue['amenities'] = is_array($amenities) ? $amenities : [];
            } else {
                $venue['amenities'] = [];
            }
            
            // Get packages for this venue
            $packageStmt = $conn->prepare("
                SELECT 
                    p.*,
                    COUNT(DISTINCT b.id) as package_bookings
                FROM packages p
                LEFT JOIN bookings b ON p.id = b.package_id
                WHERE p.venue_id = ? AND p.status = 'active' 
                GROUP BY p.id
                ORDER BY p.price ASC
            ");
            $packageStmt->execute([$venueId]);
            $packages = $packageStmt->fetchAll();
            
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
                    $package['available_days'] = [];
                }
                
                // Ensure numeric values
                $package['price'] = floatval($package['price']);
                $package['duration_hours'] = intval($package['duration_hours']);
                $package['max_guests'] = intval($package['max_guests']);
                $package['min_guests'] = intval($package['min_guests']);
                $package['package_bookings'] = intval($package['package_bookings']);
            }
            
            $venue['packages'] = $packages;
            
            // Get recent availability data (simplified)
            $availabilityStmt = $conn->prepare("
                SELECT booking_date 
                FROM bookings 
                WHERE venue_id = ? 
                AND booking_date >= CURRENT_DATE() 
                AND status IN ('pending', 'confirmed')
                ORDER BY booking_date ASC
                LIMIT 30
            ");
            $availabilityStmt->execute([$venueId]);
            $bookedDates = $availabilityStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $venue['booked_dates'] = $bookedDates;
            
            // Ensure numeric values
            $venue['capacity'] = intval($venue['capacity']);
            $venue['price_per_hour'] = floatval($venue['price_per_hour']);
            $venue['total_bookings'] = intval($venue['total_bookings']);
            
            echo json_encode([
                'success' => true,
                'venue' => $venue
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Venue not found'
            ]);
        }
    } else {
        // Get all available venues with filtering
        $whereConditions = ["v.status IN ('available', 'maintenance')"];
        $params = [];
        
        // Apply filters
        if (!empty($capacity)) {
            if (strpos($capacity, '-') !== false) {
                $capacityRange = explode('-', $capacity);
                $minCapacity = intval($capacityRange[0]);
                $maxCapacity = isset($capacityRange[1]) ? intval($capacityRange[1]) : null;
                
                if ($maxCapacity) {
                    $whereConditions[] = "v.capacity BETWEEN ? AND ?";
                    $params[] = $minCapacity;
                    $params[] = $maxCapacity;
                } else {
                    $whereConditions[] = "v.capacity >= ?";
                    $params[] = $minCapacity;
                }
            } elseif (strpos($capacity, '+') !== false) {
                $minCapacity = intval(str_replace('+', '', $capacity));
                $whereConditions[] = "v.capacity >= ?";
                $params[] = $minCapacity;
            }
        }
        
        if (!empty($priceRange)) {
            if (strpos($priceRange, '-') !== false) {
                $priceRangeArray = explode('-', $priceRange);
                $minPrice = floatval($priceRangeArray[0]);
                $maxPrice = isset($priceRangeArray[1]) ? floatval($priceRangeArray[1]) : null;
                
                if ($maxPrice) {
                    $whereConditions[] = "v.price_per_hour BETWEEN ? AND ?";
                    $params[] = $minPrice;
                    $params[] = $maxPrice;
                } else {
                    $whereConditions[] = "v.price_per_hour >= ?";
                    $params[] = $minPrice;
                }
            } elseif (strpos($priceRange, '+') !== false) {
                $minPrice = floatval(str_replace('+', '', $priceRange));
                $whereConditions[] = "v.price_per_hour >= ?";
                $params[] = $minPrice;
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        $limitClause = $limit ? "LIMIT " . intval($limit) : "";
        
        $stmt = $conn->prepare("
            SELECT 
                v.id,
                v.name,
                v.description,
                v.capacity,
                v.price_per_hour,
                v.images,
                v.amenities,
                v.status,
                v.location_area,
                v.special_features,
                v.minimum_booking_hours,
                v.created_at,
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT p.id) as package_count
            FROM venues v 
            LEFT JOIN bookings b ON v.id = b.venue_id AND b.status IN ('confirmed', 'completed')
            LEFT JOIN packages p ON v.id = p.venue_id AND p.status = 'active'
            WHERE {$whereClause}
            GROUP BY v.id, v.name, v.description, v.capacity, v.price_per_hour, v.images, v.amenities, v.status, v.location_area, v.special_features, v.minimum_booking_hours, v.created_at
            ORDER BY total_bookings DESC, v.created_at DESC
            {$limitClause}
        ");
        $stmt->execute($params);
        $venues = $stmt->fetchAll();
        
        // Process venue data
        foreach ($venues as &$venue) {
            // Process images
            if (!empty($venue['images'])) {
                $images = json_decode($venue['images'], true);
                if (is_array($images) && count($images) > 0) {
                    $venue['images'] = array_map('getCorrectImagePath', $images);
                    $venue['featured_image'] = $venue['images'][0];
                } else {
                    $venue['images'] = [getCorrectImagePath($venue['images'])];
                    $venue['featured_image'] = $venue['images'][0];
                }
            } else {
                $venue['images'] = [getCorrectImagePath('default.jpg')];
                $venue['featured_image'] = $venue['images'][0];
            }
            
            // Process amenities
            if (!empty($venue['amenities'])) {
                $amenities = json_decode($venue['amenities'], true);
                $venue['amenities'] = is_array($amenities) ? $amenities : [];
            } else {
                $venue['amenities'] = [];
            }
            
            // Ensure numeric values
            $venue['capacity'] = intval($venue['capacity']);
            $venue['price_per_hour'] = floatval($venue['price_per_hour']);
            $venue['total_bookings'] = intval($venue['total_bookings']);
            $venue['package_count'] = intval($venue['package_count']);
            $venue['minimum_booking_hours'] = intval($venue['minimum_booking_hours']);
            
            // Get basic package info for preview
            $packagePreviewStmt = $conn->prepare("
                SELECT id, name, price, duration_hours 
                FROM packages 
                WHERE venue_id = ? AND status = 'active' 
                ORDER BY price ASC 
                LIMIT 3
            ");
            $packagePreviewStmt->execute([$venue['id']]);
            $venue['packages'] = $packagePreviewStmt->fetchAll();
            
            // Process package prices
            foreach ($venue['packages'] as &$package) {
                $package['price'] = floatval($package['price']);
                $package['duration_hours'] = intval($package['duration_hours']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'venues' => $venues,
            'count' => count($venues),
            'filters_applied' => [
                'event_type' => $eventType,
                'capacity' => $capacity,
                'price_range' => $priceRange
            ],
            'debug_info' => [
                'query_params' => $_GET,
                'where_conditions' => $whereConditions,
                'params' => $params
            ]
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in venues API: " . $e->getMessage());
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
    error_log("General error in venues API: " . $e->getMessage());
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