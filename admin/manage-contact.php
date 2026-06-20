<?php
// Manage Contact Messages
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
$page_title = 'Contact Messages';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $message_id = $_POST['message_id'] ?? 0;
    
    try {
        if ($action === 'mark_read') {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read', read_at = NOW() WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success_message'] = 'Message marked as read';
        }
        
        if ($action === 'mark_replied') {
            $admin_reply = $_POST['admin_reply'] ?? '';
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW(), admin_reply = ?, replied_by = ? WHERE id = ?");
            $stmt->execute([$admin_reply, $_SESSION['admin_id'], $message_id]);
            $_SESSION['success_message'] = 'Reply saved successfully';
        }
        
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success_message'] = 'Message deleted successfully';
        }
        
        if ($action === 'archive') {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = 'archived' WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success_message'] = 'Message archived successfully';
        }
        
        header('Location: manage-contact.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
}

// Fetch messages with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

if ($filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    $stmt = $conn->prepare("SELECT * FROM contact_messages $where_sql ORDER BY created_at DESC");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for filter badges
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status");
    $counts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[$row['status']] = $row['count'];
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching messages: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Mavic's Resort Admin</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/manage-contact.css">
    <link rel="stylesheet" href="css/custom-modal.css">
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
            
            <div class="contact-management">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-envelope"></i>
                        Contact Messages
                    </h1>
                    <a href="../customer/contact.php" target="_blank" class="preview-btn">
                        <i class="fas fa-eye"></i>
                        View Contact Page
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="filters-section">
                    <div class="filter-tabs">
                        <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All Messages
                            <?php if (isset($counts)): ?>
                            <span class="badge-count"><?php echo array_sum($counts); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i>
                            Unread
                            <?php if (isset($counts['unread'])): ?>
                            <span class="badge-count"><?php echo $counts['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope-open"></i>
                            Read
                            <?php if (isset($counts['read'])): ?>
                            <span class="badge-count"><?php echo $counts['read']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?filter=replied" class="filter-tab <?php echo $filter === 'replied' ? 'active' : ''; ?>">
                            <i class="fas fa-reply"></i>
                            Replied
                            <?php if (isset($counts['replied'])): ?>
                            <span class="badge-count"><?php echo $counts['replied']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?filter=archived" class="filter-tab <?php echo $filter === 'archived' ? 'active' : ''; ?>">
                            <i class="fas fa-archive"></i>
                            Archived
                            <?php if (isset($counts['archived'])): ?>
                            <span class="badge-count"><?php echo $counts['archived']; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <form method="GET" class="search-box">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search messages by name, email, subject, or content..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <button type="submit">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                    </form>
                </div>
                
                <!-- Messages List -->
                <?php if (!empty($messages)): ?>
                    <div class="messages-grid">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-card <?php echo $msg['status']; ?>">
                                <div class="message-header">
                                    <div class="message-sender">
                                        <div class="sender-name"><?php echo htmlspecialchars($msg['name']); ?></div>
                                        <div class="sender-details">
                                            <span><i class="fas fa-envelope"></i><?php echo htmlspecialchars($msg['email']); ?></span>
                                            <?php if (!empty($msg['phone'])): ?>
                                            <span><i class="fas fa-phone"></i><?php echo htmlspecialchars($msg['phone']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="message-status status-<?php echo $msg['status']; ?>">
                                        <?php echo ucfirst($msg['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="message-subject">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($msg['subject']); ?>
                                </div>
                                
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 200))); ?>
                                    <?php if (strlen($msg['message']) > 200): ?>...<?php endif; ?>
                                </div>
                                
                                <div class="message-meta">
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                    </span>
                                    <div class="message-actions">
                                        <button class="btn-action btn-view" onclick="viewMessage(<?php echo $msg['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($msg['status'] !== 'replied'): ?>
                                        <button class="btn-action btn-reply" onclick="replyMessage(<?php echo $msg['id']; ?>)">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($msg['status'] !== 'archived'): ?>
                                        <button class="btn-action btn-archive" onclick="confirmArchiveMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes(htmlspecialchars($msg['name'])); ?>')">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-delete" onclick="confirmDeleteMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes(htmlspecialchars($msg['name'])); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Messages Found</h3>
                        <p>There are no contact messages<?php echo $filter !== 'all' ? ' with status: ' . $filter : ''; ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Include Logout Modal -->
    <?php include 'includes/logout-modal.php'; ?>
    
    <!-- Include Custom Modals -->
    <?php include 'modals/view-message-modal.php'; ?>
    <?php include 'modals/reply-message-modal.php'; ?>
    <?php include 'modals/alert-modal.php'; ?>
    <?php include 'modals/confirm-modal.php'; ?>
    
    <!-- Scripts -->
    <script src="js/admin-main.js"></script>
    <script src="js/contact-management.js"></script>
    <script>
        // Show success/error messages if present
        <?php if (isset($_SESSION['success_message'])): ?>
        showSuccessModal('<?php echo addslashes($_SESSION['success_message']); unset($_SESSION['success_message']); ?>');
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        showErrorModal('<?php echo addslashes($_SESSION['error_message']); unset($_SESSION['error_message']); ?>');
        <?php endif; ?>
    </script>
</body>
</html>
