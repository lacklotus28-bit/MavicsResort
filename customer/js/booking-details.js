// Booking Details Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Booking Details Page Loaded');
    console.log('Booking Data:', window.bookingData);
    
    // Initialize page features
    initializeActions();
    initializeAnimations();
    updatePageStatus();
});

/**
 * Initialize action buttons
 */
function initializeActions() {
    // Any additional initialization for action buttons can go here
    console.log('Actions initialized');
}

/**
 * Initialize page animations
 */
function initializeAnimations() {
    // Animate cards on scroll
    const cards = document.querySelectorAll('.detail-card');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    cards.forEach(card => {
        observer.observe(card);
    });
}

/**
 * Update page based on booking status
 */
function updatePageStatus() {
    if (!window.bookingData) return;
    
    const { status, paymentStatus } = window.bookingData;
    
    // Add status-specific classes or behaviors
    if (status === 'cancelled') {
        addCancelledNotice();
    }
    
    if (paymentStatus === 'paid') {
        hideMakePaymentButton();
    }
}

/**
 * Add a notice for cancelled bookings
 */
function addCancelledNotice() {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return;
    
    const notice = document.createElement('div');
    notice.className = 'alert alert-warning';
    notice.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <strong>This booking has been cancelled.</strong> 
        If you have any questions, please contact us.
    `;
    notice.style.cssText = `
        background: #fef3c7;
        border: 2px solid #fbbf24;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        color: #92400e;
    `;
    
    mainContent.insertBefore(notice, mainContent.firstChild);
}

/**
 * Hide make payment button if already paid
 */
function hideMakePaymentButton() {
    // This is already handled in PHP, but we can add extra logic here if needed
    console.log('Payment completed');
}

/**
 * Cancel booking
 */
function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    
    fetch('api/cancel-booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', 'Your booking has been cancelled successfully.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to cancel booking');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', error.message || 'Failed to cancel booking. Please try again.', 'error');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

/**
 * Download receipt
 */
function downloadReceipt(bookingId) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    // Create a temporary link to download the receipt
    const link = document.createElement('a');
    link.href = `api/generate-receipt.php?booking_id=${bookingId}`;
    link.download = `booking-receipt-${bookingId}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalText;
        showAlert('Success', 'Receipt downloaded successfully!', 'success');
    }, 1500);
}

/**
 * Show alert message
 */
function showAlert(title, message, type = 'info') {
    // Create alert container if it doesn't exist
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(alertContainer);
    }
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    
    const colors = {
        success: { bg: '#d1fae5', border: '#10b981', text: '#065f46', icon: 'check-circle' },
        error: { bg: '#fee2e2', border: '#ef4444', text: '#991b1b', icon: 'times-circle' },
        warning: { bg: '#fef3c7', border: '#fbbf24', text: '#92400e', icon: 'exclamation-triangle' },
        info: { bg: '#dbeafe', border: '#3b82f6', text: '#1e3a8a', icon: 'info-circle' }
    };
    
    const color = colors[type] || colors.info;
    
    alert.style.cssText = `
        background: ${color.bg};
        border: 2px solid ${color.border};
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        color: ${color.text};
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideIn 0.3s ease;
    `;
    
    alert.innerHTML = `
        <div style="display: flex; align-items: flex-start; gap: 1rem;">
            <i class="fas fa-${color.icon}" style="font-size: 1.5rem; margin-top: 0.2rem;"></i>
            <div style="flex: 1;">
                <strong style="display: block; margin-bottom: 0.25rem;">${title}</strong>
                <p style="margin: 0;">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem; padding: 0;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

/**
 * Print functionality enhancement
 */
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});

// Expose functions to global scope
window.cancelBooking = cancelBooking;
window.downloadReceipt = downloadReceipt;