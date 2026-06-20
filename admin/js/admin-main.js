// Main Admin JavaScript - Mavic's Resort
// This file contains common functionality used across admin pages

// Session management
let sessionCheckInterval;

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminInterface();
    startSessionCheck();
});

function initializeAdminInterface() {
    // Initialize sidebar functionality
    initSidebar();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize form enhancements
    initFormEnhancements();
    
    // Initialize notification system
    initNotifications();
}

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-open');
            }
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 1024;
        if (isMobile && sidebar.classList.contains('open')) {
            if (!sidebar.contains(event.target)) {
                sidebar.classList.remove('open');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-open');
                }
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('open');
            if (mainContent) {
                mainContent.classList.remove('sidebar-open');
            }
        }
    });
}

function initTooltips() {
    // Simple tooltip implementation
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.title;
    if (!tooltipText) return;
    
    // Create tooltip element
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.position = 'absolute';
    tooltip.style.background = '#333';
    tooltip.style.color = '#fff';
    tooltip.style.padding = '5px 8px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '9999';
    tooltip.style.whiteSpace = 'nowrap';
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - 30) + 'px';
    
    // Add to body
    document.body.appendChild(tooltip);
    
    // Store reference for cleanup
    element._tooltip = tooltip;
    
    // Remove original title to prevent browser tooltip
    element._originalTitle = element.title;
    element.title = '';
}

function hideTooltip(event) {
    const element = event.target;
    if (element._tooltip) {
        document.body.removeChild(element._tooltip);
        element._tooltip = null;
    }
    // Restore original title
    if (element._originalTitle) {
        element.title = element._originalTitle;
        element._originalTitle = null;
    }
}

function initFormEnhancements() {
    // Add floating label effect to form inputs
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        if (input.type !== 'checkbox' && input.type !== 'radio') {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
            
            // Check initial value
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        }
    });
}

function initNotifications() {
    // Check for URL parameters that indicate success/error messages
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('success')) {
        showNotification(decodeURIComponent(urlParams.get('success')), 'success');
    }
    
    if (urlParams.get('error')) {
        showNotification(decodeURIComponent(urlParams.get('error')), 'error');
    }
    
    // Clean URL
    if (urlParams.has('success') || urlParams.has('error')) {
        const newUrl = window.location.pathname + '?' + 
            Array.from(urlParams.entries())
                .filter(([key]) => key !== 'success' && key !== 'error')
                .map(([key, value]) => `${key}=${value}`)
                .join('&');
        window.history.replaceState({}, '', newUrl);
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="hideNotification(this.parentElement.parentElement)">&times;</button>
        </div>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto hide
    if (duration > 0) {
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
    }
    
    return notification;
}

function hideNotification(notification) {
    notification.style.transform = 'translateX(100%)';
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 300);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    return colors[type] || colors.info;
}

function startSessionCheck() {
    // Check session every 5 minutes
    sessionCheckInterval = setInterval(checkSession, 5 * 60 * 1000);
}

function checkSession() {
    fetch('check-session.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.valid) {
            // Session expired
            clearInterval(sessionCheckInterval);
            showSessionExpiredModal();
        }
    })
    .catch(error => {
        console.error('Session check failed:', error);
    });
}

function showSessionExpiredModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay show';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Session Expired</h3>
            </div>
            <div class="modal-body">
                <p>Your session has expired. Please login again to continue.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="window.location.href='admin-login.php'">
                    Go to Login
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-PH', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Export functions for use in other scripts
window.AdminUtils = {
    showNotification,
    hideNotification,
    formatCurrency,
    formatDate,
    formatTime,
    checkSession
};



document.addEventListener('DOMContentLoaded', function() {
    initializeAdminInterface();
    startSessionCheck();
    initTopbarDropdowns(); // Initialize topbar dropdowns
});

function initializeAdminInterface() {
    // Initialize sidebar functionality
    initSidebar();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize form enhancements
    initFormEnhancements();
    
    // Initialize notification system
    initNotifications();
    
    // Initialize topbar dropdowns
    initTopbarDropdowns();
}

function initTopbarDropdowns() {
    // Profile dropdown functionality
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
            profileBtn.classList.toggle('active');
            
            // Close notifications dropdown if open
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            if (notificationsDropdown.classList.contains('show')) {
                notificationsDropdown.classList.remove('show');
            }
        });
    }
    
    // Notifications dropdown functionality
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    
    if (notificationBtn && notificationsDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('show');
            
            // Close profile dropdown if open
            if (profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                profileBtn.classList.remove('active');
            }
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (profileDropdown && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
            profileBtn.classList.remove('active');
        }
        
        if (notificationsDropdown && !notificationBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    // Close dropdowns with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (profileDropdown && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                profileBtn.classList.remove('active');
            }
            
            if (notificationsDropdown && notificationsDropdown.classList.contains('show')) {
                notificationsDropdown.classList.remove('show');
            }
        }
    });
}