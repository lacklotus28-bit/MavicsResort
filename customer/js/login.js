// Login Page JavaScript - Mavic's Resort (Updated with better error handling)

// Initialize login page functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeLoginForm();
    initializePasswordToggle();
    initializeFormValidation();
    initializeAnimations();
    checkForMessages();
});

// Form elements
let loginForm;
let emailInput;
let passwordInput;
let passwordToggle;
let loginBtn;
let alertContainer;

// Initialize login form
function initializeLoginForm() {
    loginForm = document.getElementById('loginForm');
    emailInput = document.getElementById('email');
    passwordInput = document.getElementById('password');
    passwordToggle = document.getElementById('passwordToggle');
    loginBtn = document.getElementById('loginBtn');
    alertContainer = document.getElementById('alert-container');

    // Debug: Check if elements are found
    console.log('Form elements check:', {
        loginForm: !!loginForm,
        emailInput: !!emailInput,
        passwordInput: !!passwordInput,
        loginBtn: !!loginBtn,
        alertContainer: !!alertContainer
    });

    if (loginForm) {
        loginForm.addEventListener('submit', handleFormSubmit);
    }

    // Auto-focus first field with delay to ensure DOM is ready
    setTimeout(() => {
        if (emailInput) {
            emailInput.focus();
        }
    }, 100);

    // Handle return URL from session
    const urlParams = new URLSearchParams(window.location.search);
    const returnUrl = urlParams.get('return');
    if (returnUrl) {
        // Store return URL in session storage for use after login
        sessionStorage.setItem('login_return_url', decodeURIComponent(returnUrl));
        showAlert('Please login to continue to your requested page.', 'info');
    }
}

// Initialize password toggle functionality
function initializePasswordToggle() {
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            const icon = passwordToggle.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            }
            
            // Accessibility
            passwordToggle.setAttribute('aria-label', 
                isPassword ? 'Hide password' : 'Show password'
            );
            
            // Animate the toggle
            passwordToggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                passwordToggle.style.transform = 'scale(1)';
            }, 100);
        });
    }
}

// Initialize form validation
function initializeFormValidation() {
    if (!emailInput || !passwordInput) {
        console.error('Email or password input not found');
        return;
    }

    // Real-time validation - removed blur validation to prevent premature errors
    emailInput.addEventListener('input', function() {
        clearFieldError('email');
        // Optional: Show validation status while typing
        if (this.value.trim() && !isValidEmail(this.value.trim())) {
            // Don't show error immediately, just indicate invalid state
            this.style.borderColor = '#ffc107';
        } else if (this.value.trim() && isValidEmail(this.value.trim())) {
            this.style.borderColor = '#28a745';
        } else {
            this.style.borderColor = '';
        }
    });

    passwordInput.addEventListener('input', function() {
        clearFieldError('password');
        // Optional: Show validation status while typing
        if (this.value.length > 0 && this.value.length < 6) {
            this.style.borderColor = '#ffc107';
        } else if (this.value.length >= 6) {
            this.style.borderColor = '#28a745';
        } else {
            this.style.borderColor = '';
        }
    });

    // Enter key navigation
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            passwordInput.focus();
        }
    });

    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (loginForm) {
                loginForm.dispatchEvent(new Event('submit'));
            }
        }
    });
}

// Validate email field with improved error handling
function validateEmail() {
    if (!emailInput) {
        console.error('Email input element not found during validation');
        return false;
    }
    
    const email = emailInput.value ? emailInput.value.trim() : '';
    console.log('Validating email:', email, 'Original value:', emailInput.value); // Debug
    
    if (!email || email.length === 0) {
        showFieldError('email', 'Email address is required');
        return false;
    }
    
    if (!isValidEmail(email)) {
        showFieldError('email', 'Please enter a valid email address');
        return false;
    }
    
    clearFieldError('email');
    return true;
}

// Validate password field with improved error handling
function validatePassword() {
    if (!passwordInput) {
        console.error('Password input element not found during validation');
        return false;
    }
    
    const password = passwordInput.value || '';
    console.log('Validating password length:', password.length); // Debug
    
    if (!password || password.length === 0) {
        showFieldError('password', 'Password is required');
        return false;
    }
    
    if (password.length < 6) {
        showFieldError('password', 'Password must be at least 6 characters long');
        return false;
    }
    
    clearFieldError('password');
    return true;
}

