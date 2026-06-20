<?php
// Manage Footer Settings
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
$page_title = 'Footer Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_settings') {
        try {
            $conn->beginTransaction();
            
            // Update each setting
            foreach ($_POST as $key => $value) {
                if ($key !== 'action' && strpos($key, 'setting_') === 0) {
                    $setting_key = str_replace('setting_', '', $key);
                    $stmt = $conn->prepare("UPDATE footer_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $_SESSION['admin_id'], $setting_key]);
                }
            }
            
            $conn->commit();
            $_SESSION['success_message'] = 'Footer settings updated successfully!';
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = 'Error updating settings: ' . $e->getMessage();
        }
        
        header('Location: manage-footer.php');
        exit();
    }
}

// Fetch all settings grouped
try {
    $stmt = $conn->query("SELECT * FROM footer_settings ORDER BY setting_group, display_order ASC");
    $all_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group settings
    $settings = [];
    foreach ($all_settings as $setting) {
        $settings[$setting['setting_group']][] = $setting;
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching settings: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Settings - Mavic's Resort Admin</title>
    
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
    
    <style>
        .settings-grid {
            display: grid;
            gap: 2rem;
        }
        
        .settings-group {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .group-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .group-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .group-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
        }
        
        .setting-item {
            margin-bottom: 1.5rem;
        }
        
        .setting-item:last-child {
            margin-bottom: 0;
        }
        
        .setting-label {
            display: block;
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .setting-key {
            display: inline-block;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #6c757d;
            font-family: monospace;
            margin-left: 0.5rem;
        }
        
        .setting-input,
        .setting-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .setting-input:focus,
        .setting-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .setting-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .setting-help {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .form-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1.5rem;
            margin: 2rem -1.5rem -1.5rem;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            border-radius: 0 0 12px 12px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-preview {
            background: white;
            color: #667eea;
            padding: 0.875rem 2rem;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-preview:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }
    </style>
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
                        <i class="fas fa-shoe-prints"></i>
                        Footer Settings
                    </h1>
                    <a href="../customer/index.php#main-footer" target="_blank" class="preview-btn">
                        <i class="fas fa-eye"></i>
                        Preview Footer
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
                
                <!-- Settings Form -->
                <form method="POST" action="" id="settingsForm">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="settings-grid">
                        <!-- Contact Information -->
                        <?php if (isset($settings['contact'])): ?>
                        <div class="settings-group">
                            <div class="group-header">
                                <i class="fas fa-address-book"></i>
                                <h3>Contact Information</h3>
                            </div>
                            
                            <?php foreach ($settings['contact'] as $setting): ?>
                            <div class="setting-item">
                                <label class="setting-label">
                                    <?php 
                                    $labels = [
                                        'contact_address' => 'Address',
                                        'contact_phone_1' => 'Primary Phone',
                                        'contact_phone_2' => 'Secondary Phone',
                                        'contact_email' => 'Email Address',
                                        'contact_hours' => 'Business Hours'
                                    ];
                                    echo $labels[$setting['setting_key']] ?? ucfirst(str_replace(['contact_', '_'], ['', ' '], $setting['setting_key']));
                                    ?>
                                    <span class="setting-key"><?php echo $setting['setting_key']; ?></span>
                                </label>
                                <?php if ($setting['setting_key'] === 'contact_address'): ?>
                                    <textarea 
                                        name="setting_<?php echo $setting['setting_key']; ?>"
                                        class="setting-textarea"
                                        rows="3"
                                    ><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php else: ?>
                                    <input 
                                        type="text" 
                                        name="setting_<?php echo $setting['setting_key']; ?>"
                                        value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                        class="setting-input"
                                    >
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- About Section -->
                        <?php if (isset($settings['about'])): ?>
                        <div class="settings-group">
                            <div class="group-header">
                                <i class="fas fa-info-circle"></i>
                                <h3>About Section</h3>
                            </div>
                            
                            <?php foreach ($settings['about'] as $setting): ?>
                            <div class="setting-item">
                                <label class="setting-label">
                                    <?php 
                                    $labels = [
                                        'about_title' => 'Resort Title',
                                        'about_description' => 'Description'
                                    ];
                                    echo $labels[$setting['setting_key']] ?? ucfirst(str_replace(['about_', '_'], ['', ' '], $setting['setting_key']));
                                    ?>
                                    <span class="setting-key"><?php echo $setting['setting_key']; ?></span>
                                </label>
                                <?php if ($setting['setting_key'] === 'about_description'): ?>
                                    <textarea 
                                        name="setting_<?php echo $setting['setting_key']; ?>"
                                        class="setting-textarea"
                                        rows="4"
                                    ><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php else: ?>
                                    <input 
                                        type="text" 
                                        name="setting_<?php echo $setting['setting_key']; ?>"
                                        value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                        class="setting-input"
                                    >
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Social Media -->
                        <?php if (isset($settings['social'])): ?>
                        <div class="settings-group">
                            <div class="group-header">
                                <i class="fas fa-share-alt"></i>
                                <h3>Social Media Links</h3>
                            </div>
                            
                            <?php foreach ($settings['social'] as $setting): ?>
                            <div class="setting-item">
                                <label class="setting-label">
                                    <?php 
                                    $labels = [
                                        'social_facebook' => 'Facebook URL',
                                        'social_instagram' => 'Instagram URL',
                                        'social_twitter' => 'Twitter URL'
                                    ];
                                    echo $labels[$setting['setting_key']] ?? ucfirst(str_replace(['social_', '_'], ['', ' '], $setting['setting_key']));
                                    ?>
                                    <span class="setting-key"><?php echo $setting['setting_key']; ?></span>
                                </label>
                                <input 
                                    type="url" 
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="setting-input"
                                    placeholder="https://"
                                >
                                <p class="setting-help">Leave empty to hide this social media link</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Copyright -->
                        <?php if (isset($settings['copyright'])): ?>
                        <div class="settings-group">
                            <div class="group-header">
                                <i class="fas fa-copyright"></i>
                                <h3>Copyright Text</h3>
                            </div>
                            
                            <?php foreach ($settings['copyright'] as $setting): ?>
                            <div class="setting-item">
                                <label class="setting-label">
                                    Copyright Text
                                    <span class="setting-key"><?php echo $setting['setting_key']; ?></span>
                                </label>
                                <input 
                                    type="text" 
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="setting-input"
                                >
                                <p class="setting-help">The year will be automatically added before this text</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="../customer/index.php#main-footer" target="_blank" class="btn-preview">
                            <i class="fas fa-eye"></i>
                            Preview Changes
                        </a>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <!-- Include Logout Modal -->
    <?php include 'includes/logout-modal.php'; ?>
    
    <!-- Scripts -->
    <script src="js/admin-main.js"></script>
    <script>
        // Unsaved Changes Warning
        let formChanged = false;
        
        const form = document.getElementById('settingsForm');
        const inputs = form.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                formChanged = true;
            });
        });
        
        form.addEventListener('submit', () => {
            formChanged = false;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Form Submission with Loading State
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-save');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
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
