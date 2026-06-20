<?php
session_start();
require_once 'config/database.php';
require_once 'includes/admin-helpers.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

try {
    $conn = getDBConnection();
    
    // Fetch admin data for topbar
    $admin_stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $admin_stmt->execute([$admin_id]);
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        $admin = [
            'id' => $admin_id,
            'full_name' => $admin_username,
            'email' => 'admin@mavicsresort.com',
            'role' => 'admin',
            'profile_photo' => null
        ];
    }
    
    $page_title = 'Manage Users';
    
    // Get filter parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Build query
    $query = "SELECT 
                c.id,
                c.first_name,
                c.last_name,
                c.email,
                c.phone,
                c.address,
                c.status,
                c.email_verified,
                c.last_login,
                c.created_at,
                c.login_attempts,
                c.locked_until,
                COUNT(DISTINCT b.id) as total_bookings,
                COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_bookings
              FROM customers c
              LEFT JOIN bookings b ON c.id = b.customer_id
              WHERE 1=1";
    
    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (c.first_name LIKE :search 
                    OR c.last_name LIKE :search 
                    OR c.email LIKE :search 
                    OR c.phone LIKE :search)";
    }
    
    // Add status filter
    if (!empty($status_filter)) {
        $query .= " AND c.status = :status";
    }
    
    $query .= " GROUP BY c.id ORDER BY c.$sort $order";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }
    
    if (!empty($status_filter)) {
        $stmt->bindParam(':status', $status_filter);
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_signups,
                        SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_signups
                    FROM customers";
    
    $stats_stmt = $conn->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Mavic's Resort Admin</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">    
    <link rel="stylesheet" href="css/manage-users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5A3C, #6B4226);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8FBC8F, #6B9B6B);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['active_users']); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #DEB887, #CD853F);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['inactive_users']); ?></h3>
                        <p>Inactive Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #CD5C5C, #A04444);">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['blocked_users']); ?></h3>
                        <p>Blocked Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #5F9EA0, #4A7C7E);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['today_signups']); ?></h3>
                        <p>Today's Signups</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #D2B48C, #CD853F);">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['week_signups']); ?></h3>
                        <p>This Week</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="card">
                <div class="filters-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select id="statusFilter" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select id="sortFilter" class="form-control">
                            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                            <option value="last_name" <?php echo $sort === 'last_name' ? 'selected' : ''; ?>>Name</option>
                            <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    
                    <button class="btn btn-outline" onclick="exportUsers()">
                        <i class="fas fa-file-export"></i> Export CSV
                    </button>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Bookings</th>
                                <th>Registered</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr data-user-id="<?php echo $user['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                        </td>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                                <?php if ($user['email_verified']): ?>
                                                    <i class="fas fa-check-circle verified-badge" title="Email Verified"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                            <?php if ($user['locked_until'] && strtotime($user['locked_until']) > time()): ?>
                                                <i class="fas fa-lock text-danger" title="Account Locked"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="booking-count">
                                                <?php echo $user['total_bookings']; ?> 
                                                <small>(<?php echo $user['confirmed_bookings']; ?> confirmed)</small>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action btn-view" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <button class="btn-action btn-deactivate" onclick="changeUserStatus(<?php echo $user['id']; ?>, 'inactive')" title="Deactivate">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-action btn-activate" onclick="changeUserStatus(<?php echo $user['id']; ?>, 'active')" title="Activate">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <span id="selectedCount">0 users selected</span>
                <button class="btn btn-sm btn-primary" onclick="bulkChangeStatus('active')">
                    <i class="fas fa-check"></i> Activate
                </button>
                <button class="btn btn-sm btn-secondary" onclick="bulkChangeStatus('inactive')">
                    <i class="fas fa-ban"></i> Deactivate
                </button>
                <button class="btn btn-sm btn-outline" onclick="bulkExport()">
                    <i class="fas fa-download"></i> Export Selected
                </button>
                <button class="btn btn-sm" style="background: #CD5C5C; color: white;" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> User Details</h2>
                <span class="close" onclick="closeModal('viewUserModal')">&times;</span>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit User</h2>
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_first_name">First Name *</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_last_name">Last Name *</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status *</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="blocked">Blocked</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_first_name">First Name *</label>
                            <input type="text" class="form-control" id="add_first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_last_name">Last Name *</label>
                            <input type="text" class="form-control" id="add_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_email">Email *</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_phone">Phone</label>
                        <input type="tel" class="form-control" id="add_phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="add_password">Password *</label>
                        <input type="password" class="form-control" id="add_password" name="password" required minlength="6">
                        <small class="form-text">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_address">Address</label>
                        <textarea class="form-control" id="add_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/logout-modal.php'; ?>
    
    <script src="js/admin-main.js"></script>
    <script src="js/manage-users.js"></script>
</body>
</html>
