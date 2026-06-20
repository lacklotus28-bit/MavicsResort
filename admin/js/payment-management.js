// Payment Management JavaScript

let hasChanges = false;
const changedFields = new Set();

// Mark field as changed
function markAsChanged(element) {
    hasChanges = true;
    changedFields.add(element.name);
    element.classList.add('changed');
    
    // Show unsaved changes indicator
    updateSaveButtonState();
}

// Update save button state
function updateSaveButtonState() {
    const saveBtn = document.querySelector('.content-header .btn-primary');
    if (hasChanges) {
        saveBtn.classList.add('has-changes');
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes (' + changedFields.size + ')';
    } else {
        saveBtn.classList.remove('has-changes');
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
    }
}

// Save all settings
function saveAllSettings() {
    if (!hasChanges) {
        showNotification('No changes to save', 'info', '<i class="fas fa-info-circle"></i>');
        return;
    }

    // Show custom confirm modal instead of default confirm
    showConfirmModal(
        'Save Changes?',
        `Save ${changedFields.size} change${changedFields.size > 1 ? 's' : ''}? These changes will be immediately visible to customers.`,
        function() {
            // User confirmed - proceed with save
            performSave();
        }
    );
}

// Perform the actual save operation
function performSave() {
    const formData = new FormData();
    
    // Collect all form inputs including hidden inputs
    document.querySelectorAll('input[type="checkbox"], input[type="text"], input[type="hidden"], textarea').forEach(input => {
        if (input.type === 'checkbox') {
            formData.append(input.name, input.checked ? '1' : '0');
        } else if (input.name) {
            formData.append(input.name, input.value);
        }
    });

    // Show loading state
    const saveBtn = document.querySelector('.content-header .btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    fetch('api/update-payment-settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message with details
            const changesCount = data.updated_count || changedFields.size;
            showSuccessMessage(
                'Payment Settings Saved!',
                `Successfully updated ${changesCount} setting${changesCount > 1 ? 's' : ''}. Changes are now visible to customers.`
            );
            
            hasChanges = false;
            changedFields.clear();
            
            // Remove changed class from all fields
            document.querySelectorAll('.form-control.changed, input.changed').forEach(el => {
                el.classList.remove('changed');
            });
            
            // Update button state
            updateSaveButtonState();
            
            // Show brief success indicator on page
            showPageSuccessIndicator();
        } else {
            showNotification(data.message || 'Failed to save settings', 'error', '<i class="fas fa-exclamation-triangle"></i>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while saving. Please try again.', 'error', '<i class="fas fa-times-circle"></i>');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Show confirm modal
function showConfirmModal(title, message, onConfirm) {
    const confirmModal = document.createElement('div');
    confirmModal.className = 'confirm-modal-overlay';
    confirmModal.innerHTML = `
        <div class="confirm-modal">
            <div class="confirm-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <h2>${title}</h2>
            <p>${message}</p>
            <div class="confirm-actions">
                <button class="btn btn-outline" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" onclick="confirmModalAction()">
                    <i class="fas fa-check"></i> Yes, Save Changes
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmModal);
    
    // Store the callback
    window.confirmModalCallback = onConfirm;
    
    // Trigger animation
    setTimeout(() => confirmModal.classList.add('show'), 10);
    
    // Close on Escape key
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            closeConfirmModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

// Close confirm modal
function closeConfirmModal() {
    const modal = document.querySelector('.confirm-modal-overlay');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
    window.confirmModalCallback = null;
}

// Confirm modal action
function confirmModalAction() {
    if (window.confirmModalCallback) {
        window.confirmModalCallback();
    }
    closeConfirmModal();
}

// Show success message with custom styling
function showSuccessMessage(title, message) {
    const successModal = document.createElement('div');
    successModal.className = 'success-modal-overlay';
    successModal.innerHTML = `
        <div class="success-modal">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>${title}</h2>
            <p>${message}</p>
            <button class="btn btn-primary" onclick="closeSuccessModal()">
                <i class="fas fa-check"></i> Got it
            </button>
        </div>
    `;
    
    document.body.appendChild(successModal);
    
    // Trigger animation
    setTimeout(() => successModal.classList.add('show'), 10);
    
    // Auto close after 5 seconds
    setTimeout(() => {
        closeSuccessModal();
    }, 5000);
}

// Close success modal
function closeSuccessModal() {
    const modal = document.querySelector('.success-modal-overlay');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Show page success indicator
function showPageSuccessIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'page-success-indicator';
    indicator.innerHTML = '<i class="fas fa-check-circle"></i> Settings saved successfully!';
    
    document.body.appendChild(indicator);
    
    setTimeout(() => indicator.classList.add('show'), 10);
    
    setTimeout(() => {
        indicator.classList.remove('show');
        setTimeout(() => indicator.remove(), 300);
    }, 3000);
}

// Handle QR code upload
function handleQRUpload(input) {
    const file = input.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        showNotification('Please upload an image file', 'error', '<i class="fas fa-exclamation-circle"></i>');
        return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        showNotification('Image file size must be less than 2MB', 'error', '<i class="fas fa-exclamation-circle"></i>');
        return;
    }

    const formData = new FormData();
    formData.append('qr_code', file);

    // Show loading
    showNotification('Uploading QR code...', 'info', '<i class="fas fa-spinner fa-spin"></i>');

    fetch('api/upload-qr-code.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(
                'QR Code Uploaded!',
                'Your GCash QR code has been uploaded successfully. Customers can now scan it during checkout.'
            );
            
            // Update hidden input and preview
            const hiddenInput = document.getElementById('gcash_qr_code');
            hiddenInput.value = data.file_path;
            markAsChanged(hiddenInput);
            
            // Update or create preview
            let preview = document.querySelector('.qr-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'qr-preview';
                input.parentElement.appendChild(preview);
            }
            preview.innerHTML = `<img src="${data.file_path}" alt="GCash QR Code">`;
        } else {
            showNotification(data.message || 'Failed to upload QR code', 'error', '<i class="fas fa-exclamation-triangle"></i>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred during upload', 'error', '<i class="fas fa-times-circle"></i>');
    });
}

// Show notification with icon
function showNotification(message, type = 'info', iconHTML = '') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    if (!iconHTML) {
        iconHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>`;
    }
    
    notification.innerHTML = `
        ${iconHTML}
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Warn before leaving if there are unsaved changes
window.addEventListener('beforeunload', (e) => {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment Management page loaded');
    
    // Add keyboard shortcut for save (Ctrl+S or Cmd+S)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveAllSettings();
        }
    });
    
    // Show welcome tip on first load
    const hasSeenTip = sessionStorage.getItem('paymentManagementTipSeen');
    if (!hasSeenTip) {
        setTimeout(() => {
            showNotification(
                'Tip: All changes here will be immediately visible to customers on the payment methods page',
                'info',
                '<i class="fas fa-lightbulb"></i>'
            );
            sessionStorage.setItem('paymentManagementTipSeen', 'true');
        }, 1000);
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-modal-overlay')) {
            closeConfirmModal();
        }
        if (e.target.classList.contains('success-modal-overlay')) {
            closeSuccessModal();
        }
    });
});
