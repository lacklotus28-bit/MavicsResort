<?php
// Manage About Page Content
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';
$conn = getDBConnection();
require_once 'includes/admin-helpers.php';

// Fetch admin data for topbar
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching admin data: " . $e->getMessage());
    $admin = [
        'full_name' => $_SESSION['admin_username'] ?? 'Admin',
        'email' => 'admin@mavicsresort.com',
        'role' => 'admin',
        'profile_photo' => null
    ];
}

// Set page title for topbar
$page_title = 'Manage About Page';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_section') {
        $section_id = $_POST['section_id'];
        $section_title = $_POST['section_title'];
        $section_content = $_POST['section_content'];
        $section_data = $_POST['section_data'];
        $current_image = $_POST['current_image'] ?? '';
        $image_path = $current_image;
        
        // Handle image upload
        if (isset($_FILES['section_image']) && $_FILES['section_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/about/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_tmp = $_FILES['section_image']['tmp_name'];
            $file_name = $_FILES['section_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_types)) {
                // Generate unique filename
                $new_filename = 'about_section_' . $section_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                // Delete old image if exists
                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_path = $upload_path;
                } else {
                    $_SESSION['error_message'] = 'Failed to upload image.';
                }
            } else {
                $_SESSION['error_message'] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
            }
        }
        
        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if (!empty($current_image) && file_exists($current_image)) {
                unlink($current_image);
            }
            $image_path = null;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE about_page_content SET section_title = ?, section_content = ?, section_data = ?, section_image = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$section_title, $section_content, $section_data, $image_path, $_SESSION['admin_id'], $section_id]);
            
            if (!isset($_SESSION['error_message'])) {
                $_SESSION['success_message'] = 'Section updated successfully!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error updating section: ' . $e->getMessage();
        }
        
        header('Location: manage-about.php');
        exit();
    }
}

