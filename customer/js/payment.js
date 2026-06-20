// Payment Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment Page Loaded');
    console.log('Payment Data:', window.paymentData);
    
    // Initialize payment page features
    initializeAmountSelection();
    initializePaymentMethod();
    initializeFileUpload();
    initializePaymentSubmission();
});

/**
 * Initialize amount selection
 */
function initializeAmountSelection() {
    const amountOptions = document.querySelectorAll('.amount-option input[type="radio"]');
    const customAmountInput = document.getElementById('custom-amount');
    const amountDisplay = document.getElementById('amount-to-pay');
    
    amountOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Enable/disable custom input
            if (this.value === 'custom') {
                customAmountInput.disabled = false;
                customAmountInput.focus();
            } else {
                customAmountInput.disabled = true;
                customAmountInput.value = '';
            }
            
            // Update amount display
            updateAmountDisplay();
        });
    });
    
    // Custom amount input handling
    customAmountInput.addEventListener('input', function() {
        updateAmountDisplay();
        validateCustomAmount();
    });
    
    function updateAmountDisplay() {
        const selectedOption = document.querySelector('.amount-option input[type="radio"]:checked');
        let amount = 0;
        
        if (selectedOption.value === 'custom') {
            amount = parseFloat(customAmountInput.value) || 0;
        } else {
            const optionElement = selectedOption.closest('.amount-option');
            amount = parseFloat(optionElement.dataset.amount);
        }
        
        amountDisplay.textContent = '₱' + amount.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Update payment method instructions
        updatePaymentInstructions(amount);
    }
    
    function validateCustomAmount() {
        const value = parseFloat(customAmountInput.value);
        const max = window.paymentData.remainingBalance;
        const min = 500;
        
        if (value < min) {
            customAmountInput.setCustomValidity('Minimum amount is ₱500');
        } else if (value > max) {
            customAmountInput.setCustomValidity('Amount exceeds remaining balance');
        } else {
            customAmountInput.setCustomValidity('');
        }
    }
    
    function updatePaymentInstructions(amount) {
        const formattedAmount = '₱' + amount.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Update all amount displays in instructions
        document.getElementById('gcash-amount').textContent = formattedAmount;
        document.getElementById('bank-amount').textContent = formattedAmount;
        document.getElementById('paymaya-amount').textContent = formattedAmount;
    }
}

/**
 * Initialize payment method selection
 */
function initializePaymentMethod() {
    const methodOptions = document.querySelectorAll('.payment-method-option input[type="radio"]');
    const paymentDetails = document.querySelectorAll('.payment-details-content');
    const uploadSection = document.getElementById('upload-section');
    
    methodOptions.forEach(option => {
        option.addEventListener('change', function() {
            const method = this.value;
            
            // Hide all payment details
            paymentDetails.forEach(detail => {
                detail.style.display = 'none';
            });
            
            // Show selected method details
            const selectedDetail = document.getElementById(`${method.replace('_', '-')}-details`);
            if (selectedDetail) {
                selectedDetail.style.display = 'block';
            }
            
            // Show/hide upload section based on method
            if (method === 'cash') {
                uploadSection.style.display = 'none';
            } else {
                uploadSection.style.display = 'block';
            }
        });
    });
}

/**
 * Initialize file upload functionality
 */