// Show field error with improved handling
function showFieldError(fieldName, message) {
    const errorElement = document.getElementById(fieldName + '-error');
    const inputElement = document.getElementById(fieldName);
    
    console.log('Showing field error:', fieldName, message); // Debug
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.animation = 'none'; // Reset animation
        errorElement.offsetHeight; // Trigger reflow
        errorElement.style.animation = 'fadeIn 0.3s ease-out';
    } else {
        console.error('Error element not found:', fieldName + '-error');
    }
    
    if (inputElement) {
        inputElement.classList.add('error');
        inputElement.style.borderColor = '#dc3545';
        inputElement.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    } else {
        console.error('Input element not found:', fieldName);
    }
}

// Clear field error
function clearFieldError(fieldName) {
    const errorElement = document.getElementById(fieldName + '-error');
    const inputElement = document.getElementById(fieldName);
    
    if (errorElement) {
        errorElement.textContent = '';
    }
    
    if (inputElement) {
        inputElement.classList.remove('error');
        inputElement.style.borderColor = '';
        inputElement.style.boxShadow = '';
    }
}

// Handle form submission with improved validation
async function handleFormSubmit(event) {
    event.preventDefault();
    
    console.log('Form submission started'); // Debug
    
    // Clear any existing alerts
    clearAlerts();
    
    // Double-check that form elements are available
    if (!emailInput || !passwordInput) {
        console.error('Form elements not available during submission');
        showAlert('Form initialization error. Please refresh the page.', 'error');
        return;
    }
    
    // Log current values for debugging
    console.log('Current form values:', {
        email: emailInput.value,
        emailTrimmed: emailInput.value ? emailInput.value.trim() : '',
        password: passwordInput.value ? '[' + passwordInput.value.length + ' chars]' : 'empty'
    });
    
    // Validate form with detailed logging
    const isEmailValid = validateEmail();
    const isPasswordValid = validatePassword();
    
    console.log('Validation results:', { isEmailValid, isPasswordValid });
    
    if (!isEmailValid || !isPasswordValid) {
        showAlert('Please correct the errors below.', 'error');
        
        // Focus on first invalid field
        if (!isEmailValid && emailInput) {
            emailInput.focus();
        } else if (!isPasswordValid && passwordInput) {
            passwordInput.focus();
        }
        return;
    }
    
    // Show loading state
    setLoadingState(true);
    
    try {
        // Get form values directly instead of using FormData
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        
        console.log('Direct form values:', { email, password: password ? '[' + password.length + ' chars]' : 'empty' });
        
        // Create URLSearchParams for form submission
        const params = new URLSearchParams();
        params.append('email', email);
        params.append('password', password);
        
        console.log('Sending params:', params.toString());
        
        // Send login request
        const response = await fetch('process/login_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Login response:', result); // Debug
        
        if (result.success) {
            // Login successful
            showAlert('Login successful! Redirecting...', 'success');
            
            // Store user session data
            if (result.user) {
                AuthManager.setUserSession(result.user);
            }
            
            // Redirect after short delay
            setTimeout(() => {
                const returnUrl = sessionStorage.getItem('login_return_url');
                if (returnUrl) {
                    sessionStorage.removeItem('login_return_url');
                    window.location.href = returnUrl;
                } else {
                    window.location.href = result.redirect || 'index.php';
                }
            }, 1500);
            
        } else {
            // Login failed
            const errorMessage = result.message || 'Invalid email or password. Please try again.';
            showAlert(errorMessage, 'error');
            
            // Focus on email field for retry
            if (emailInput) {
                setTimeout(() => {
                    emailInput.focus();
                    emailInput.select();
                }, 100);
            }
        }
        
    } catch (error) {
        console.error('Login error:', error);
        
        let errorMessage = 'A network error occurred. Please check your connection and try again.';
        
        if (error.message.includes('JSON')) {
            errorMessage = 'Server response error. Please try again or contact support.';
        } else if (error.message.includes('fetch')) {
            errorMessage = 'Unable to connect to server. Please check your internet connection.';
        }
        
        showAlert(errorMessage, 'error');
    } finally {
        setLoadingState(false);
    }
}

