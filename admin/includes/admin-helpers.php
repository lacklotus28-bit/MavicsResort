<?php
// Admin Helper Functions - Mavic's Resort

// Helper function to get initials
function getInitials($name) {
    if (empty($name)) return 'NA';
    
    $words = explode(' ', $name);
    $initials = '';
    foreach($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

// Helper function to format currency
function formatCurrency($amount) {
    if ($amount === null || $amount === '') {
        return '₱0.00';
    }
    return '₱' . number_format($amount, 2);
}

// Helper function to format status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'confirmed': 
        case 'completed':
        case 'paid':
            return 'confirmed';
        case 'pending':
        case 'partial':
            return 'pending';
        case 'cancelled':
        case 'failed':
        case 'unpaid':
        case 'refunded':
            return 'cancelled';
        default: 
            return 'pending';
    }
}

// Helper function to get active nav class
function isActivePage($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

// Helper function to format date
function formatDate($date, $format = 'M j, Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

// Helper function to format datetime
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

// Helper function to format time
function formatTime($time, $format = 'g:i A') {
    if (empty($time)) return '-';
    return date($format, strtotime($time));
}

// Helper function to get time ago
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hour' . (floor($time/3600) > 1 ? 's' : '') . ' ago';
    if ($time < 2592000) return floor($time/86400) . ' day' . (floor($time/86400) > 1 ? 's' : '') . ' ago';
    
    return formatDate($datetime);
}

// Helper function to truncate text
function truncateText($text, $limit = 50) {
    if (strlen($text) <= $limit) return $text;
    return substr($text, 0, $limit) . '...';
}

// Helper function to sanitize output
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Helper function to get payment method icon
function getPaymentMethodIcon($method) {
    $icons = [
        'cash' => '<i class="fas fa-money-bill"></i>',
        'bank_transfer' => '<i class="fas fa-university"></i>',
        'credit_card' => '<i class="fas fa-credit-card"></i>',
        'gcash' => '<i class="fas fa-mobile-alt"></i>',
        'paymaya' => '<i class="fas fa-mobile-alt"></i>'
    ];
    
    return $icons[strtolower($method)] ?? '<i class="fas fa-question"></i>';
}

// Note: logActivity() function is already declared in manage-bookings.php

// Helper function to get payment statistics
function getPaymentStats($conn) {
    try {
        $stats = [];
        
        // Today's revenue
        $stmt = $conn->query("
            SELECT COALESCE(SUM(amount), 0) as today_revenue 
            FROM payments 
            WHERE DATE(payment_date) = CURRENT_DATE() 
            AND payment_status = 'completed'
        ");
        $stats['today_revenue'] = $stmt->fetch()['today_revenue'];
        
        // Monthly revenue
        $stmt = $conn->query("
            SELECT COALESCE(SUM(amount), 0) as monthly_revenue 
            FROM payments 
            WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(payment_date) = YEAR(CURRENT_DATE())
            AND payment_status = 'completed'
        ");
        $stats['monthly_revenue'] = $stmt->fetch()['monthly_revenue'];
        
        // Pending payments count
        $stmt = $conn->query("
            SELECT COUNT(*) as pending_count 
            FROM payments 
            WHERE payment_status = 'pending'
        ");
        $stats['pending_payments'] = $stmt->fetch()['pending_count'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Payment stats error: " . $e->getMessage());
        return [
            'today_revenue' => 0,
            'monthly_revenue' => 0,
            'pending_payments' => 0
        ];
    }
}

// Helper function to validate payment amount
function validatePaymentAmount($conn, $booking_id, $amount) {
    try {
        $stmt = $conn->prepare("
            SELECT total_amount, COALESCE(down_payment, 0) as paid_amount 
            FROM bookings 
            WHERE id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['valid' => false, 'message' => 'Booking not found'];
        }
        
        $balance = $booking['total_amount'] - $booking['paid_amount'];
        
        if ($amount > $balance) {
            return [
                'valid' => false, 
                'message' => "Amount exceeds outstanding balance of " . formatCurrency($balance)
            ];
        }
        
        if ($amount <= 0) {
            return ['valid' => false, 'message' => 'Amount must be greater than zero'];
        }
        
        return ['valid' => true, 'balance' => $balance];
    } catch (PDOException $e) {
        error_log("Payment validation error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Validation failed'];
    }
}

// Helper function to generate payment reference number
function generatePaymentReference($prefix = 'PAY') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Helper function to get booking payment summary
function getBookingPaymentSummary($conn, $booking_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                b.total_amount,
                COALESCE(SUM(p.amount), 0) as total_paid,
                (b.total_amount - COALESCE(SUM(p.amount), 0)) as balance,
                COUNT(p.id) as payment_count
            FROM bookings b
            LEFT JOIN payments p ON b.id = p.booking_id AND p.payment_status = 'completed'
            WHERE b.id = ?
            GROUP BY b.id
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Booking payment summary error: " . $e->getMessage());
        return null;
    }
}

// Helper function to check if payment can be refunded
function canRefundPayment($payment_date, $booking_date) {
    $payment_timestamp = strtotime($payment_date);
    $booking_timestamp = strtotime($booking_date);
    $current_timestamp = time();
    
    // Can refund if booking is more than 7 days away
    $days_until_booking = ($booking_timestamp - $current_timestamp) / (24 * 60 * 60);
    
    return $days_until_booking > 7;
}

// Helper function to get status badge HTML
function getStatusBadge($status) {
    $class = getStatusClass($status);
    $display = ucfirst(str_replace('_', ' ', $status));
    return "<span class=\"status {$class}\">{$display}</span>";
}

// Helper function to format phone number
function formatPhone($phone) {
    if (empty($phone)) return '-';
    
    // Remove all non-digits
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Format Philippine mobile numbers
    if (strlen($clean) == 11 && substr($clean, 0, 2) == '09') {
        return substr($clean, 0, 4) . ' ' . substr($clean, 4, 3) . ' ' . substr($clean, 7, 4);
    }
    
    return $phone;
}

// Helper function to get user role badge
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="role-badge admin">Admin</span>',
        'superadmin' => '<span class="role-badge superadmin">Super Admin</span>',
        'staff' => '<span class="role-badge staff">Staff</span>'
    ];
    
    return $badges[strtolower($role)] ?? '<span class="role-badge">User</span>';
}

// Helper function to calculate age
function calculateAge($birthdate) {
    if (empty($birthdate)) return null;
    
    $birth = new DateTime($birthdate);
    $now = new DateTime();
    $age = $now->diff($birth);
    
    return $age->y;
}

// Helper function to get event type icon
function getEventTypeIcon($eventType) {
    $icons = [
        'wedding' => '<i class="fas fa-ring"></i>',
        'wedding reception' => '<i class="fas fa-ring"></i>',
        'birthday party' => '<i class="fas fa-birthday-cake"></i>',
        'corporate event' => '<i class="fas fa-briefcase"></i>',
        'conference' => '<i class="fas fa-users"></i>',
        'seminar' => '<i class="fas fa-chalkboard-teacher"></i>',
        'family reunion' => '<i class="fas fa-home"></i>',
        'graduation' => '<i class="fas fa-graduation-cap"></i>'
    ];
    
    $key = strtolower(trim($eventType));
    return $icons[$key] ?? '<i class="fas fa-calendar"></i>';
}

// Helper function to generate secure token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Helper function to check if date is weekend
function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return ($dayOfWeek >= 6); // Saturday = 6, Sunday = 7
}

// Helper function to get next business day
function getNextBusinessDay($date = null) {
    if ($date === null) $date = date('Y-m-d');
    
    $timestamp = strtotime($date . ' +1 day');
    
    while (isWeekend(date('Y-m-d', $timestamp))) {
        $timestamp = strtotime('+1 day', $timestamp);
    }
    
    return date('Y-m-d', $timestamp);
}

// Note: logActivity() function is already declared in manage-bookings.php

// Helper function to log admin activities
/**
 * Log admin activity for audit trail
 * 
 * @param PDO $conn Database connection
 * @param int $admin_id ID of the admin performing the action
 * @param string $action Type of action performed
 * @param string $table_name Table affected by the action
 * @param int $record_id ID of the record affected
 * @param string $old_values JSON string of old values (optional)
 * @param string $new_values JSON string of new values (optional)
 * @return bool Success status
 */
function logActivity($conn, $admin_id, $action, $table_name, $record_id, $old_values = null, $new_values = null) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (
                admin_id, 
                action, 
                table_name, 
                record_id, 
                old_values,
                new_values,
                ip_address, 
                user_agent, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $stmt->execute([
            $admin_id,
            $action,
            $table_name,
            $record_id,
            $old_values,
            $new_values,
            $ip_address,
            $user_agent
        ]);
    } catch(PDOException $e) {
        error_log("Activity logging error: " . $e->getMessage());
        return false; // Don't break the main operation if logging fails
    }
}

// Helper function to get pending notifications count
function getPendingNotificationsCount($conn) {
    try {
        // Count pending bookings
        $stmt = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
        $pending_bookings = $stmt->fetch()['count'];
        
        // Count unread inquiries
        $stmt = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'");
        $unread_inquiries = $stmt->fetch()['count'];
        
        // Count pending payments
        $stmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'pending'");
        $pending_payments = $stmt->fetch()['count'];
        
        return $pending_bookings + $unread_inquiries + $pending_payments;
    } catch (PDOException $e) {
        error_log("Notifications count error: " . $e->getMessage());
        return 0;
    }
}

// Helper function to get notifications list
function getNotificationsList($conn) {
    try {
        $output = '';
        
        // Get pending bookings
        $stmt = $conn->query("
            SELECT b.id, b.booking_date, c.first_name, c.last_name 
            FROM bookings b 
            JOIN customers c ON b.customer_id = c.id 
            WHERE b.status = 'pending' 
            ORDER BY b.created_at DESC 
            LIMIT 5
        ");
        $pending_bookings = $stmt->fetchAll();
        
        foreach ($pending_bookings as $booking) {
            $output .= '
            <div class="notification-item">
                <div class="notification-icon booking">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="notification-content">
                    <p>New booking request from ' . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . '</p>
                    <span class="notification-time">' . formatDate($booking['booking_date']) . '</span>
                </div>
            </div>';
        }
        
        // Get unread inquiries
        $stmt = $conn->query("
            SELECT id, name, subject, created_at 
            FROM inquiries 
            WHERE status = 'new' 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $unread_inquiries = $stmt->fetchAll();
        
        foreach ($unread_inquiries as $inquiry) {
            $output .= '
            <div class="notification-item">
                <div class="notification-icon inquiry">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="notification-content">
                    <p>New inquiry: ' . htmlspecialchars($inquiry['subject']) . '</p>
                    <span class="notification-time">' . getTimeAgo($inquiry['created_at']) . '</span>
                </div>
            </div>';
        }
        
        // Get pending payments
        $stmt = $conn->query("
            SELECT p.id, p.amount, b.booking_date, c.first_name, c.last_name 
            FROM payments p 
            JOIN bookings b ON p.booking_id = b.id 
            JOIN customers c ON b.customer_id = c.id 
            WHERE p.payment_status = 'pending' 
            ORDER BY p.created_at DESC 
            LIMIT 3
        ");
        $pending_payments = $stmt->fetchAll();
        
        foreach ($pending_payments as $payment) {
            $output .= '
            <div class="notification-item">
                <div class="notification-icon payment">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="notification-content">
                    <p>Pending payment: ' . formatCurrency($payment['amount']) . ' from ' . htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) . '</p>
                    <span class="notification-time">' . formatDate($payment['booking_date']) . '</span>
                </div>
            </div>';
        }
        
        if (empty($output)) {
            $output = '<div class="notification-empty">No new notifications</div>';
        }
        
        return $output;
    } catch (PDOException $e) {
        error_log("Notifications list error: " . $e->getMessage());
        return '<div class="notification-empty">Error loading notifications</div>';
    }
}


?>