function initializeFileUpload() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('payment-proof');
    const uploadPlaceholder = document.querySelector('.upload-placeholder');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const removeButton = document.getElementById('remove-upload');
    
    // Click to upload
    uploadArea.addEventListener('click', function() {
        if (!uploadPreview.style.display || uploadPreview.style.display === 'none') {
            fileInput.click();
        }
    });
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        handleFile(e.target.files[0]);
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFile(e.dataTransfer.files[0]);
    });
    
    // Remove upload
    removeButton.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.value = '';
        uploadPlaceholder.style.display = 'block';
        uploadPreview.style.display = 'none';
        previewImage.src = '';
    });
    
    function handleFile(file) {
        if (!file) return;
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showAlert('Error', 'Please upload an image file', 'error');
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('Error', 'File size must be less than 5MB', 'error');
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            uploadPlaceholder.style.display = 'none';
            uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Initialize payment submission
 */
function initializePaymentSubmission() {
    const submitButton = document.getElementById('submit-payment');
    
    submitButton.addEventListener('click', function() {
        if (validatePaymentForm()) {
            submitPayment();
        }
    });
}

/**
 * Validate payment form
 */
function validatePaymentForm() {
    // Get selected amount
    const selectedAmountOption = document.querySelector('.amount-option input[type="radio"]:checked');
    let amount = 0;
    
    if (selectedAmountOption.value === 'custom') {
        const customAmount = document.getElementById('custom-amount');
        amount = parseFloat(customAmount.value);
        
        if (!amount || amount < 500) {
            showAlert('Validation Error', 'Please enter a valid amount (minimum ₱500)', 'error');
            customAmount.focus();
            return false;
        }
        
        if (amount > window.paymentData.remainingBalance) {
            showAlert('Validation Error', 'Amount exceeds remaining balance', 'error');
            customAmount.focus();
            return false;
        }
    } else {
        const optionElement = selectedAmountOption.closest('.amount-option');
        amount = parseFloat(optionElement.dataset.amount);
    }
    
    // Get selected payment method
    const selectedMethod = document.querySelector('.payment-method-option input[type="radio"]:checked');
    if (!selectedMethod) {
        showAlert('Validation Error', 'Please select a payment method', 'error');
        return false;
    }
    
    // Validate proof of payment (except for cash)
    if (selectedMethod.value !== 'cash') {
        const fileInput = document.getElementById('payment-proof');
        if (!fileInput.files || fileInput.files.length === 0) {
            showAlert('Validation Error', 'Please upload proof of payment', 'error');
            return false;
        }
    }
    
    // Validate reference number
    const referenceNumber = document.getElementById('reference-number').value.trim();
    if (!referenceNumber) {
        showAlert('Validation Error', 'Please enter the transaction reference number', 'error');
        document.getElementById('reference-number').focus();
        return false;
    }
    
    return true;
}

/**
 * Submit payment
 */
function submitPayment() {
    const submitButton = document.getElementById('submit-payment');
    const originalText = submitButton.innerHTML;
    
    // Disable button and show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Prepare form data
    const formData = new FormData();
    
    // Amount
    const selectedAmountOption = document.querySelector('.amount-option input[type="radio"]:checked');
    let amount = 0;
    
    if (selectedAmountOption.value === 'custom') {
        amount = parseFloat(document.getElementById('custom-amount').value);
    } else {
        const optionElement = selectedAmountOption.closest('.amount-option');
        amount = parseFloat(optionElement.dataset.amount);
    }
    
    formData.append('booking_id', window.paymentData.bookingId);
    formData.append('amount', amount);
    
    // Payment method
    const selectedMethod = document.querySelector('.payment-method-option input[type="radio"]:checked');
    formData.append('payment_method', selectedMethod.value);
    
    // Proof of payment (if not cash)
    if (selectedMethod.value !== 'cash') {
        const fileInput = document.getElementById('payment-proof');
        formData.append('payment_proof', fileInput.files[0]);
    }
    
    // Reference number
    const referenceNumber = document.getElementById('reference-number').value.trim();
    formData.append('reference_number', referenceNumber);
    
    // Notes
    const notes = document.getElementById('payment-notes').value.trim();
    formData.append('notes', notes);
    
    // Submit via AJAX
    fetch('api/process-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success', 'Payment submitted successfully! Please wait for verification.', 'success');
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'booking-details.php?id=' + window.paymentData.bookingId;
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to process payment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error', error.message || 'Failed to process payment. Please try again.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
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