<?php
// Manage Venues & Packages - Mavic's Resort
require_once 'config/database.php';
require_once 'classes/auth.php';
require_once 'includes/admin-helpers.php';

$auth = new Auth();

// Check authentication
if (!$auth->isAdminLoggedIn()) {
    header("Location: admin-login.php");
    exit;
}

$admin = $auth->getCurrentAdmin();
$page_title = 'Venues & Packages Management';

// Handle logout
if (isset($_POST['logout'])) {
    $auth->adminLogout();
    header("Location: admin-login.php");
    exit;
}

// Handle image upload function
function handleImageUpload($files, $uploadDir, $prefix) {
    $uploadedImages = [];
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }
    
    if (!empty($files['name'][0])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                $fileType = $files['type'][$i];
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }
                
                // Validate file size (5MB max)
                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    continue; // Skip files that are too large
                }
                
                // Generate safe filename
                $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $fileName = $prefix . '_' . time() . '_' . $i . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
                    $uploadedImages[] = $fileName;
                }
            }
        }
    }
    
    return $uploadedImages;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Ensure we return JSON
    header('Content-Type: application/json');
    
    // Turn off error reporting to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        $conn = getDBConnection();
        
        switch ($_POST['action']) {
            case 'add_venue':
                // Handle image uploads
                $images = [];
                if (isset($_FILES['venue_images']) && !empty($_FILES['venue_images']['name'][0])) {
                    $images = handleImageUpload($_FILES['venue_images'], 'images/venues/', 'venue');
                }
                
                $stmt = $conn->prepare("INSERT INTO venues (name, description, capacity, price_per_hour, minimum_booking_hours, amenities, special_features, location_area, operating_hours_start, operating_hours_end, status, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $amenities = !empty($_POST['amenities']) ? json_encode(array_map('trim', explode(',', $_POST['amenities']))) : null;
                $imagesJson = !empty($images) ? json_encode($images) : null;
                
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['capacity'],
                    $_POST['price_per_hour'],
                    $_POST['minimum_booking_hours'] ?? 1,
                    $amenities,
                    $_POST['special_features'] ?? null,
                    $_POST['location_area'] ?? null,
                    $_POST['operating_hours_start'] ?? null,
                    $_POST['operating_hours_end'] ?? null,
                    $_POST['status'],
                    $imagesJson
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Venue added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add venue']);
                }
                break;
                
            case 'update_venue':
                // Handle image uploads
                $images = [];
                if (isset($_FILES['venue_images']) && !empty($_FILES['venue_images']['name'][0])) {
                    $images = handleImageUpload($_FILES['venue_images'], 'images/venues/', 'venue');
                }
                
                // Get existing images if no new images uploaded
                if (empty($images)) {
                    $existingStmt = $conn->prepare("SELECT images FROM venues WHERE id = ?");
                    $existingStmt->execute([$_POST['venue_id']]);
                    $existing = $existingStmt->fetch();
                    $imagesJson = $existing['images'];
                } else {
                    $imagesJson = json_encode($images);
                }
                
                $stmt = $conn->prepare("UPDATE venues SET name=?, description=?, capacity=?, price_per_hour=?, minimum_booking_hours=?, amenities=?, special_features=?, location_area=?, operating_hours_start=?, operating_hours_end=?, status=?, images=? WHERE id=?");
                
                $amenities = !empty($_POST['amenities']) ? json_encode(array_map('trim', explode(',', $_POST['amenities']))) : null;
                
                $result = $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['capacity'],
                    $_POST['price_per_hour'],
                    $_POST['minimum_booking_hours'] ?? 1,
                    $amenities,
                    $_POST['special_features'] ?? null,
                    $_POST['location_area'] ?? null,
                    $_POST['operating_hours_start'] ?? null,
                    $_POST['operating_hours_end'] ?? null,
                    $_POST['status'],
                    $imagesJson,
                    $_POST['venue_id']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Venue updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update venue']);
                }
                break;
                
            case 'delete_venue':
                // Check if venue has bookings
                $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE venue_id = ? AND status IN ('pending', 'confirmed')");
                $checkStmt->execute([$_POST['venue_id']]);
                $bookingCount = $checkStmt->fetch()['count'];
                
                if ($bookingCount > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete venue with active bookings']);
                } else {
                    $stmt = $conn->prepare("DELETE FROM venues WHERE id = ?");
                    $result = $stmt->execute([$_POST['venue_id']]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Venue deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete venue']);
                    }
                }
                break;
                
            case 'add_package':
                // Validate required fields first
                if (empty($_POST['name'])) {
                    echo json_encode(['success' => false, 'message' => 'Package name is required']);
                    exit;
                }
                
                if (empty($_POST['venue_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Venue selection is required']);
                    exit;
                }
                
                if (!isset($_POST['price']) || $_POST['price'] === '' || $_POST['price'] < 0) {
                    echo json_encode(['success' => false, 'message' => 'Valid price is required']);
                    exit;
                }
                
                if (empty($_POST['duration_hours']) || $_POST['duration_hours'] < 1) {
                    echo json_encode(['success' => false, 'message' => 'Valid duration is required']);
                    exit;
                }
                
                if (empty($_POST['inclusions'])) {
                    echo json_encode(['success' => false, 'message' => 'Package inclusions are required']);
                    exit;
                }
                
                // Handle image uploads
                $images = [];
                if (isset($_FILES['package_images']) && !empty($_FILES['package_images']['name'][0])) {
                    $images = handleImageUpload($_FILES['package_images'], 'images/packages/', 'package');
                }
                
                $stmt = $conn->prepare("INSERT INTO packages (venue_id, name, description, price, price_type, duration_hours, min_guests, max_guests, inclusions, optional_addons, available_days, advance_notice_days, status, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $inclusions = !empty($_POST['inclusions']) ? json_encode(array_map('trim', explode(',', $_POST['inclusions']))) : null;
                $availableDays = isset($_POST['available_days']) && is_array($_POST['available_days']) ? json_encode($_POST['available_days']) : null;
                $imagesJson = !empty($images) ? json_encode($images) : null;
                
                // Handle numeric values safely
                $minGuests = !empty($_POST['min_guests']) ? (int)$_POST['min_guests'] : null;
                $maxGuests = !empty($_POST['max_guests']) ? (int)$_POST['max_guests'] : null;
                $advanceNotice = !empty($_POST['advance_notice_days']) ? (int)$_POST['advance_notice_days'] : null;
                
                $result = $stmt->execute([
                    (int)$_POST['venue_id'],
                    trim($_POST['name']),
                    trim($_POST['description']),
                    (float)$_POST['price'],
                    $_POST['price_type'] ?? 'fixed',
                    (int)$_POST['duration_hours'],
                    $minGuests,
                    $maxGuests,
                    $inclusions,
                    trim($_POST['optional_addons'] ?? ''),
                    $availableDays,
                    $advanceNotice,
                    $_POST['status'],
                    $imagesJson
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Package added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add package. Please check all required fields.']);
                }
                break;
                
            case 'update_package':
                // Validate required fields first
                if (empty($_POST['name'])) {
                    echo json_encode(['success' => false, 'message' => 'Package name is required']);
                    exit;
                }
                
                if (empty($_POST['venue_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Venue selection is required']);
                    exit;
                }
                
                if (!isset($_POST['price']) || $_POST['price'] === '' || $_POST['price'] < 0) {
                    echo json_encode(['success' => false, 'message' => 'Valid price is required']);
                    exit;
                }
                
                if (empty($_POST['duration_hours']) || $_POST['duration_hours'] < 1) {
                    echo json_encode(['success' => false, 'message' => 'Valid duration is required']);
                    exit;
                }
                
                // Handle image uploads
                $images = [];
                if (isset($_FILES['package_images']) && !empty($_FILES['package_images']['name'][0])) {
                    $images = handleImageUpload($_FILES['package_images'], 'images/packages/', 'package');
                }
                
                // Get existing images if no new images uploaded
                if (empty($images)) {
                    $existingStmt = $conn->prepare("SELECT images FROM packages WHERE id = ?");
                    $existingStmt->execute([$_POST['package_id']]);
                    $existing = $existingStmt->fetch();
                    $imagesJson = $existing['images'] ?? null;
                } else {
                    $imagesJson = json_encode($images);
                }
                
                $stmt = $conn->prepare("UPDATE packages SET venue_id=?, name=?, description=?, price=?, price_type=?, duration_hours=?, min_guests=?, max_guests=?, inclusions=?, optional_addons=?, available_days=?, advance_notice_days=?, status=?, images=? WHERE id=?");
                
                $inclusions = !empty($_POST['inclusions']) ? json_encode(array_map('trim', explode(',', $_POST['inclusions']))) : null;
                $availableDays = isset($_POST['available_days']) && is_array($_POST['available_days']) ? json_encode($_POST['available_days']) : null;
                
                // Handle numeric values safely
                $minGuests = !empty($_POST['min_guests']) ? (int)$_POST['min_guests'] : null;
                $maxGuests = !empty($_POST['max_guests']) ? (int)$_POST['max_guests'] : null;
                $advanceNotice = !empty($_POST['advance_notice_days']) ? (int)$_POST['advance_notice_days'] : null;
                
                $result = $stmt->execute([
                    (int)$_POST['venue_id'],
                    trim($_POST['name']),
                    trim($_POST['description']),
                    (float)$_POST['price'],
                    $_POST['price_type'] ?? 'fixed',
                    (int)$_POST['duration_hours'],
                    $minGuests,
                    $maxGuests,
                    $inclusions,
                    trim($_POST['optional_addons'] ?? ''),
                    $availableDays,
                    $advanceNotice,
                    $_POST['status'],
                    $imagesJson,
                    (int)$_POST['package_id']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update package. Please check all required fields.']);
                }
                break;
                
            case 'delete_package':
                if (empty($_POST['package_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Package ID is required']);
                    exit;
                }
                
                $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
                $result = $stmt->execute([(int)$_POST['package_id']]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete package']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        
    } catch(PDOException $e) {
        // Log the error for debugging
        error_log("Database error in manage-venues.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
    } catch(Exception $e) {
        // Log the error for debugging
        error_log("General error in manage-venues.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    
    exit;
}

// Get venues and packages data
try {
    $conn = getDBConnection();
    
    // Get all venues
    $venuesStmt = $conn->query("
        SELECT v.*, 
               COUNT(b.id) as total_bookings,
               COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) as confirmed_bookings
        FROM venues v
        LEFT JOIN bookings b ON v.id = b.venue_id
        GROUP BY v.id
        ORDER BY v.name
    ");
    $venues = $venuesStmt->fetchAll();
    
    // Get all packages
    $packagesStmt = $conn->query("
        SELECT p.*, v.name as venue_name
        FROM packages p
        JOIN venues v ON p.venue_id = v.id
        ORDER BY v.name, p.name
    ");
    $packages = $packagesStmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Venues management error: " . $e->getMessage());
    $venues = [];
    $packages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venues & Packages - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/manage-venues.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Venues & Packages Content -->
        <div class="venues-content">
            <!-- Header Actions -->
            <div class="content-header">
                <div class="header-actions">
                    <button class="btn btn-primary" id="addVenueBtn">
                        <i class="fas fa-plus"></i>
                        Add New Venue
                    </button>
                    <button class="btn btn-secondary" id="addPackageBtn">
                        <i class="fas fa-package"></i>
                        Add New Package
                    </button>
                </div>
            </div>

            <!-- Advanced Filter Section -->
            <section class="filter-section">
                <div class="filter-bar">
                    <div class="filter-group">
                        <label for="statusFilter">Status:</label>
                        <select id="statusFilter" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="capacityFilter">Guest Capacity:</label>
                        <select id="capacityFilter" class="form-control">
                            <option value="">Any Size</option>
                            <option value="1-50">1-50 guests</option>
                            <option value="51-100">51-100 guests</option>
                            <option value="101-200">101-200 guests</option>
                            <option value="201+">201+ guests</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="priceRangeFilter">Price Range:</label>
                        <select id="priceRangeFilter" class="form-control">
                            <option value="">Any Price</option>
                            <option value="0-1000">Under ₱1,000/hour</option>
                            <option value="1000-2000">₱1,000-₱2,000/hour</option>
                            <option value="2000-5000">₱2,000-₱5,000/hour</option>
                            <option value="5000+">₱5,000+/hour</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="searchInput">Search:</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search venues...">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-outline filter-btn" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        
                        <button class="btn btn-outline clear-btn" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </section>

            <!-- Venues Grid -->
            <section class="venues-section">
                <div class="section-header">
                    <h2>Venues Overview</h2>
                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="toggle-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
                
                <div class="venues-grid" id="venuesGrid">
                    <?php foreach ($venues as $venue): ?>
                        <div class="venue-card" data-venue-id="<?php echo $venue['id']; ?>" data-status="<?php echo $venue['status']; ?>">
                            <div class="venue-header">
                                <div class="venue-image">
                                    <?php
                                    $venueImages = json_decode($venue['images'], true);
                                    $firstImage = !empty($venueImages) ? $venueImages[0] : 'venue-placeholder.jpg';
                                    ?>
                                    <img src="images/venues/<?php echo $firstImage; ?>" 
                                         alt="<?php echo clean($venue['name']); ?>" 
                                         onerror="this.src='images/venue-placeholder.jpg'">
                                    <div class="venue-status <?php echo $venue['status']; ?>">
                                        <?php echo ucfirst($venue['status']); ?>
                                    </div>
                                </div>
                                <div class="venue-actions">
                                    <button class="action-btn edit-venue" data-venue-id="<?php echo $venue['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-venue" data-venue-id="<?php echo $venue['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="venue-info">
                                <h3><?php echo clean($venue['name']); ?></h3>
                                <p class="venue-description"><?php echo clean($venue['description']); ?></p>
                                
                                <div class="venue-details">
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span>Capacity: <?php echo $venue['capacity']; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-peso-sign"></i>
                                        <span><?php echo formatCurrency($venue['price_per_hour']); ?>/hour</span>
                                    </div>
                                    <?php if ($venue['location_area']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo clean($venue['location_area']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="venue-amenities">
                                    <?php 
                                    $amenities = json_decode($venue['amenities'], true) ?? [];
                                    foreach (array_slice($amenities, 0, 3) as $amenity): 
                                    ?>
                                        <span class="amenity-tag"><?php echo clean($amenity); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($amenities) > 3): ?>
                                        <span class="amenity-tag more">+<?php echo count($amenities) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="venue-stats">
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $venue['total_bookings']; ?></span>
                                        <span class="stat-label">Total Bookings</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $venue['confirmed_bookings']; ?></span>
                                        <span class="stat-label">Confirmed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Packages Section -->
            <section class="packages-section">
                <div class="section-header">
                    <h2>Event Packages</h2>
                </div>
                
                <div class="packages-table-container">
                    <table class="packages-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Venue</th>
                                <th>Duration</th>
                                <th>Guest Range</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                                <tr data-package-id="<?php echo $package['id']; ?>">
                                    <td>
                                        <div class="package-name">
                                            <strong><?php echo clean($package['name']); ?></strong>
                                            <p class="package-desc"><?php echo clean($package['description']); ?></p>
                                        </div>
                                    </td>
                                    <td><?php echo clean($package['venue_name']); ?></td>
                                    <td><?php echo $package['duration_hours']; ?> hours</td>
                                    <td>
                                        <?php
                                        if ($package['min_guests'] && $package['max_guests']) {
                                            echo $package['min_guests'] . '-' . $package['max_guests'];
                                        } elseif ($package['max_guests']) {
                                            echo 'Up to ' . $package['max_guests'];
                                        } elseif ($package['min_guests']) {
                                            echo $package['min_guests'] . '+';
                                        } else {
                                            echo 'No limit';
                                        }
                                        ?> guests
                                    </td>
                                    <td>
                                        <?php echo formatCurrency($package['price']); ?>
                                        <?php if ($package['price_type'] === 'per_person'): ?>
                                            <small>/person</small>
                                        <?php elseif ($package['price_type'] === 'per_hour'): ?>
                                            <small>/hour</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $package['status']; ?>">
                                            <?php echo ucfirst($package['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="action-btn edit-package" data-package-id="<?php echo $package['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete-package" data-package-id="<?php echo $package['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <!-- Venue Modal -->
    <div class="modal" id="venueModal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3 id="venueModalTitle">Add New Venue</h3>
                <button class="modal-close" id="venueModalClose">&times;</button>
            </div>
            <form id="venueForm" enctype="multipart/form-data">
                <input type="hidden" id="venueId" name="venue_id">
                
                <!-- Basic Info Section -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="venueName">Venue Name <span class="required">*</span></label>
                            <input type="text" id="venueName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="venueStatus">Status <span class="required">*</span></label>
                            <select id="venueStatus" name="status" required>
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="venueDescription">Description <span class="required">*</span></label>
                        <textarea id="venueDescription" name="description" rows="3" required></textarea>
                    </div>
                </div>

                <!-- Capacity & Pricing Section -->
                <div class="form-section">
                    <h4>Capacity & Pricing</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="venueCapacity">Maximum Capacity <span class="required">*</span></label>
                            <input type="number" id="venueCapacity" name="capacity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="venuePricePerHour">Price per Hour <span class="required">*</span></label>
                            <input type="number" id="venuePricePerHour" name="price_per_hour" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="venueMinBookingHours">Minimum Booking Hours</label>
                            <input type="number" id="venueMinBookingHours" name="minimum_booking_hours" min="1" value="1">
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="form-section">
                    <h4>Features</h4>
                    <div class="form-group">
                        <label for="venueAmenities">Amenities</label>
                        <input type="text" id="venueAmenities" name="amenities" placeholder="Sound System, Air Conditioning, Stage (comma-separated)">
                        <small class="form-help">Separate amenities with commas</small>
                    </div>
                    <div class="form-group">
                        <label for="venueSpecialFeatures">Special Features</label>
                        <textarea id="venueSpecialFeatures" name="special_features" rows="2" placeholder="Unique features or selling points"></textarea>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section">
                    <h4>Location</h4>
                    <div class="form-group">
                        <label for="venueLocationArea">Location/Area</label>
                        <input type="text" id="venueLocationArea" name="location_area" placeholder="Specific area within the resort">
                    </div>
                </div>

                <!-- Visuals Section -->
                <div class="form-section">
                    <h4>Images</h4>
                    <div class="form-group">
                        <label for="venueImages">Venue Images <span class="required">*</span></label>
                        <input type="file" id="venueImages" name="venue_images[]" multiple accept=".jpg,.jpeg,.png,.webp" required>
                        <small class="form-help">Upload 1-10 images (max 5MB each). Supported formats: JPG, PNG, WEBP</small>
                    </div>
                    <div id="venueImagePreview" class="image-preview"></div>
                </div>

                <!-- Availability Section -->
                <div class="form-section">
                    <h4>Operating Hours</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="venueOperatingStart">Operating Hours Start</label>
                            <input type="time" id="venueOperatingStart" name="operating_hours_start">
                        </div>
                        <div class="form-group">
                            <label for="venueOperatingEnd">Operating Hours End</label>
                            <input type="time" id="venueOperatingEnd" name="operating_hours_end">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelVenueBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Venue</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Package Modal -->
    <div class="modal" id="packageModal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3 id="packageModalTitle">Add New Package</h3>
                <button class="modal-close" id="packageModalClose">&times;</button>
            </div>
            <form id="packageForm" enctype="multipart/form-data">
                <input type="hidden" id="packageId" name="package_id">
                
                <!-- Basic Info Section -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="packageName">Package Name <span class="required">*</span></label>
                            <input type="text" id="packageName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="packageVenue">Associated Venue <span class="required">*</span></label>
                            <select id="packageVenue" name="venue_id" required>
                                <option value="">Select Venue</option>
                                <?php foreach ($venues as $venue): ?>
                                    <option value="<?php echo $venue['id']; ?>"><?php echo clean($venue['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="packageDescription">Description <span class="required">*</span></label>
                            <textarea id="packageDescription" name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="packageStatus">Status <span class="required">*</span></label>
                            <select id="packageStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Duration Section -->
                <div class="form-section">
                    <h4>Pricing & Duration</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="packagePrice">Package Price <span class="required">*</span></label>
                            <input type="number" id="packagePrice" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="packageDuration">Duration Hours <span class="required">*</span></label>
                            <input type="number" id="packageDuration" name="duration_hours" min="1" value="8" required>
                        </div>
                        <div class="form-group">
                            <label for="packagePriceType">Price Type <span class="required">*</span></label>
                            <select id="packagePriceType" name="price_type" required>
                                <option value="fixed">Fixed</option>
                                <option value="per_person">Per Person</option>
                                <option value="per_hour">Per Hour</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Capacity Section -->
                <div class="form-section">
                    <h4>Capacity</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="packageMinGuests">Minimum Guests</label>
                            <input type="number" id="packageMinGuests" name="min_guests" min="1">
                        </div>
                        <div class="form-group">
                            <label for="packageMaxGuests">Maximum Guests</label>
                            <input type="number" id="packageMaxGuests" name="max_guests" min="1">
                        </div>
                    </div>
                </div>

                <!-- Inclusions Section -->
                <div class="form-section">
                    <h4>Inclusions</h4>
                    <div class="form-group">
                        <label for="packageInclusions">Included Services <span class="required">*</span></label>
                        <input type="text" id="packageInclusions" name="inclusions" placeholder="Catering, Decorations, Sound System (comma-separated)" required>
                        <small class="form-help">Separate services with commas</small>
                    </div>
                    <div class="form-group">
                        <label for="packageOptionalAddons">Optional Add-ons</label>
                        <textarea id="packageOptionalAddons" name="optional_addons" rows="2" placeholder="Available upgrades or additional services"></textarea>
                    </div>
                </div>

                <!-- Visuals Section -->
                <div class="form-section">
                    <h4>Images</h4>
                    <div class="form-group">
                        <label for="packageImages">Package Images <span class="required">*</span></label>
                        <input type="file" id="packageImages" name="package_images[]" multiple accept=".jpg,.jpeg,.png,.webp" required>
                        <small class="form-help">Upload 1-8 images (max 5MB each). Supported formats: JPG, PNG, WEBP</small>
                    </div>
                    <div id="packageImagePreview" class="image-preview"></div>
                </div>

                <!-- Restrictions Section -->
                <div class="form-section">
                    <h4>Availability Restrictions</h4>
                    <div class="form-group">
                        <label>Available Days</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="monday">
                                <span>Monday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="tuesday">
                                <span>Tuesday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="wednesday">
                                <span>Wednesday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="thursday">
                                <span>Thursday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="friday">
                                <span>Friday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="saturday">
                                <span>Saturday</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="available_days[]" value="sunday">
                                <span>Sunday</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="packageAdvanceNotice">Advance Notice (Days)</label>
                        <input type="number" id="packageAdvanceNotice" name="advance_notice_days" min="0" placeholder="Days advance booking required">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPackageBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Package</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h3 id="confirmModalTitle">Confirm Action</h3>
            </div>
            <div class="modal-body">
                <div class="confirm-icon" id="confirmIcon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p id="confirmMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">Delete</button>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>

    <script src="js/admin-main.js"></script>
    <script src="js/manage-venues.js"></script>
    
    <style>
    /* Additional styles for the enhanced forms */
    .large-modal .modal-content {
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--light-gray);
    }
    
    .form-section:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .form-section h4 {
        margin: 0 0 1rem 0;
        color: var(--dark-gray);
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .required {
        color: #e74c3c;
    }
    
    .form-help {
        color: var(--medium-gray);
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: var(--radius-sm);
        transition: background-color 0.3s ease;
    }
    
    .checkbox-item:hover {
        background-color: var(--light-gray);
    }
    
    .checkbox-item input[type="checkbox"] {
        margin: 0;
    }
    
    .image-preview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .image-preview-item {
        position: relative;
        border-radius: var(--radius-sm);
        overflow: hidden;
        aspect-ratio: 16/9;
        background: var(--light-gray);
    }
    
    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-preview-remove {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    
    .image-preview-remove:hover {
        background: #e74c3c;
    }
    
    /* Form validation styles */
    .form-group.error input,
    .form-group.error select,
    .form-group.error textarea {
        border-color: #e74c3c;
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
    }
    
    .error-message {
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
    }
    </style>
    
    <script>
    // Image preview functionality
    document.getElementById('venueImages').addEventListener('change', function(e) {
        handleImagePreview(e.target, 'venueImagePreview');
    });
    
    document.getElementById('packageImages').addEventListener('change', function(e) {
        handleImagePreview(e.target, 'packageImagePreview');
    });
    
    function handleImagePreview(input, previewContainerId) {
        const container = document.getElementById(previewContainerId);
        container.innerHTML = '';
        
        if (input.files) {
            Array.from(input.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'image-preview-item';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="image-preview-remove" onclick="removeImagePreview(this, ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        container.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }
    
    function removeImagePreview(button, index) {
        const previewItem = button.closest('.image-preview-item');
        previewItem.remove();
        
        // Note: In a full implementation, you'd also need to update the file input
        // This is complex with vanilla JS, so consider using a library like Dropzone.js
    }
    
    // Form validation
    function validateForm(form) {
        const errors = [];
        const requiredFields = form.querySelectorAll('[required]');
        
        // Clear previous errors
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                const formGroup = field.closest('.form-group');
                formGroup.classList.add('error');
                
                const errorMsg = document.createElement('span');
                errorMsg.className = 'error-message';
                errorMsg.textContent = 'This field is required';
                formGroup.appendChild(errorMsg);
                
                errors.push(`${field.name} is required`);
            }
        });
        
        // Additional validations
        const capacity = form.querySelector('[name="capacity"]');
        if (capacity && capacity.value && capacity.value < 1) {
            errors.push('Capacity must be at least 1');
        }
        
        const price = form.querySelector('[name="price"], [name="price_per_hour"]');
        if (price && price.value && price.value < 0) {
            errors.push('Price cannot be negative');
        }
        
        return errors;
    }
    
    // Enhanced form submission with validation
    document.getElementById('venueForm').addEventListener('submit', function(e) {
        const errors = validateForm(this);
        if (errors.length > 0) {
            e.preventDefault();
            showNotification('Please fix the form errors: ' + errors.join(', '), 'error');
        }
    });
    
    document.getElementById('packageForm').addEventListener('submit', function(e) {
        const errors = validateForm(this);
        if (errors.length > 0) {
            e.preventDefault();
            showNotification('Please fix the form errors: ' + errors.join(', '), 'error');
        }
    });
    </script>
</body>
</html>