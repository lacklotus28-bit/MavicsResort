// Profile Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile Page Loaded');
    console.log('User Data:', window.userData);
    
    // Initialize all features
    initializeTabNavigation();
    initializePersonalInfoForm();
    initializePasswordForm();
    initializePasswordStrength();
    loadActivityLog();
});

/**
 * Initialize tab navigation
 */
function initializeTabNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            // Update active states
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Show target tab
            tabContents.forEach(tab => tab.classList.remove('active'));
            document.getElementById(targetTab + '-tab').classList.add('active');
            
            // Update URL hash
            window.location.hash = targetTab;
        });
    });
    
    // Handle initial hash
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetNav = document.querySelector(`[data-tab="${hash}"]`);
        if (targetNav) {
            targetNav.click();
        }
    }
}

/**
 * Initialize personal info form
 */
function initializePersonalInfoForm() {
    const form = document.getElementById('personal-info-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Show loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // Send update request
        fetch('api/update-profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Success', 'Profile updated successfully!', 'success');
                // Update session data if name changed
                if (data.user) {
                    window.userData.firstName = data.user.first_name;
                    window.userData.lastName = data.user.last_name;
                    // Update header display if exists
                    updateHeaderName();
                }
            } else {
                throw new Error(data.message || 'Failed to update profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', error.message || 'Failed to update profile', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}

/**
 * Initialize password change form
 */
function initializePasswordForm() {
    const form = document.getElementById('change-password-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validate passwords match
        if (newPassword !== confirmPassword) {
            showAlert('Error', 'New passwords do not match', 'error');
            return;
        }
        
        // Validate password strength
        if (newPassword.length < 8) {
            showAlert('Error', 'Password must be at least 8 characters long', 'error');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
        
        fetch('api/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Success', 'Password changed successfully!', 'success');
                form.reset();
                // Reset password strength indicator
                document.querySelector('.strength-bar').className = 'strength-bar';
            } else {
                throw new Error(data.message || 'Failed to change password');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', error.message || 'Failed to change password', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}


/**
 * Initialize password strength indicator
 */
function initializePasswordStrength() {
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.querySelector('.strength-bar');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthBar.className = 'strength-bar';
            if (strength > 0) {
                if (strength < 40) {
                    strengthBar.classList.add('weak');
                } else if (strength < 70) {
                    strengthBar.classList.add('medium');
                } else {
                    strengthBar.classList.add('strong');
                }
            }
        });
    }
}

/**
 * Calculate password strength
 */
function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 20;
    if (password.length >= 12) strength += 20;
    if (/[a-z]/.test(password)) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 10;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
    
    return strength;
}

/**
 * Toggle password visibility
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password i');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.classList.remove('fa-eye');
        button.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        button.classList.remove('fa-eye-slash');
        button.classList.add('fa-eye');
    }
}

/**
 * Load activity log
 */
function loadActivityLog() {
    const activityList = document.getElementById('activity-list');
    
    fetch('api/get-activity-log.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities) {
                if (data.activities.length === 0) {
                    activityList.innerHTML = `
                        <div class="empty-state" style="padding: 3rem; text-align: center;">
                            <div class="empty-icon" style="width: 100px; height: 100px; background: rgba(139, 69, 19, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-history" style="font-size: 3rem; color: #D2691E;"></i>
                            </div>
                            <h3>No Activity Yet</h3>
                            <p style="color: #6b7280;">Your recent activity will appear here</p>
                        </div>
                    `;
                } else {
                    let html = '';
                    data.activities.forEach(activity => {
                        html += createActivityItem(activity);
                    });
                    activityList.innerHTML = html;
                }
            } else {
                throw new Error('Failed to load activity log');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            activityList.innerHTML = `
                <div class="empty-state" style="padding: 3rem; text-align: center; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3>Failed to Load Activity</h3>
                    <p>Please try refreshing the page</p>
                </div>
            `;
        });
}

/**
 * Create activity item HTML
 */
function createActivityItem(activity) {
    const icons = {
        'login': 'fa-sign-in-alt',
        'logout': 'fa-sign-out-alt',
        'booking': 'fa-calendar-check',
        'payment': 'fa-credit-card',
        'profile_update': 'fa-user-edit',
        'password_change': 'fa-key'
    };
    
    const icon = icons[activity.type] || 'fa-info-circle';
    
    return `
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="activity-content">
                <h4>${activity.title}</h4>
                <p>${activity.description}</p>
                <span class="activity-time">${activity.time_ago}</span>
            </div>
        </div>
    `;
}

/**
 * Reset form to original values
 */
function resetForm(formId) {
    const form = document.getElementById(formId);
    form.reset();
    showAlert('Info', 'Form has been reset to original values', 'info');
}

/**
 * Update header name
 */
function updateHeaderName() {
    const headerName = document.querySelector('.profile-header-info h1');
    if (headerName && window.userData) {
        headerName.textContent = `${window.userData.firstName} ${window.userData.lastName}`;
    }
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

// Expose functions to global scope
window.togglePassword = togglePassword;
window.resetForm = resetForm;