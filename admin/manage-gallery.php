<?php
// Gallery Management - Mavic's Resort
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
$page_title = 'Gallery Management';

// Handle AJAX request for image data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_image' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $image_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$image_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }
    
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
        exit;
        
    } catch(PDOException $e) {
        error_log("Get image data error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->adminLogout();
    header("Location: admin-login.php");
    exit;
}

// Handle AJAX delete request
if (isset($_POST['ajax']) && $_POST['ajax'] === 'delete_image' && isset($_POST['image_id'])) {
    header('Content-Type: application/json');
    
    $image_id = filter_var($_POST['image_id'], FILTER_VALIDATE_INT);
    if (!$image_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }
    
    $response = deleteGalleryImage($image_id);
    echo json_encode($response);
    exit;
}

// Handle image upload
if (isset($_POST['upload_images'])) {
    $response = uploadGalleryImages($_FILES['gallery_images'], $_POST);
    
    // Redirect to prevent form resubmission on refresh
    $status = $response['success'] ? 'upload_success' : 'upload_error';
    $message = urlencode($response['message']);
    header("Location: manage-gallery.php?status={$status}&message={$message}");
    exit;
}

// Handle image deletion (fallback for non-AJAX)
if (isset($_POST['delete_image'])) {
    $image_id = filter_var($_POST['image_id'], FILTER_VALIDATE_INT);
    if ($image_id) {
        $response = deleteGalleryImage($image_id);
        $status = $response['success'] ? 'delete_success' : 'delete_error';
        $message = urlencode($response['message']);
        header("Location: manage-gallery.php?status={$status}&message={$message}");
        exit;
    }
}

// Handle image update
if (isset($_POST['update_image'])) {
    $image_id = filter_var($_POST['image_id'], FILTER_VALIDATE_INT);
    if ($image_id) {
        $response = updateGalleryImage($image_id, $_POST);
        $status = $response['success'] ? 'update_success' : 'update_error';
        $message = urlencode($response['message']);
        header("Location: manage-gallery.php?status={$status}&message={$message}");
        exit;
    }
}

// Handle status messages from redirects
$upload_message = $delete_message = $update_message = '';
$upload_success = $delete_success = $update_success = false;

if (isset($_GET['status']) && isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    
    switch ($_GET['status']) {
        case 'upload_success':
            $upload_message = $message;
            $upload_success = true;
            break;
        case 'upload_error':
            $upload_message = $message;
            $upload_success = false;
            break;
        case 'delete_success':
            $delete_message = $message;
            $delete_success = true;
            break;
        case 'delete_error':
            $delete_message = $message;
            $delete_success = false;
            break;
        case 'update_success':
            $update_message = $message;
            $update_success = true;
            break;
        case 'update_error':
            $update_message = $message;
            $update_success = false;
            break;
    }
}

// Get gallery images
try {
    $conn = getDBConnection();
    
    // Filter parameters
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if ($category !== 'all') {
        $where_conditions[] = "category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE :search OR description LIKE :search OR tags LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Sort options
    $order_clause = '';
    switch ($sort) {
        case 'newest':
            $order_clause = 'ORDER BY created_at DESC';
            break;
        case 'oldest':
            $order_clause = 'ORDER BY created_at ASC';
            break;
        case 'title':
            $order_clause = 'ORDER BY title ASC';
            break;
        case 'featured':
            $order_clause = 'ORDER BY is_featured DESC, created_at DESC';
            break;
    }
    
    $sql = "SELECT * FROM gallery_images {$where_clause} {$order_clause}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $gallery_images = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $conn->query("SELECT DISTINCT category FROM gallery_images WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get statistics
    $stats = getGalleryStats();
    
} catch(PDOException $e) {
    error_log("Gallery error: " . $e->getMessage());
    $gallery_images = [];
    $categories = [];
    $stats = ['total' => 0, 'featured' => 0, 'categories' => 0];
}

function uploadGalleryImages($files, $data) {
    try {
        $conn = getDBConnection();
        $upload_dir = 'uploads/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $uploaded_count = 0;
        $errors = [];
        
        // Handle multiple file uploads
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                
                // Validate file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($file_tmp);
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Invalid file type for {$file_name}";
                    continue;
                }
                
                if ($file_size > 5 * 1024 * 1024) { // 5MB limit
                    $errors[] = "File size too large for {$file_name}";
                    continue;
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'gallery_' . time() . '_' . $i . '.' . $file_extension;
                $file_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert into database
                    $stmt = $conn->prepare("
                        INSERT INTO gallery_images (title, description, image_path, category, tags, alt_text, is_featured, uploaded_by)
                        VALUES (:title, :description, :image_path, :category, :tags, :alt_text, :is_featured, :uploaded_by)
                    ");
                    
                    $title = !empty($data['titles'][$i]) ? $data['titles'][$i] : pathinfo($file_name, PATHINFO_FILENAME);
                    $description = $data['descriptions'][$i] ?? '';
                    $category = $data['category'] ?? 'general';
                    $tags = $data['tags'] ?? '';
                    $alt_text = $data['alt_texts'][$i] ?? $title;
                    $is_featured = isset($data['featured'][$i]) ? 1 : 0;
                    
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':image_path' => $new_filename,
                        ':category' => $category,
                        ':tags' => $tags,
                        ':alt_text' => $alt_text,
                        ':is_featured' => $is_featured,
                        ':uploaded_by' => $_SESSION['admin_id']
                    ]);
                    
                    $uploaded_count++;
                }
            }
        }
        
        if ($uploaded_count > 0) {
            return [
                'success' => true,
                'message' => "Successfully uploaded {$uploaded_count} image(s)" . (!empty($errors) ? ". Errors: " . implode(", ", $errors) : "")
            ];
        } else {
            return [
                'success' => false,
                'message' => "No images uploaded. Errors: " . implode(", ", $errors)
            ];
        }
        
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => "Upload error: " . $e->getMessage()
        ];
    }
}

