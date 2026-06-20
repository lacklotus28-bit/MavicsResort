// Admin Login JavaScript - Debug Version
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('adminLoginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    console.log('Form elements found:', {
        form: !!loginForm,
        username: !!usernameInput,
        password: !!passwordInput
    });
    
    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        clearErrors();
        
        // Validate username
        if (!usernameInput.value.trim()) {
            showError(usernameInput, 'Username is required');
            isValid = false;
        } else if (usernameInput.value.trim().length < 3) {
            showError(usernameInput, 'Username must be at least 3 characters');
            isValid = false;
        }
        
        // Validate password
        if (!passwordInput.value) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        } else if (passwordInput.value.length < 6) {
            showError(passwordInput, 'Password must be at least 6 characters');
            isValid = false;
        }
        
        console.log('Form validation result:', isValid);
        return isValid;
    }
    
    // Show error message
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        formGroup.classList.add('error');
        
        // Remove existing error message
        const existingError = formGroup.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Create and add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
        
        // Trigger animation
        setTimeout(() => {
            errorDiv.classList.add('show');
        }, 10);
    }
    
    // Clear all errors
    function clearErrors() {
        const errorGroups = document.querySelectorAll('.form-group.error');
        errorGroups.forEach(group => {
            group.classList.remove('error');
            const errorMessage = group.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
        
        // Also clear any existing success/error messages from PHP
        const existingMessages = document.querySelectorAll('.success-message:not(.form-group .success-message), .error-message:not(.form-group .error-message)');
        existingMessages.forEach(msg => {
            msg.remove();
        });
    }
    
    // Show success message
    function showSuccess(message) {
        console.log('Showing success message:', message);
        const existingSuccess = document.querySelector('.success-message:not(.form-group .success-message)');
        if (existingSuccess) {
            existingSuccess.remove();
        }
        
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        
        loginForm.insertBefore(successDiv, loginForm.firstChild);
        
        setTimeout(() => {
            successDiv.classList.add('show');
        }, 10);
    }
    
    // Show general error message
    function showGeneralError(message) {
        console.log('Showing error message:', message);
        const existingError = document.querySelector('.error-message:not(.form-group .error-message)');
        if (existingError) {
            existingError.remove();
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        loginForm.insertBefore(errorDiv, loginForm.firstChild);
        
        setTimeout(() => {
            errorDiv.classList.add('show');
        }, 10);
    }
    
    // Handle form submission
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        if (!validateForm()) {
            console.log('Form validation failed');
            return;
        }
        
        console.log('Form validation passed, proceeding with login');
        
        // Show loading state
        loginForm.classList.add('loading');
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;
        
        try {
            // Prepare form data
            const formData = new FormData(loginForm);
            console.log('Form data prepared:', {
                username: formData.get('username'),
                password: formData.get('password') ? '[PRESENT]' : '[MISSING]',
                remember: formData.get('remember')
            });
            
            console.log('Sending request to admin-login.php...');
            
            // Send AJAX request
            const response = await fetch('admin-login.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            console.log('Response received:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok,
                headers: {
                    'content-type': response.headers.get('content-type'),
                    'content-length': response.headers.get('content-length')
                }
            });
            
            // Check response status first
            if (!response.ok) {
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            }
            
            // Get response text first
            const responseText = await response.text();
            console.log('Response text length:', responseText.length);
            console.log('Response text preview:', responseText.substring(0, 500));
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            let result;
            
            if (contentType && contentType.includes('application/json')) {
                console.log('Processing JSON response...');
                try {
                    result = JSON.parse(responseText);
                    console.log('Parsed JSON result:', result);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.log('Raw response causing parse error:', responseText);
                    throw new Error('Invalid JSON response from server. Check server logs.');
                }
                
                if (result.success) {
                    console.log('Login successful, redirecting...');
                    showSuccess(result.message || 'Login successful! Redirecting...');
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        console.log('Redirecting to:', result.redirect || 'admin-dashboard.php');
                        window.location.href = result.redirect || 'admin-dashboard.php';
                    }, 1500);
                } else {
                    console.log('Login failed with message:', result.message);
                    throw new Error(result.message || 'Login failed');
                }
            } else {
                console.log('Processing non-JSON response...');
                console.log('Full response text:', responseText);
                
                // Check if it looks like PHP error
                if (responseText.includes('Fatal error') || responseText.includes('Parse error') || responseText.includes('Warning:') || responseText.includes('Notice:')) {
                    console.error('PHP error detected in response');
                    throw new Error('Server configuration error. Please check PHP error logs.');
                }
                
                // Check if it's a successful redirect (empty response or HTML with success indicators)
                if (responseText.trim() === '') {
                    console.log('Empty response - assuming success');
                    showSuccess('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'admin-dashboard.php';
                    }, 1500);
                } else if (responseText.includes('dashboard') || response.redirected) {
                    console.log('Response contains dashboard reference - assuming success');
                    showSuccess('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'admin-dashboard.php';
                    }, 1500);
                } else if (responseText.includes('<!DOCTYPE html>')) {
                    console.log('HTML response received - extracting error message');
                    // Try to extract error message from HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(responseText, 'text/html');
                    const errorElement = doc.querySelector('.error-message');
                    const errorMessage = errorElement ? errorElement.textContent.trim() : 'Login failed. Server returned HTML instead of expected response.';
                    console.log('Extracted error message:', errorMessage);
                    throw new Error(errorMessage);
                } else {
                    console.log('Unexpected response format');
                    throw new Error('Unexpected response from server. Response: ' + responseText.substring(0, 100) + '...');
                }
            }
            
        } catch (error) {
            console.error('Login error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            // Show user-friendly error message
            let errorMessage = error.message || 'Login failed. Please try again.';
            
            // Handle specific error types
            if (error instanceof TypeError && error.message.includes('fetch')) {
                errorMessage = 'Network error. Please check your connection and try again.';
                console.log('Network error detected');
            } else if (error.message.includes('Server error: 500')) {
                errorMessage = 'Server error (500). Please check server logs and try again.';
                console.log('Server 500 error detected');
            } else if (error.message.includes('Server error: 404')) {
                errorMessage = 'Login service not found (404). Please contact support.';
                console.log('404 error detected');
            } else if (error.message.includes('configuration error')) {
                errorMessage = 'Server configuration error. Please check PHP error logs.';
                console.log('PHP configuration error detected');
            }
            
            showGeneralError(errorMessage);
        } finally {
            console.log('Cleaning up loading state');
            // Remove loading state
            loginForm.classList.remove('loading');
            submitBtn.innerHTML = originalBtnContent;
            submitBtn.disabled = false;
        }
    });
    
    // Real-time validation
    usernameInput.addEventListener('input', function() {
        if (this.value.trim() && this.closest('.form-group').classList.contains('error')) {
            if (this.value.trim().length >= 3) {
                this.closest('.form-group').classList.remove('error');
                const errorMessage = this.closest('.form-group').querySelector('.error-message');
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if (this.value && this.closest('.form-group').classList.contains('error')) {
            if (this.value.length >= 6) {
                this.closest('.form-group').classList.remove('error');
                const errorMessage = this.closest('.form-group').querySelector('.error-message');
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
        }
    });
    
    // Enter key handling
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && (e.target === usernameInput || e.target === passwordInput)) {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Auto-focus username field
    usernameInput.focus();
    
    console.log('Admin login script loaded successfully with debug logging');
});