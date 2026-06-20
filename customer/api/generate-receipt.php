<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$userId = $_SESSION['user_id'];
$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'print';

if (!$paymentId) {
    die('Invalid payment ID');
}

try {
    $conn = getDBConnection();
    
    // Get payment details with booking and customer info
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            b.id as booking_id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.event_type,
            b.guest_count,
            b.total_amount as booking_total,
            b.down_payment,
            b.balance,
            b.special_requests,
            v.name as venue_name,
            v.location_area,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.address
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.id
        INNER JOIN venues v ON b.venue_id = v.id
        INNER JOIN customers c ON b.customer_id = c.id
        WHERE p.id = ? AND b.customer_id = ?
    ");
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        die('Payment not found');
    }
    
} catch (PDOException $e) {
    error_log("Error fetching payment details: " . $e->getMessage());
    die('Error generating receipt');
}

// Generate receipt HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?php echo $payment['id']; ?> - Mavic's Resort</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 2px solid #667eea;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .receipt-header h1 {
            color: #667eea;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .receipt-header .subtitle {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .receipt-number {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section h2 {
            color: #667eea;
            font-size: 1.3rem;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .payment-details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .payment-details-table th,
        .payment-details-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .payment-details-table th {
            background: #f9fafb;
            color: #667eea;
            font-weight: 600;
        }
        
        .amount-row {
            background: #f9fafb;
            font-weight: bold;
        }
        
        .total-row {
            background: #667eea;
            color: white;
            font-size: 1.2rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .print-button {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin: 20px auto;
            display: block;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
            
            .receipt-container {
                border: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>MAVIC'S RESORT</h1>
            <p class="subtitle">Payment Receipt</p>
        </div>
        
        <div class="receipt-number">
            RECEIPT #<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
        
        <div class="info-section">
            <h2>Customer Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Booking ID</span>
                    <span class="info-value">#<?php echo $payment['booking_id']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Booking Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Venue</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['venue_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Event Type</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['event_type']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Event Date</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($payment['booking_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Event Time</span>
                    <span class="info-value">
                        <?php echo date('g:i A', strtotime($payment['start_time'])); ?> - 
                        <?php echo date('g:i A', strtotime($payment['end_time'])); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Number of Guests</span>
                    <span class="info-value"><?php echo $payment['guest_count']; ?> people</span>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Payment Information</h2>
            <table class="payment-details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Booking Total</td>
                        <td style="text-align: right;">₱<?php echo number_format($payment['booking_total'], 2); ?></td>
                    </tr>
                    <tr class="amount-row">
                        <td>This Payment</td>
                        <td style="text-align: right;">₱<?php echo number_format($payment['amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Remaining Balance</td>
                        <td style="text-align: right;">₱<?php echo number_format($payment['balance'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value"><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Status</span>
                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                        <?php echo ucfirst($payment['payment_status']); ?>
                    </span>
                </div>
                <?php if ($payment['reference_number']): ?>
                <div class="info-item">
                    <span class="info-label">Reference Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['reference_number']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Payment Date</span>
                    <span class="info-value">
                        <?php echo $payment['payment_date'] ? date('F j, Y g:i A', strtotime($payment['payment_date'])) : 'Pending'; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Receipt Generated</span>
                    <span class="info-value"><?php echo date('F j, Y g:i A'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p><strong>Thank you for choosing Mavic's Resort!</strong></p>
            <p>For inquiries, please contact us at info@mavicsresort.com or call +63 961 306 7957</p>
            <p style="margin-top: 10px; font-size: 0.85rem;">
                This is an official receipt. Please keep this for your records.
            </p>
        </div>
    </div>
    
    <?php if ($action === 'print'): ?>
    <button class="print-button" onclick="window.print()">Print Receipt</button>
    <?php endif; ?>
    
    <?php if ($action === 'print'): ?>
    <script>
        // Auto print on load if print action
        window.onload = function() {
            // Optional: uncomment to auto-print
            // window.print();
        };
    </script>
    <?php endif; ?>
</body>
</html>
