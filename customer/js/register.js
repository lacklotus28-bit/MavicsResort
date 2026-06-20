// Registration Page JavaScript - Mavic's Resort

// Initialize registration page functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeRegistrationForm();
    initializePasswordToggles();
    initializeFormValidation();
    initializePasswordStrength();
    initializeAnimations();
    checkForMessages();
});

// Form elements
let registerForm;
let firstNameInput;
let lastNameInput;
let emailInput;
let phoneInput;
let dateOfBirthInput;
let addressInput;
let passwordInput;
let confirmPasswordInput;
let passwordToggle;
let confirmPasswordToggle;
let termsCheckbox;
let registerBtn;
let alertContainer;
let strengthFill;
let strengthText;

// Initialize registration form
function initializeRegistrationForm() {
    registerForm = document.getElementById('registerForm');
    firstNameInput = document.getElementById('first_name');
    lastNameInput = document.getElementById('last_name');
    emailInput = document.getElementById('email');
    phoneInput = document.getElementById('phone');
    dateOfBirthInput = document.getElementById('date_of_birth');
    addressInput = document.getElementById('address');
    passwordInput = document.getElementById('password');
    confirmPasswordInput = document.getElementById('confirm_password');
    passwordToggle = document.getElementById('passwordToggle');
    confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
    termsCheckbox = document.getElementById('terms');
    registerBtn = document.getElementById('registerBtn');
    alertContainer = document.getElementById('alert-container');
    strengthFill = document.getElementById('strengthFill');
    strengthText = document.getElementById('strengthText');

    if (registerForm) {
        registerForm.addEventListener('submit', handleFormSubmit);
    }

    // Auto-focus first field
    if (firstNameInput) {
        firstNameInput.focus();
    }
}

// Initialize password toggle functionality
function initializePasswordToggles() {
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, passwordToggle);
        });
    }

    if (confirmPasswordToggle && confirmPasswordInput) {
        confirmPasswordToggle.addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordInput, confirmPasswordToggle);
        });
    }
}

// Toggle password visibility
function togglePasswordVisibility(input, toggle) {
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    
    const icon = toggle.querySelector('i');
    if (icon) {
        icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
    
    toggle.setAttribute('aria-label', 
        isPassword ? 'Hide password' : 'Show password'
    );
    
    toggle.style.transform = 'scale(0.9)';
    setTimeout(() => {
        toggle.style.transform = 'scale(1)';
    }, 100);
}

// Initialize form validation
function initializeFormValidation() {
    if (!registerForm) return;

    firstNameInput?.addEventListener('blur', () => validateField('first_name'));
    firstNameInput?.addEventListener('input', () => clearFieldError('first_name'));
    
    lastNameInput?.addEventListener('blur', () => validateField('last_name'));
    lastNameInput?.addEventListener('input', () => clearFieldError('last_name'));
    
    emailInput?.addEventListener('blur', () => validateField('email'));
    emailInput?.addEventListener('input', () => clearFieldError('email'));
    
    phoneInput?.addEventListener('blur', () => validateField('phone'));
    phoneInput?.addEventListener('input', () => clearFieldError('phone'));
    
    dateOfBirthInput?.addEventListener('blur', () => validateField('date_of_birth'));
    dateOfBirthInput?.addEventListener('input', () => clearFieldError('date_of_birth'));
    
    addressInput?.addEventListener('blur', () => validateField('address'));
    addressInput?.addEventListener('input', () => clearFieldError('address'));
    
    passwordInput?.addEventListener('blur', () => validateField('password'));
    passwordInput?.addEventListener('input', () => {
        clearFieldError('password');
        updatePasswordStrength();
    });
    
    confirmPasswordInput?.addEventListener('blur', () => validateField('confirm_password'));
    confirmPasswordInput?.addEventListener('input', () => clearFieldError('confirm_password'));
    
    termsCheckbox?.addEventListener('change', () => validateField('terms'));

    const inputs = [firstNameInput, lastNameInput, emailInput, phoneInput, dateOfBirthInput, addressInput, passwordInput, confirmPasswordInput];
    inputs.forEach((input, index) => {
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && input.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                    } else {
                        registerForm.dispatchEvent(new Event('submit'));
                    }
                }
            });
        }
    });
}