// Fetch all sections
try {
    $stmt = $conn->query("SELECT * FROM about_page_content ORDER BY display_order ASC");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error fetching content: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Page - Mavic's Resort Admin</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/manage-about.css">
    <link rel="stylesheet" href="css/modal.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="about-management">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-file-alt"></i>
                        Manage About Page Content
                    </h1>
                    <a href="../customer/about.php" target="_blank" class="preview-btn">
                        <i class="fas fa-eye"></i>
                        Preview Page
                    </a>
                </div>
                
                <!-- Success Alert -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Error Alert -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Section Cards -->
                <?php if (isset($sections) && count($sections) > 0): ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="section-card">
                            <form method="POST" action="" class="section-form" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_section">
                                <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($section['section_image'] ?? ''); ?>">
                                <input type="hidden" name="remove_image" id="remove_image_<?php echo $section['id']; ?>" value="0">
                                
                                <!-- Section Header -->
                                <div class="section-header">
                                    <h3><?php echo htmlspecialchars($section['section_name']); ?></h3>
                                    <span class="section-badge">Order: <?php echo $section['display_order']; ?></span>
                                </div>
                                
                                <!-- Section Title -->
                                <div class="form-group">
                                    <label for="section_title_<?php echo $section['id']; ?>">
                                        Section Title
                                    </label>
                                    <input 
                                        type="text" 
                                        id="section_title_<?php echo $section['id']; ?>" 
                                        name="section_title" 
                                        value="<?php echo htmlspecialchars($section['section_title']); ?>" 
                                        placeholder="Enter section title..."
                                        required
                                    >
                                </div>
                                
                                <!-- Section Content -->
                                <div class="form-group">
                                    <label for="section_content_<?php echo $section['id']; ?>">
                                        Section Content
                                    </label>
                                    <textarea 
                                        id="section_content_<?php echo $section['id']; ?>" 
                                        name="section_content"
                                        placeholder="Enter section content or description..."
                                        rows="6"
                                    ><?php echo htmlspecialchars($section['section_content']); ?></textarea>
                                    <p class="help-text">
                                        Main content or description for this section. Supports basic text formatting.
                                    </p>
                                </div>
                                
                                <!-- Section Data -->
                                <div class="form-group">
                                    <label for="section_data_<?php echo $section['id']; ?>">
                                        Additional Section Data
                                    </label>
                                    <textarea 
                                        id="section_data_<?php echo $section['id']; ?>" 
                                        name="section_data" 
                                        rows="8"
                                        placeholder="Enter additional information, lists, or details for this section..."
                                    ><?php echo htmlspecialchars($section['section_data']); ?></textarea>
                                    <p class="help-text">
                                        Additional text content for this section. Use line breaks to separate items.
                                    </p>
                                </div>
                                
                                <!-- Section Image (Only for Story Section) -->
                                <?php if ($section['section_name'] === 'story'): ?>
                                <div class="form-group">
                                    <label for="section_image_<?php echo $section['id']; ?>">
                                        Section Image
                                    </label>
                                    
                                    <?php if (!empty($section['section_image']) && file_exists($section['section_image'])): ?>
                                        <div class="current-image" id="current_image_<?php echo $section['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($section['section_image']); ?>" alt="Current Section Image" onclick="viewFullImage('<?php echo htmlspecialchars($section['section_image']); ?>', '<?php echo htmlspecialchars($section['section_name']); ?>')" style="cursor: pointer;" title="Click to view full size">
                                            <div class="image-actions">
                                                <button type="button" class="btn-view-image" onclick="viewFullImage('<?php echo htmlspecialchars($section['section_image']); ?>', '<?php echo htmlspecialchars($section['section_name']); ?>')">
                                                    <i class="fas fa-search-plus"></i>
                                                    View Full Size
                                                </button>
                                                <button type="button" class="btn-remove-image" onclick="removeImage(<?php echo $section['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                    Remove Image
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-image"></i>
                                            <p>No image uploaded for this section</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Image Preview -->
                                    <div class="image-preview" id="preview_<?php echo $section['id']; ?>" style="display: none;">
                                        <img src="" alt="Image Preview">
                                        <button type="button" class="btn-remove-preview" onclick="clearPreview(<?php echo $section['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            Clear Selection
                                        </button>
                                    </div>
                                    
                                    <div class="file-upload-wrapper">
                                        <label for="section_image_<?php echo $section['id']; ?>" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span class="file-name">Choose Image File</span>
                                        </label>
                                        <input 
                                            type="file" 
                                            id="section_image_<?php echo $section['id']; ?>" 
                                            name="section_image"
                                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                            class="image-upload"
                                            onchange="previewImage(this, <?php echo $section['id']; ?>)"
                                        >
                                    </div>
                                    <p class="help-text">
                                        Upload an image for this section. Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB.
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Submit Button -->
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save"></i>
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="section-card">
                        <div style="text-align: center; padding: 3rem 1rem;">
                            <i class="fas fa-database" style="font-size: 4rem; color: #ddd; margin-bottom: 1.5rem;"></i>
                            <h3 style="color: #666; margin-bottom: 1rem;">No Content Found</h3>
                            <p style="color: #999; margin-bottom: 2rem;">
                                The about page content sections haven't been initialized yet.<br>
                                Click the button below to set up the default content structure.
                            </p>
                            <a href="setup-about-content.php" class="btn-primary" style="text-decoration: none; display: inline-block;">
                                <i class="fas fa-magic"></i>
                                Initialize About Content
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Include Logout Modal -->
    <?php include 'includes/logout-modal.php'; ?>
    
    <!-- Image Viewer Modal -->
    <div id="imageViewerModal" class="image-viewer-modal" onclick="closeImageViewer()">
        <div class="image-viewer-content" onclick="event.stopPropagation()">
            <button class="image-viewer-close" onclick="closeImageViewer()">
                <i class="fas fa-times"></i>
            </button>
            <h3 id="imageViewerTitle" class="image-viewer-title"></h3>
            <div class="image-viewer-container">
                <img id="imageViewerImg" src="" alt="Full Size Image">
            </div>
            <div class="image-viewer-info">
                <p id="imageViewerPath"></p>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="js/admin-main.js"></script>
    <script>
        // View Full Image Function
        function viewFullImage(imagePath, sectionName) {
            const modal = document.getElementById('imageViewerModal');
            const img = document.getElementById('imageViewerImg');
            const title = document.getElementById('imageViewerTitle');
            const path = document.getElementById('imageViewerPath');
            
            // Set image and details
            img.src = imagePath;
            title.textContent = sectionName.charAt(0).toUpperCase() + sectionName.slice(1) + ' Section Image';
            path.textContent = 'Path: ' + imagePath;
            
            // Show modal with animation
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        // Close Image Viewer Function
        function closeImageViewer() {
            const modal = document.getElementById('imageViewerModal');
            
            // Hide with animation
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageViewer();
            }
        });
        
        // Remove Image Function
        function removeImage(sectionId) {
            if (confirm('Are you sure you want to remove this image?')) {
                document.getElementById('remove_image_' + sectionId).value = '1';
                document.querySelector(`input[name="section_id"][value="${sectionId}"]`).closest('form').submit();
            }
        }
        
        // Image Preview Function
        function previewImage(input, sectionId) {
            const file = input.files[0];
            
            if (file) {
                const fileSize = file.size / 1024 / 1024; // in MB
                
                // Check file size (5MB limit)
                if (fileSize > 5) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Hide current image if exists
                    const currentImage = document.getElementById('current_image_' + sectionId);
                    if (currentImage) {
                        currentImage.style.display = 'none';
                    }
                    
                    // Show preview
                    const preview = document.getElementById('preview_' + sectionId);
                    const previewImg = preview.querySelector('img');
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Update file label
                    const label = input.closest('.file-upload-wrapper').querySelector('.file-name');
                    if (label) {
                        label.textContent = file.name;
                        label.style.color = '#28a745';
                    }
                };
                
                reader.readAsDataURL(file);
                formChanged = true;
            }
        }
        
        // Clear Preview Function
        function clearPreview(sectionId) {
            // Clear file input
            const input = document.getElementById('section_image_' + sectionId);
            input.value = '';
            
            // Hide preview
            const preview = document.getElementById('preview_' + sectionId);
            preview.style.display = 'none';
            preview.querySelector('img').src = '';
            
            // Show current image if exists
            const currentImage = document.getElementById('current_image_' + sectionId);
            if (currentImage) {
                currentImage.style.display = 'block';
            }
            
            // Reset file label
            const label = input.closest('.file-upload-wrapper').querySelector('.file-name');
            if (label) {
                label.textContent = 'Choose Image File';
                label.style.color = '';
            }
        }
        
        // Unsaved Changes Warning
        let formChanged = false;
        
        document.querySelectorAll('.section-form').forEach(form => {
            const inputs = form.querySelectorAll('input, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    formChanged = true;
                });
            });
            
            form.addEventListener('submit', () => {
                formChanged = false;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Form Submission with Loading State
        document.querySelectorAll('.section-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('.btn-primary');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            });
        });
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>