// Set loading state
function setLoadingState(isLoading) {
    if (!loginBtn) {
        console.error('Login button not found');
        return;
    }
    
    if (isLoading) {
        loginBtn.classList.add('loading');
        loginBtn.disabled = true;
        
        // Disable form inputs
        if (emailInput) emailInput.disabled = true;
        if (passwordInput) passwordInput.disabled = true;
        
    } else {
        loginBtn.classList.remove('loading');
        loginBtn.disabled = false;
        
        // Re-enable form inputs
        if (emailInput) emailInput.disabled = false;
        if (passwordInput) passwordInput.disabled = false;
    }
}

// Show alert message
function showAlert(message, type = 'info', duration = 5000) {
    if (!alertContainer) {
        console.error('Alert container not found');
        // Fallback to browser alert
        alert(message);
        return;
    }
    
    // Remove existing alerts
    clearAlerts();
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none; 
                border: none; 
                font-size: 1.2em; 
                cursor: pointer; 
                margin-left: 15px;
                opacity: 0.7;
                color: inherit;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            ">&times;</button>
        </div>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto remove after duration (except for success messages which redirect)
    if (duration > 0 && type !== 'success') {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.animation = 'fadeOut 0.3s ease-in';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, duration);
    }
    
    // Scroll to alert if needed
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Clear all alerts
function clearAlerts() {
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
}

// Initialize animations
function initializeAnimations() {
    // Stagger form animations based on scroll position
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    // Observe animated elements
    const animatedElements = document.querySelectorAll('[style*="animation"]');
    animatedElements.forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });
    
    // Add hover effects to form elements
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            if (this.parentElement) {
                this.parentElement.classList.add('focused');
            }
        });
        
        control.addEventListener('blur', function() {
            if (this.parentElement) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
}

// Check for messages from URL parameters or session
function checkForMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type');
    
    if (message) {
        showAlert(decodeURIComponent(message), messageType || 'info');
        
        // Clean URL
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }
    
    // Check for session messages
    checkSessionMessages();
}

// Check for session messages
function checkSessionMessages() {
    // This would typically be populated by PHP session data
    // For now, we'll check if there are any messages in the page
    const sessionMessages = window.sessionMessages || [];
    
    sessionMessages.forEach(msg => {
        showAlert(msg.message, msg.type);
    });
}

// Utility function to validate email with improved regex
function isValidEmail(email) {
    // More comprehensive email validation
    const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    return emailRegex.test(email);
}

// Handle social login (placeholder functionality)
function handleSocialLogin(provider) {
    showAlert(`${provider} login is not available yet. Please use email and password.`, 'warning');
}

// Initialize social login buttons
document.addEventListener('DOMContentLoaded', function() {
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const provider = this.classList.contains('google-btn') ? 'Google' : 'Facebook';
            handleSocialLogin(provider);
        });
    });
});

// Add keyboard accessibility
document.addEventListener('keydown', function(event) {
    // Escape key to clear alerts
    if (event.key === 'Escape') {
        clearAlerts();
    }
    
    // Tab navigation improvements
    if (event.key === 'Tab') {
        const focusableElements = document.querySelectorAll(
            'input:not([disabled]), button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }
});

// Handle network connectivity
window.addEventListener('online', function() {
    showAlert('Connection restored.', 'success', 2000);
});

window.addEventListener('offline', function() {
    showAlert('No internet connection. Please check your network.', 'warning');
});

// Add form analytics (non-intrusive)
function trackFormInteraction(action, field = null) {
    // This could be used for analytics
    console.log(`Form interaction: ${action}`, field ? `Field: ${field}` : '');
    
    // Example: Track failed login attempts, successful logins, etc.
    // This would integrate with your analytics service
}

// Track form events
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (loginForm) {
            loginForm.addEventListener('submit', () => trackFormInteraction('submit_attempt'));
        }
        
        if (emailInput) {
            emailInput.addEventListener('focus', () => trackFormInteraction('focus', 'email'));
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('focus', () => trackFormInteraction('focus', 'password'));
        }
    }, 200);
});

// Create a simple AuthManager if it doesn't exist
if (typeof AuthManager === 'undefined') {
    window.AuthManager = {
        setUserSession: function(user) {
            console.log('Setting user session:', user);
            // Store user data in sessionStorage
            try {
                sessionStorage.setItem('user_data', JSON.stringify(user));
            } catch (e) {
                console.error('Failed to store user session:', e);
            }
        }
    };
}

// Export functions for global access
window.LoginPage = {
    showAlert,
    clearAlerts,
    validateEmail,
    validatePassword,
    setLoadingState
};