// Initialize password strength meter
function initializePasswordStrength() {
    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordStrength);
    }
}

// Update password strength indicator
function updatePasswordStrength() {
    const password = passwordInput.value;
    const strength = calculatePasswordStrength(password);
    
    if (!strengthFill || !strengthText) return;
    
    strengthFill.classList.remove('weak', 'fair', 'good', 'strong');
    strengthText.classList.remove('weak', 'fair', 'good', 'strong');
    
    if (password.length === 0) {
        strengthFill.style.width = '0%';
        strengthText.textContent = 'Enter password';
        return;
    }
    
    switch (strength.level) {
        case 'weak':
            strengthFill.classList.add('weak');
            strengthText.classList.add('weak');
            strengthText.textContent = 'Weak';
            break;
        case 'fair':
            strengthFill.classList.add('fair');
            strengthText.classList.add('fair');
            strengthText.textContent = 'Fair';
            break;
        case 'good':
            strengthFill.classList.add('good');
            strengthText.classList.add('good');
            strengthText.textContent = 'Good';
            break;
        case 'strong':
            strengthFill.classList.add('strong');
            strengthText.classList.add('strong');
            strengthText.textContent = 'Strong';
            break;
    }
}

// Calculate password strength
function calculatePasswordStrength(password) {
    let score = 0;
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        numbers: /\d/.test(password),
        symbols: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };
    
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (checks.lowercase) score++;
    if (checks.uppercase) score++;
    if (checks.numbers) score++;
    if (checks.symbols) score++;
    
    let level = 'weak';
    if (score >= 6) level = 'strong';
    else if (score >= 4) level = 'good';
    else if (score >= 2) level = 'fair';
    
    return { score, level, checks };
}

// Validate individual field
function validateField(fieldName) {
    const input = document.getElementById(fieldName);
    if (!input) {
        console.warn(`Field ${fieldName} not found in DOM`);
        return true;
    }
    
    let value;
    if (input.type === 'checkbox') {
        value = input.checked;
    } else {
        value = input.value.trim();
    }
    
    console.log(`Validating ${fieldName}: "${value}" (length: ${value.length})`);
    
    switch (fieldName) {
        case 'first_name':
        case 'last_name':
            return validateName(fieldName, value);
        case 'email':
            return validateEmail(value);
        case 'phone':
            return validatePhone(value);
        case 'date_of_birth':
            return validateDateOfBirth(value);
        case 'address':
            return validateAddress(value);
        case 'password':
            return validatePassword(value);
        case 'confirm_password':
            return validateConfirmPassword(value);
        case 'terms':
            return validateTerms();
        default:
            return true;
    }
}

