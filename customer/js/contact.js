// Contact Form Handler
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    
    if (!contactForm) return;
    
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Hide previous messages
        successMessage.style.display = 'none';
        errorMessage.style.display = 'none';
        
        // Validate all required fields
        let isValid = true;
        const requiredFields = contactForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        // If validation fails, show error and don't submit
        if (!isValid) {
            document.getElementById('errorText').textContent = 'Please fill in all required fields correctly.';
            errorMessage.style.display = 'flex';
            
            // Add close button if not exists
            if (!errorMessage.querySelector('.alert-close')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.onclick = () => errorMessage.style.display = 'none';
                errorMessage.appendChild(closeBtn);
            }
            
            // Scroll to first invalid field
            const firstInvalid = contactForm.querySelector('.invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sending...</span>';
        
        // Get form data
        const formData = new FormData(contactForm);
        
        try {
            const response = await fetch('process/submit_contact.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                document.getElementById('successText').textContent = data.message;
                successMessage.style.display = 'flex';
                
                // Add close button if not exists
                if (!successMessage.querySelector('.alert-close')) {
                    const closeBtn = document.createElement('button');
                    closeBtn.className = 'alert-close';
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.onclick = () => {
                        successMessage.style.opacity = '0';
                        successMessage.style.transform = 'translateY(-20px)';
                        setTimeout(() => {
                            successMessage.style.display = 'none';
                            successMessage.style.opacity = '1';
                            successMessage.style.transform = 'translateY(0)';
                        }, 300);
                    };
                    successMessage.appendChild(closeBtn);
                }
                
                // Reset form
                contactForm.reset();
                
                // Update character counter if exists
                const charCounter = document.querySelector('.char-counter');
                if (charCounter) {
                    charCounter.textContent = '0 / 1000 characters';
                    charCounter.style.color = '#666';
                }
                
                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Hide success message after 8 seconds
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                        successMessage.style.opacity = '1';
                        successMessage.style.transform = 'translateY(0)';
                    }, 300);
                }, 8000);
            } else {
                // Show error message
                document.getElementById('errorText').textContent = data.message;
                errorMessage.style.display = 'flex';
                
                // Add close button if not exists
                if (!errorMessage.querySelector('.alert-close')) {
                    const closeBtn = document.createElement('button');
                    closeBtn.className = 'alert-close';
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.onclick = () => errorMessage.style.display = 'none';
                    errorMessage.appendChild(closeBtn);
                }
                
                // Scroll to error message
                errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('errorText').textContent = 'An error occurred. Please try again later.';
            errorMessage.style.display = 'flex';
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Send Message</span>';
        }
    });
    
    // Form validation
    const inputs = contactForm.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('invalid')) {
                validateField(this);
            }
        });
    });
    
    function validateField(field) {
        // Skip validation for non-required empty fields
        if (!field.hasAttribute('required') && !field.value.trim()) {
            field.classList.remove('invalid');
            return true;
        }
        
        // Check if required field is empty
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('invalid');
            return false;
        }
        
        // Validate email format
        if (field.type === 'email' && field.value.trim()) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(field.value.trim())) {
                field.classList.add('invalid');
                return false;
            }
        }
        
        // Validate select field (check if value is not empty string)
        if (field.tagName === 'SELECT' && field.hasAttribute('required')) {
            if (field.value === '' || field.value === null) {
                field.classList.add('invalid');
                return false;
            }
        }
        
        // Field is valid
        field.classList.remove('invalid');
        return true;
    }
    
    // Phone number formatting (basic)
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            e.target.value = value;
        });
    }
    
    // Character counter for message
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const maxLength = 1000;
        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.style.cssText = 'text-align: right; color: #666; font-size: 0.85rem; margin-top: 0.25rem;';
        messageTextarea.parentNode.appendChild(counterDiv);
        
        function updateCounter() {
            const remaining = maxLength - messageTextarea.value.length;
            counterDiv.textContent = `${messageTextarea.value.length} / ${maxLength} characters`;
            
            if (remaining < 50) {
                counterDiv.style.color = '#e74c3c';
            } else {
                counterDiv.style.color = '#666';
            }
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        messageTextarea.setAttribute('maxlength', maxLength);
        updateCounter();
    }
});
