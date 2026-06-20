<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my-messages.php');
    exit();
}

$conn = getDBConnection();

// Get customer email
$customerEmail = $_SESSION['email'] ?? '';

// Fetch customer's messages with improved query
$messages = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            email,
            phone,
            subject,
            message,
            status,
            admin_reply,
            created_at,
            replied_at
        FROM contact_messages 
        WHERE email = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$customerEmail]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
}

// Count unread replied messages for notification
$newRepliesCount = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as new_replies
        FROM contact_messages 
        WHERE email = ? AND status = 'replied' AND admin_reply IS NOT NULL
    ");
    $stmt->execute([$customerEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newRepliesCount = $result['new_replies'] ?? 0;
} catch (PDOException $e) {
    error_log("Error counting replies: " . $e->getMessage());
}

// Page-specific CSS and JavaScript files
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/messages.css'];
$pageJSFiles = ['js/messages.js'];

// Include header
include 'includes/header.php';
?>

<!-- Messages Page -->
<div class="messages-page">
    <!-- Hero Section -->
    <section class="messages-hero">
        <div class="container">
            <div class="hero-content">
                <h1>
                    <i class="fas fa-envelope"></i> My Messages
                    <?php if ($newRepliesCount > 0): ?>
                        <span class="new-replies-badge"><?php echo $newRepliesCount; ?> New</span>
                    <?php endif; ?>
                </h1>
                <p>View your inquiries and replies from our team</p>
            </div>
        </div>
    </section>

    <!-- Messages Content -->
    <section class="messages-content">
        <div class="container">
            <?php if (empty($messages)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Messages Yet</h3>
                    <p>You haven't sent us any messages. Have a question?</p>
                    <a href="contact.php" class="btn-contact">
                        <i class="fas fa-paper-plane"></i>
                        Contact Us
                    </a>
                </div>
            <?php else: ?>
                <!-- Messages Stats Bar -->
                <div class="messages-stats">
                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <div class="stat-info">
                            <h4><?php echo count($messages); ?></h4>
                            <p>Total Messages</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-reply"></i>
                        <div class="stat-info">
                            <h4><?php echo $newRepliesCount; ?></h4>
                            <p>New Replies</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-info">
                            <h4><?php echo count(array_filter($messages, function($m) { return $m['status'] === 'replied'; })); ?></h4>
                            <p>Resolved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <div class="stat-info">
                            <h4><?php echo count(array_filter($messages, function($m) { return in_array($m['status'], ['new', 'unread', 'read']); })); ?></h4>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="messages-toolbar">
                    <!-- Search Box -->
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input 
                            type="text" 
                            id="messageSearch" 
                            placeholder="Search messages..."
                            onkeyup="searchMessages(this.value)"
                        >
                        <button class="clear-search" onclick="document.getElementById('messageSearch').value=''; searchMessages('');" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Filter Buttons -->
                    <div class="messages-filters">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> All
                        </button>
                        <button class="filter-btn" data-filter="replied">
                            <i class="fas fa-reply"></i> Replied
                        </button>
                        <button class="filter-btn" data-filter="pending">
                            <i class="fas fa-hourglass-half"></i> Pending
                        </button>
                        <button class="filter-btn" data-filter="read">
                            <i class="fas fa-envelope-open"></i> Read
                        </button>
                    </div>
                </div>

                <!-- Messages List -->
                <div class="messages-list">
                    <?php foreach ($messages as $message): ?>
                        <div class="message-card <?php echo $message['status']; ?>" data-status="<?php echo $message['status']; ?>" data-message-id="<?php echo $message['id']; ?>">
                            <div class="message-header">
                                <div class="message-info">
                                    <h3 class="message-subject">
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                    </h3>
                                    <span class="message-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-status-badge status-<?php echo $message['status']; ?>">
                                    <?php
                                    $statusIcons = [
                                    'new' => 'envelope',
                                    'unread' => 'envelope',
                                    'read' => 'envelope-open',
                                    'replied' => 'reply',
                                        'archived' => 'archive'
                                    ];
                                    $statusLabels = [
                                    'new' => 'New',
                                    'unread' => 'Unread',
                                    'read' => 'Read',
                                        'replied' => 'Replied',
                        'archived' => 'Archived'
                    ];
                                    ?>
                                    <i class="fas fa-<?php echo $statusIcons[$message['status']]; ?>"></i>
                                    <?php echo $statusLabels[$message['status']]; ?>
                                </div>
                            </div>
                            
                            <div class="message-body">
                                <div class="original-message">
                                    <h4><i class="fas fa-comment"></i> Your Message:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    
                                    <!-- Message Details -->
                                    <div class="message-meta">
                                        <?php if (!empty($message['phone'])): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($message['phone']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="meta-item">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($message['email']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($message['status'] === 'replied' && !empty($message['admin_reply'])): ?>
                                    <div class="admin-reply">
                                        <div class="reply-header">
                                            <h4><i class="fas fa-reply"></i> Admin Reply:</h4>
                                            <?php if (!empty($message['replied_at'])): ?>
                                                <span class="reply-date">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('F j, Y \a\t g:i A', strtotime($message['replied_at'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reply-content">
                                            <p><?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?></p>
                                        </div>
                                        <div class="reply-actions">
                                            <button class="btn-action" onclick="markAsRead(<?php echo $message['id']; ?>)">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </button>
                                        </div>
                                    </div>
                                <?php elseif ($message['status'] === 'read'): ?>
                                    <div class="pending-reply">
                                        <i class="fas fa-hourglass-half"></i>
                                        <p>Your message has been read. We'll reply soon!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="pending-reply">
                                        <i class="fas fa-paper-plane"></i>
                                        <p>Your message has been sent and is awaiting review.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="message-footer">
                                <button class="btn-toggle" onclick="toggleMessage(this)">
                                    <i class="fas fa-chevron-down"></i>
                                    <span>Show Details</span>
                                </button>
                                <div class="message-actions">
                                    <button class="btn-action-small" onclick="refreshMessages()" title="Refresh">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button class="btn-action-small" onclick="printMessage(<?php echo $message['id']; ?>)" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Back to Contact -->
                <div class="messages-cta">
                    <p>Have another question?</p>
                    <a href="contact.php" class="btn-contact">
                        <i class="fas fa-paper-plane"></i>
                        Send New Message
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