// Validate name fields
function validateName(fieldName, value) {
    const displayName = fieldName.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    if (!value || value.length === 0) {
        showFieldError(fieldName, `${displayName} is required`);
        return false;
    }
    
    if (value.length < 2) {
        showFieldError(fieldName, `${displayName} must be at least 2 characters long`);
        return false;
    }
    
    if (!/^[a-zA-Z\s\-'\.]+$/.test(value)) {
        showFieldError(fieldName, `${displayName} contains invalid characters`);
        return false;
    }
    
    clearFieldError(fieldName);
    return true;
}

// Validate email field
function validateEmail(value) {
    if (!value) {
        showFieldError('email', 'Email address is required');
        return false;
    }
    
    if (!isValidEmail(value)) {
        showFieldError('email', 'Please enter a valid email address');
        return false;
    }
    
    clearFieldError('email');
    return true;
}

// Validate phone field
function validatePhone(value) {
    if (!value) {
        showFieldError('phone', 'Phone number is required');
        return false;
    }
    
    if (!isValidPhone(value)) {
        showFieldError('phone', 'Please enter a valid Philippine phone number (e.g., 09123456789)');
        return false;
    }
    
    clearFieldError('phone');
    return true;
}

// Validate date of birth field
function validateDateOfBirth(value) {
    if (!value) {
        showFieldError('date_of_birth', 'Date of birth is required');
        return false;
    }
    
    const birthDate = new Date(value);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    if (birthDate > today) {
        showFieldError('date_of_birth', 'Date of birth cannot be in the future');
        return false;
    }
    
    if (age < 18) {
        showFieldError('date_of_birth', 'You must be at least 18 years old to register');
        return false;
    }
    
    if (age > 120) {
        showFieldError('date_of_birth', 'Please enter a valid date of birth');
        return false;
    }
    
    clearFieldError('date_of_birth');
    return true;
}

// Validate address field
function validateAddress(value) {
    if (!value || value.length === 0) {
        showFieldError('address', 'Address is required');
        return false;
    }
    
    if (value.length < 10) {
        showFieldError('address', 'Please enter a complete address');
        return false;
    }
    
    if (value.length > 500) {
        showFieldError('address', 'Address is too long');
        return false;
    }
    
    clearFieldError('address');
    return true;
}

// Validate password field
function validatePassword(value) {
    if (!value) {
        showFieldError('password', 'Password is required');
        return false;
    }
    
    if (value.length < 8) {
        showFieldError('password', 'Password must be at least 8 characters long');
        return false;
    }
    
    const strength = calculatePasswordStrength(value);
    if (strength.level === 'weak' && value.length >= 8) {
        showFieldError('password', 'Password is too weak. Please include uppercase, lowercase, numbers, and symbols');
        return false;
    }
    
    clearFieldError('password');
    return true;
}

// Validate confirm password field
function validateConfirmPassword(value) {
    const passwordValue = passwordInput?.value || '';
    
    if (!value) {
        showFieldError('confirm_password', 'Please confirm your password');
        return false;
    }
    
    if (value !== passwordValue) {
        showFieldError('confirm_password', 'Passwords do not match');
        return false;
    }
    
    clearFieldError('confirm_password');
    return true;
}

// Validate terms checkbox
function validateTerms() {
    if (!termsCheckbox?.checked) {
        showFieldError('terms', 'You must agree to the Terms of Service and Privacy Policy');
        return false;
    }
    
    clearFieldError('terms');
    return true;
}

// Show field error
function showFieldError(fieldName, message) {
    const errorElement = document.getElementById(fieldName + '-error');
    const inputElement = document.getElementById(fieldName);
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.animation = 'fadeIn 0.3s ease-out';
    }
    
    if (inputElement && inputElement.type !== 'checkbox') {
        inputElement.classList.add('error');
        inputElement.style.borderColor = '#dc3545';
        inputElement.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    }
}

// Clear field error
function clearFieldError(fieldName) {
    const errorElement = document.getElementById(fieldName + '-error');
    const inputElement = document.getElementById(fieldName);
    
    if (errorElement) {
        errorElement.textContent = '';
    }
    
    if (inputElement && inputElement.type !== 'checkbox') {
        inputElement.classList.remove('error');
        inputElement.style.borderColor = '';
        inputElement.style.boxShadow = '';
    }
}

