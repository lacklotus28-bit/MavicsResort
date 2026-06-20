// Global JavaScript Functions

// Authentication state management
class AuthManager {
    static isLoggedIn() {
        return sessionStorage.getItem('user_logged_in') === 'true';
    }
    
    static getUserData() {
        const userData = sessionStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    }
    
    static setUserSession(userData) {
        sessionStorage.setItem('user_logged_in', 'true');
        sessionStorage.setItem('user_data', JSON.stringify(userData));
    }
    
    static logout() {
        sessionStorage.removeItem('user_logged_in');
        sessionStorage.removeItem('user_data');
        window.location.href = 'index.php';
    }
    
    static requireAuth() {
        if (!this.isLoggedIn()) {
            showAlert('Please login to access this feature.', 'warning');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
            return false;
        }
        return true;
    }
}



// Utility Functions
function showAlert(message, type = 'info', duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-popup');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-popup`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        max-width: 500px;
        animation: slideInRight 0.3s ease-out;
    `;
    
    alert.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none; 
                border: none; 
                font-size: 1.2em; 
                cursor: pointer; 
                margin-left: 10px;
                opacity: 0.7;
            ">&times;</button>
        </div>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }, duration);
    }
}

// Loading state management
function showLoading(element, text = 'Loading...') {
    const originalContent = element.innerHTML;
    element.setAttribute('data-original-content', originalContent);
    element.innerHTML = `<span class="loading-spinner"></span> ${text}`;
    element.disabled = true;
}

function hideLoading(element) {
    const originalContent = element.getAttribute('data-original-content');
    if (originalContent) {
        element.innerHTML = originalContent;
        element.removeAttribute('data-original-content');
    }
    element.disabled = false;
}

// Form validation
function validateForm(formElement) {
    const requiredFields = formElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.classList.remove('error');
            field.style.borderColor = '';
        }
    });
    
    // Email validation
    const emailFields = formElement.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            field.classList.add('error');
            field.style.borderColor = '#dc3545';
            isValid = false;
        }
    });
    
    // Phone validation
    const phoneFields = formElement.querySelectorAll('input[type="tel"], input[name*="phone"]');
    phoneFields.forEach(field => {
        if (field.value && !isValidPhone(field.value)) {
            field.classList.add('error');
            field.style.borderColor = '#dc3545';
            isValid = false;
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^(\+63|0)?[0-9]{10}$/;
    return phoneRegex.test(phone.replace(/\s|-/g, ''));
}

// Date utilities
function formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    switch(format) {
        case 'YYYY-MM-DD':
            return `${year}-${month}-${day}`;
        case 'DD/MM/YYYY':
            return `${day}/${month}/${year}`;
        case 'MMM DD, YYYY':
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[d.getMonth()]} ${day}, ${year}`;
        default:
            return date;
    }
}

function getTodayDate() {
    return new Date().toISOString().split('T')[0];
}

function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

// Currency formatting
function formatCurrency(amount, currency = 'PHP') {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
    }).format(amount);
}

// Smooth scroll to element
function scrollTo(elementId, offset = 0) {
    const element = document.getElementById(elementId);
    if (element) {
        const targetPosition = element.offsetTop - offset;
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
}

// Modal handling
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        modal.style.animation = 'fadeIn 0.3s ease-out forwards';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease-in forwards';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Image lazy loading
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// AJAX helper function
async function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data) {
        if (method === 'GET') {
            const params = new URLSearchParams(data);
            url += '?' + params.toString();
        } else {
            options.body = JSON.stringify(data);
        }
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Request failed');
        }
        
        return result;
    } catch (error) {
        console.error('Request error:', error);
        throw error;
    }
}

// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: var(--dark-brown);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.9rem;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
    
    setTimeout(() => tooltip.style.opacity = '1', 10);
    
    element._tooltip = tooltip;
}

function hideTooltip(event) {
    const element = event.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

// CSS Animations
const CSS_ANIMATIONS = `
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.9); }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes slideInUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
`;

// Initialize global functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add CSS animations to head
    const style = document.createElement('style');
    style.textContent = CSS_ANIMATIONS;
    document.head.appendChild(style);
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize lazy loading
    lazyLoadImages();
    
    // Update user interface based on login status
    updateUIForAuthState();
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            const modalId = e.target.id;
            closeModal(modalId);
        }
    });
    
    // Handle form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM' && !form.classList.contains('no-validation')) {
            if (!validateForm(form)) {
                e.preventDefault();
                showAlert('Please fill in all required fields correctly.', 'error');
            }
        }
    });
});

// Update UI elements based on authentication state
function updateUIForAuthState() {
    const isLoggedIn = AuthManager.isLoggedIn();
    const userData = AuthManager.getUserData();
    
    // Update navigation
    const loginLinks = document.querySelectorAll('.login-required');
    const guestLinks = document.querySelectorAll('.guest-only');
    const userInfo = document.querySelectorAll('.user-info');
    
    loginLinks.forEach(link => {
        link.style.display = isLoggedIn ? 'block' : 'none';
    });
    
    guestLinks.forEach(link => {
        link.style.display = isLoggedIn ? 'none' : 'block';
    });
    
    if (isLoggedIn && userData) {
        userInfo.forEach(element => {
            element.textContent = `Welcome, ${userData.first_name}!`;
            element.style.display = 'block';
        });
    }
}