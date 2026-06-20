// Authentication Manager for Mavic's Resort
// Place this file as: js/auth.js

const AuthManager = {
    userSession: null,
    
    // Set user session data (called from header.php)
    setUserSession: function(userData) {
        this.userSession = userData;
        console.log('User session set:', userData);
    },
    
    // Check if user is logged in
    isLoggedIn: function() {
        return this.userSession !== null && this.userSession.id;
    },
    
    // Get current user data
    getCurrentUser: function() {
        return this.userSession;
    },
    
    // Require authentication - redirect to login if not logged in
    requireAuth: function(message = 'Please log in to access this feature.', returnUrl = null) {
        if (!this.isLoggedIn()) {
            // Get current URL for return after login
            const currentUrl = returnUrl || window.location.href;
            const loginUrl = `login.php?return=${encodeURIComponent(currentUrl)}`;
            
            // Show message and redirect
            if (typeof showAlert === 'function') {
                showAlert(message, 'warning', function() {
                    window.location.href = loginUrl;
                });
            } else {
                alert(message);
                window.location.href = loginUrl;
            }
            return false;
        }
        return true;
    },
    
    // Handle logout
    logout: function() {
        window.location.href = 'logout.php';
    },
    
    // Initialize auth manager
    init: function() {
        // Add auth-required click handlers
        document.addEventListener('click', function(e) {
            const element = e.target.closest('.login-required, [data-auth-required]');
            if (element && !AuthManager.isLoggedIn()) {
                e.preventDefault();
                const message = element.dataset.authMessage || 'Please log in to continue.';
                AuthManager.requireAuth(message);
            }
        });
        
        // Handle auth-required form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.classList.contains('login-required') || form.hasAttribute('data-auth-required')) {
                if (!AuthManager.isLoggedIn()) {
                    e.preventDefault();
                    const message = form.dataset.authMessage || 'Please log in to submit this form.';
                    AuthManager.requireAuth(message);
                }
            }
        });
    }
};

// Global authentication functions for backward compatibility
window.requireAuth = function(message, returnUrl) {
    return AuthManager.requireAuth(message, returnUrl);
};

window.isUserLoggedIn = function() {
    return AuthManager.isLoggedIn();
};

window.getCurrentUser = function() {
    return AuthManager.getCurrentUser();
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    AuthManager.init();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}