// Handle form submission
async function handleFormSubmit(event) {
    event.preventDefault();
    
    console.log('=== FORM SUBMISSION STARTED ===');
    
    clearAlerts();
    
    // Get fresh values from inputs
    const formValues = {
        first_name: firstNameInput?.value?.trim() || '',
        last_name: lastNameInput?.value?.trim() || '',
        email: emailInput?.value?.trim() || '',
        phone: phoneInput?.value?.trim() || '',
        date_of_birth: dateOfBirthInput?.value || '',
        address: addressInput?.value?.trim() || '',
        password: passwordInput?.value || '',
        confirm_password: confirmPasswordInput?.value || '',
        terms: termsCheckbox?.checked || false
    };
    
    console.log('Current form values:', {
        first_name: formValues.first_name,
        last_name: formValues.last_name,
        email: formValues.email,
        phone: formValues.phone,
        date_of_birth: formValues.date_of_birth,
        address: formValues.address ? '[PRESENT]' : '[EMPTY]',
        password: formValues.password ? '[PRESENT]' : '[EMPTY]',
        confirm_password: formValues.confirm_password ? '[PRESENT]' : '[EMPTY]',
        terms: formValues.terms
    });
    
    // Validate all fields
    const validations = [
        validateField('first_name'),
        validateField('last_name'),
        validateField('email'),
        validateField('phone'),
        validateField('date_of_birth'),
        validateField('address'),
        validateField('password'),
        validateField('confirm_password'),
        validateField('terms')
    ];
    
    console.log('Validation results:', validations);
    
    const isValid = validations.every(result => result);
    
    if (!isValid) {
        showAlert('Please correct the errors below.', 'error');
        const firstInvalidIndex = validations.findIndex(result => !result);
        const fieldNames = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'address', 'password', 'confirm_password', 'terms'];
        if (firstInvalidIndex !== -1) {
            const firstInvalidField = document.getElementById(fieldNames[firstInvalidIndex]);
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }
        return;
    }
    
    setLoadingState(true);
    
    try {
        // Create FormData and explicitly append values
        const formData = new FormData();
        formData.append('first_name', formValues.first_name);
        formData.append('last_name', formValues.last_name);
        formData.append('email', formValues.email);
        formData.append('phone', formValues.phone);
        formData.append('date_of_birth', formValues.date_of_birth);
        formData.append('address', formValues.address);
        formData.append('password', formValues.password);
        formData.append('confirm_password', formValues.confirm_password);
        if (formValues.terms) {
            formData.append('terms', '1');
        }
        
        console.log('Sending FormData:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${key.includes('password') ? '[HIDDEN]' : value}`);
        }
        
        const response = await fetch('process/register_process.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        // Get response text first
        const responseText = await response.text();
        console.log('Raw response:', responseText.substring(0, 500));
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was not valid JSON. First 500 chars:', responseText.substring(0, 500));
            throw new Error('Server returned invalid response. Please check the error log.');
        }
        console.log('Server response:', result);
        
        if (result.success) {
            showAlert(result.message || 'Account created successfully! Redirecting to dashboard...', 'success');
            
            registerForm.reset();
            updatePasswordStrength();
            
            setTimeout(() => {
                window.location.href = result.redirect || 'dashboard.php';
            }, 2000);
            
        } else {
            showAlert(result.message || 'Registration failed. Please try again.', 'error');
            
            if (result.field) {
                const field = document.getElementById(result.field);
                if (field) {
                    field.focus();
                    field.select();
                }
            }
        }
        
    } catch (error) {
        console.error('Registration error:', error);
        showAlert('A network error occurred. Please check your connection and try again.', 'error');
    } finally {
        setLoadingState(false);
        console.log('=== FORM SUBMISSION ENDED ===');
    }
}

// Set loading state
function setLoadingState(isLoading) {
    if (!registerBtn) return;
    
    if (isLoading) {
        registerBtn.classList.add('loading');
        registerBtn.disabled = true;
        
        const inputs = registerForm?.querySelectorAll('input, button, select, textarea');
        inputs?.forEach(input => {
            if (input !== registerBtn) {
                input.disabled = true;
            }
        });
        
    } else {
        registerBtn.classList.remove('loading');
        registerBtn.disabled = false;
        
        const inputs = registerForm?.querySelectorAll('input, button, select, textarea');
        inputs?.forEach(input => {
            input.disabled = false;
        });
    }
}

// Show alert message
function showAlert(message, type = 'info', duration = 5000) {
    if (!alertContainer) return;
    
    clearAlerts();
    
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
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    const animatedElements = document.querySelectorAll('[style*="animation"]');
    animatedElements.forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });
    
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
}

// Check for messages from URL parameters
function checkForMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type');
    
    if (message) {
        showAlert(decodeURIComponent(message), messageType || 'info');
        
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const cleanPhone = phone.replace(/\s|-|\(|\)/g, '');
    const phoneRegex = /^(\+63|63|0)?9[0-9]{9}$/;
    return phoneRegex.test(cleanPhone);
}

// Add keyboard accessibility
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        clearAlerts();
    }
    
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

// Export functions for global access
window.RegisterPage = {
    showAlert,
    clearAlerts,
    validateField,
    setLoadingState,
    calculatePasswordStrength
};