function deleteGalleryImage($image_id) {
    try {
        $conn = getDBConnection();
        
        // Get image path first
        $stmt = $conn->prepare("SELECT image_path FROM gallery_images WHERE id = :id");
        $stmt->execute([':id' => $image_id]);
        $image = $stmt->fetch();
        
        if ($image) {
            // Delete file
            $file_path = 'uploads/gallery/' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM gallery_images WHERE id = :id");
            $stmt->execute([':id' => $image_id]);
            
            return [
                'success' => true,
                'message' => 'Image deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Image not found'
            ];
        }
        
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Delete error: ' . $e->getMessage()
        ];
    }
}

function updateGalleryImage($image_id, $data) {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            UPDATE gallery_images 
            SET title = :title, description = :description, category = :category, 
                tags = :tags, alt_text = :alt_text, is_featured = :is_featured,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $image_id,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':tags' => $data['tags'],
            ':alt_text' => $data['alt_text'],
            ':is_featured' => isset($data['is_featured']) ? 1 : 0
        ]);
        
        return [
            'success' => true,
            'message' => 'Image updated successfully'
        ];
        
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Update error: ' . $e->getMessage()
        ];
    }
}

function getGalleryStats() {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM gallery_images");
        $total = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as featured FROM gallery_images WHERE is_featured = 1");
        $featured = $stmt->fetch()['featured'];
        
        $stmt = $conn->query("SELECT COUNT(DISTINCT category) as categories FROM gallery_images WHERE category IS NOT NULL AND category != ''");
        $categories = $stmt->fetch()['categories'];
        
        return [
            'total' => $total,
            'featured' => $featured,
            'categories' => $categories
        ];
        
    } catch(Exception $e) {
        return ['total' => 0, 'featured' => 0, 'categories' => 0];
    }
}

