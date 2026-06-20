<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration - Multiple path options
$possible_db_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../database.php',
    dirname(__DIR__) . '/config/database.php',
    dirname(__DIR__) . '/database.php'
];

$db_loaded = false;
foreach ($possible_db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    // Fallback database connection if config file not found
    class Database {
        private $host = 'localhost';
        private $db_name = 'mavics_resort';
        private $username = 'root';
        private $password = '';
        private $conn;
        
        public function getConnection() {
            $this->conn = null;
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch(PDOException $e) {
                error_log("Connection Error: " . $e->getMessage());
                throw $e;
            }
            return $this->conn;
        }
    }
    
    function getDBConnection() {
        $database = new Database();
        return $database->getConnection();
    }
}

try {
    $conn = getDBConnection();
    
    // Get real statistics from database
    $stats = [];
    
    // Get total customers
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_customers'] = intval($result['total']);
    
    // Get total bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE status IN ('confirmed', 'completed')");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_bookings'] = intval($result['total']);
    
    // Get total venues
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM venues WHERE status = 'available'");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_venues'] = intval($result['total']);
    
    // Get total revenue this month
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as revenue 
        FROM bookings 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE()) 
        AND status != 'cancelled'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['monthly_revenue'] = floatval($result['revenue']);
    
    // Get years of experience (based on earliest booking or system launch)
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(YEAR, MIN(created_at), NOW()) as years FROM bookings");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['years_experience'] = max(intval($result['years']), 1); // At least 1 year
    
    // Get additional useful stats
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['pending_bookings'] = intval($result['pending']);
    
    // Get most popular venue
    $stmt = $conn->prepare("
        SELECT v.name, COUNT(b.id) as booking_count 
        FROM venues v 
        LEFT JOIN bookings b ON v.id = b.venue_id AND b.status IN ('confirmed', 'completed')
        WHERE v.status = 'available'
        GROUP BY v.id, v.name 
        ORDER BY booking_count DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['popular_venue'] = $result ? $result['name'] : 'Conference Room';
    $stats['popular_venue_bookings'] = $result ? intval($result['booking_count']) : 0;
    
    // Get monthly booking trend (last 6 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue
        FROM bookings 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $stmt->execute();
    $stats['monthly_trends'] = $stmt->fetchAll();
    
    // Get today's stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as today_bookings,
            COALESCE(SUM(total_amount), 0) as today_revenue
        FROM bookings 
        WHERE DATE(created_at) = CURRENT_DATE()
        AND status != 'cancelled'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['today_bookings'] = intval($result['today_bookings']);
    $stats['today_revenue'] = floatval($result['today_revenue']);
    
    // Get venue utilization
    $stmt = $conn->prepare("
        SELECT 
            v.name,
            v.capacity,
            COUNT(b.id) as total_bookings,
            ROUND((COUNT(b.id) / NULLIF(
                (SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'completed'))
            , 0)) * 100, 2) as utilization_percent
        FROM venues v
        LEFT JOIN bookings b ON v.id = b.venue_id AND b.status IN ('confirmed', 'completed')
        WHERE v.status = 'available'
        GROUP BY v.id, v.name, v.capacity
        ORDER BY utilization_percent DESC
    ");
    $stmt->execute();
    $stats['venue_utilization'] = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in stats.php: " . $e->getMessage());
    
    // Return fallback stats if database error
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error',
        'fallback_stats' => [
            'total_customers' => 4,
            'total_bookings' => 4,
            'total_venues' => 2,
            'monthly_revenue' => 42000,
            'years_experience' => 1,
            'pending_bookings' => 3,
            'today_bookings' => 0,
            'today_revenue' => 0,
            'popular_venue' => 'Conference Room',
            'popular_venue_bookings' => 2,
            'monthly_trends' => [],
            'venue_utilization' => [
                ['name' => 'Conference Room', 'capacity' => 50, 'total_bookings' => 2, 'utilization_percent' => 66.67],
                ['name' => 'Poolside Terrace', 'capacity' => 5, 'total_bookings' => 2, 'utilization_percent' => 33.33]
            ]
        ],
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("General error in stats.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'fallback_stats' => [
            'total_customers' => 4,
            'total_bookings' => 4,
            'total_venues' => 2,
            'monthly_revenue' => 42000,
            'years_experience' => 1,
            'pending_bookings' => 3,
            'today_bookings' => 0,
            'today_revenue' => 0,
            'popular_venue' => 'Conference Room',
            'popular_venue_bookings' => 2,
            'monthly_trends' => [],
            'venue_utilization' => [
                ['name' => 'Conference Room', 'capacity' => 50, 'total_bookings' => 2, 'utilization_percent' => 66.67],
                ['name' => 'Poolside Terrace', 'capacity' => 5, 'total_bookings' => 2, 'utilization_percent' => 33.33]
            ]
        ],
        'error' => $e->getMessage()
    ]);
}
?>