// Create gallery_images table if it doesn't exist
try {
    $conn = getDBConnection();
    $conn->exec("
        CREATE TABLE IF NOT EXISTS gallery_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_path VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            tags TEXT,
            alt_text VARCHAR(255),
            is_featured BOOLEAN DEFAULT 0,
            uploaded_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES admin_users(id)
        )
    ");
} catch(Exception $e) {
    error_log("Gallery table creation error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/gallery-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Gallery Content -->
        <div class="gallery-content">
            <!-- Header Section -->
            <div class="gallery-header">
                <div class="header-info">
                    <h1>Gallery Management</h1>
                    <p>Manage resort highlight images for customer viewing</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-primary" onclick="openUploadModal()">
                        <i class="fas fa-plus"></i> Add Images
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="gallery-stats">
                <div class="stat-card">
                    <div class="stat-icon total-images">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Images</h3>
                        <p class="stat-number"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon featured-images">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Featured Images</h3>
                        <p class="stat-number"><?php echo $stats['featured']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon categories">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Categories</h3>
                        <p class="stat-number"><?php echo $stats['categories']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="gallery-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, description, or tags">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucwords(htmlspecialchars($cat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort by:</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                            <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="manage-gallery.php" class="btn btn-outline">Clear</a>
                </form>
            </div>

            <!-- Messages -->
            <?php if (!empty($upload_message)): ?>
                <div class="alert alert-dismissible <?php echo $upload_success ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($upload_message); ?>
                    <button type="button" class="close" onclick="dismissAlert(this.parentElement)">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($delete_message)): ?>
                <div class="alert alert-dismissible <?php echo $delete_success ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($delete_message); ?>
                    <button type="button" class="close" onclick="dismissAlert(this.parentElement)">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($update_message)): ?>
                <div class="alert alert-dismissible <?php echo $update_success ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($update_message); ?>
                    <button type="button" class="close" onclick="dismissAlert(this.parentElement)">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Gallery Grid -->
            <div class="gallery-grid">
                <?php if (empty($gallery_images)): ?>
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <h3>No Images Found</h3>
                        <p>Start by uploading some beautiful resort images to showcase.</p>
                        <button type="button" class="btn btn-primary" onclick="openUploadModal()">
                            <i class="fas fa-plus"></i> Upload Images
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($gallery_images as $image): ?>
                        <div class="gallery-item" data-id="<?php echo $image['id']; ?>">
                            <div class="image-container">
                                <img src="uploads/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['alt_text']); ?>" 
                                     onclick="openImageModal(<?php echo $image['id']; ?>)">
                                
                                <?php if ($image['is_featured']): ?>
                                    <div class="featured-badge">
                                        <i class="fas fa-star"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="image-overlay">
                                    <div class="overlay-actions">
                                        <button type="button" class="btn-icon" onclick="editImage(<?php echo $image['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-icon delete" onclick="deleteImage(<?php echo $image['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="image-info">
                                <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                <p class="image-category"><?php echo htmlspecialchars($image['category'] ?? 'Uncategorized'); ?></p>
                                <p class="image-date"><?php echo formatDate($image['created_at']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>Upload Images</h2>
                <button type="button" class="close" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-body">
                    <div class="upload-section">
                        <div class="upload-drop-zone" onclick="document.getElementById('gallery_images').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h3>Drag & drop images here or click to browse</h3>
                            <p>Supported formats: JPG, PNG, GIF, WEBP (Max: 5MB each)</p>
                        </div>
                        
                        <input type="file" 
                               id="gallery_images" 
                               name="gallery_images[]" 
                               multiple 
                               accept="image/*" 
                               style="display: none;"
                               onchange="handleFileSelect(this.files)">
                    </div>
                    
                    <div class="upload-settings">
                        <div class="form-group">
                            <label for="bulk_category">Category (applies to all):</label>
                            <select id="bulk_category" name="category" class="form-control">
                                <option value="general">General</option>
                                <option value="venues">Venues</option>
                                <option value="food">Food & Dining</option>
                                <option value="activities">Activities</option>
                                <option value="events">Events</option>
                                <option value="facilities">Facilities</option>
                                <option value="nature">Nature & Views</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="bulk_tags">Tags (applies to all, comma-separated):</label>
                            <input type="text" id="bulk_tags" name="tags" class="form-control" 
                                   placeholder="e.g., pool, sunset, dining, family">
                        </div>
                    </div>
                    
                    <div id="file-preview" class="file-preview"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" name="upload_images" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Images
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Image Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Image</h2>
                <button type="button" class="close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="image_id" id="edit_image_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title">Title:</label>
                        <input type="text" id="edit_title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description:</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category">Category:</label>
                        <select id="edit_category" name="category" class="form-control">
                            <option value="general">General</option>
                            <option value="venues">Venues</option>
                            <option value="food">Food & Dining</option>
                            <option value="activities">Activities</option>
                            <option value="events">Events</option>
                            <option value="facilities">Facilities</option>
                            <option value="nature">Nature & Views</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_tags">Tags (comma-separated):</label>
                        <input type="text" id="edit_tags" name="tags" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_alt_text">Alt Text:</label>
                        <input type="text" id="edit_alt_text" name="alt_text" class="form-control">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" id="edit_is_featured" name="is_featured" value="1">
                            <span class="checkmark"></span>
                            Featured Image
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_image" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image View Modal -->
    <div id="imageModal" class="modal image-modal">
        <div class="modal-content image-viewer">
            <div class="modal-header">
                <h2 id="image-title"></h2>
                <button type="button" class="close" onclick="closeImageModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <img id="modal-image" src="" alt="">
                <div class="image-details">
                    <p id="image-description"></p>
                    <div class="image-meta">
                        <span class="meta-item">Category: <strong id="image-category"></strong></span>
                        <span class="meta-item">Tags: <strong id="image-tags"></strong></span>
                        <span class="meta-item">Uploaded: <strong id="image-date"></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button type="button" class="close" onclick="closeDeleteConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <i class="fas fa-exclamation-triangle delete-warning-icon"></i>
                    <h3>Are you sure you want to delete this image?</h3>
                    <p>This action cannot be undone. The image will be permanently removed from the gallery.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeleteConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Image
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>

    <script src="js/admin-main.js"></script>
    <script src="js/gallery-management.js"></script>
</body>
</html>