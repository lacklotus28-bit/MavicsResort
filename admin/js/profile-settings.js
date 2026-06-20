// Enhanced Profile Settings JavaScript - Mavic's Resort
document.addEventListener('DOMContentLoaded', function() {
    initializeProfileSettings();
});

// Global variables
let currentAdminData = {};
let formChangeDetection = {};

function initializeProfileSettings() {
    // Initialize all features
    initTabNavigation();
    initPasswordFeatures();
    initPhotoUpload();
    initFormHandlers();
    initResponsiveFeatures();
    initFormChangeDetection();
    initModalSystem();
    
    // Load current admin data
    loadCurrentAdminData();
}

// Tab Navigation
function initTabNavigation() {
    const navTabs = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            // Check for unsaved changes before switching tabs
            if (hasUnsavedChanges() && !confirm('You have unsaved changes. Do you want to continue without saving?')) {
                return;
            }
            
            // Remove active class from all tabs and contents
            navTabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                if (targetTab === 'admin-management') {
                    setTimeout(initResponsiveAdminManagement, 100);
                }
            }
            
            // Update URL hash
            history.replaceState(null, null, `#${targetTab}`);
            
            // Reset form change detection for new tab
            resetFormChangeDetection();
        });
    });
    
    // Handle initial hash navigation
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetTab = document.querySelector(`[data-tab="${hash}"]`);
        if (targetTab) {
            targetTab.click();
        }
    }
}

// Form Handlers
function initFormHandlers() {
    // Profile form handler
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleProfileUpdate();
        });
    }
    
    // Password form handler
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePasswordChange();
        });
    }
    
    // Photo upload form handler
    const photoUploadForm = document.getElementById('photoUploadForm');
    if (photoUploadForm) {
        photoUploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePhotoUpload();
        });
    }
    
    // Admin create form handler
    const adminCreateForm = document.getElementById('adminCreateForm');
    if (adminCreateForm) {
        adminCreateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAdminCreation();
        });
    }
    
    // Remove photo button
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', function() {
            confirmAction(
                'Remove Profile Photo',
                'Are you sure you want to remove your profile photo? This action cannot be undone.',
                () => handlePhotoRemoval()
            );
        });
    }
}

// Profile Update Handler
async function handleProfileUpdate() {
    const form = document.getElementById('profileForm');
    const formData = new FormData(form);
    formData.append('action', 'update_profile');
    
    try {
        showButtonLoading(form.querySelector('button[type="submit"]'));
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResponseModal('success', 'Profile Updated', result.message);
            if (result.data) {
                updateUIWithNewData(result.data);
            }
            resetFormChangeDetection();
        } else {
            showResponseModal('error', 'Update Failed', result.message);
        }
        
    } catch (error) {
        console.error('Profile update error:', error);
        showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        hideButtonLoading(form.querySelector('button[type="submit"]'));
    }
}

// Password Change Handler
async function handlePasswordChange() {
    const form = document.getElementById('passwordForm');
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showResponseModal('error', 'Password Mismatch', 'New passwords do not match.');
        return;
    }
    
    // Validate password strength
    if (!isPasswordStrong(newPassword)) {
        showResponseModal('error', 'Weak Password', 'Password does not meet the minimum requirements.');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'change_password');
    
    try {
        showButtonLoading(form.querySelector('button[type="submit"]'));
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResponseModal('success', 'Password Changed', result.message);
            form.reset();
            resetPasswordStrengthIndicator();
        } else {
            showResponseModal('error', 'Password Change Failed', result.message);
        }
        
    } catch (error) {
        console.error('Password change error:', error);
        showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        hideButtonLoading(form.querySelector('button[type="submit"]'));
    }
}

// Photo Upload Handler
async function handlePhotoUpload() {
    const form = document.getElementById('photoUploadForm');
    const fileInput = document.getElementById('profile_photo');
    
    if (!fileInput.files.length) {
        showResponseModal('error', 'No File Selected', 'Please select a photo to upload.');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('profile_photo', fileInput.files[0]);
    
    try {
        showButtonLoading(form.querySelector('button[type="submit"]'));
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResponseModal('success', 'Photo Updated', result.message);
            updateProfilePhoto(result.photo_path);
            cancelUpload();
        } else {
            showResponseModal('error', 'Upload Failed', result.message);
        }
        
    } catch (error) {
        console.error('Photo upload error:', error);
        showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        hideButtonLoading(form.querySelector('button[type="submit"]'));
    }
}

// Photo Removal Handler
async function handlePhotoRemoval() {
    try {
        const formData = new FormData();
        formData.append('action', 'remove_photo');
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResponseModal('success', 'Photo Removed', result.message);
            removeProfilePhoto();
        } else {
            showResponseModal('error', 'Removal Failed', result.message);
        }
        
    } catch (error) {
        console.error('Photo removal error:', error);
        showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
    }
}

// Admin Creation Handler
async function handleAdminCreation() {
    const form = document.getElementById('adminCreateForm');
    const formData = new FormData(form);
    formData.append('action', 'create_admin');
    
    try {
        showButtonLoading(form.querySelector('button[type="submit"]'));
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResponseModal('success', 'Admin Created', result.message);
            form.reset();
            if (result.reload_table) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            showResponseModal('error', 'Creation Failed', result.message);
        }
        
    } catch (error) {
        console.error('Admin creation error:', error);
        showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        hideButtonLoading(form.querySelector('button[type="submit"]'));
    }
}

// Admin Status Toggle
async function toggleAdminStatus(adminId, newStatus, adminName) {
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    const message = `Are you sure you want to ${action} ${adminName}? ${newStatus === 'active' ? 'They will be able to log in and access the system.' : 'They will be logged out and unable to access the system.'}`;
    
    confirmAction(
        `${action.charAt(0).toUpperCase() + action.slice(1)} Admin`,
        message,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'update_admin_status');
                formData.append('admin_id', adminId);
                formData.append('status', newStatus);
                
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResponseModal('success', 'Status Updated', result.message);
                    if (result.reload_table) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showResponseModal('error', 'Status Update Failed', result.message);
                }
                
            } catch (error) {
                console.error('Status update error:', error);
                showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
            }
        }
    );
}

// Admin Deletion
async function deleteAdmin(adminId, adminName) {
    confirmAction(
        'Delete Administrator',
        `Are you sure you want to delete ${adminName}? This action cannot be undone and will permanently remove their account and all associated data.`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'delete_admin');
                formData.append('admin_id', adminId);
                
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResponseModal('success', 'Admin Deleted', result.message);
                    if (result.reload_table) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showResponseModal('error', 'Deletion Failed', result.message);
                }
                
            } catch (error) {
                console.error('Admin deletion error:', error);
                showResponseModal('error', 'Error', 'An unexpected error occurred. Please try again.');
            }
        }
    );
}

// Password Features
function initPasswordFeatures() {
    // Password toggle visibility
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                targetInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });
    
    // Password strength checker
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            updatePasswordRequirements(this.value);
            if (confirmPasswordInput.value) {
                checkPasswordMatch(this.value, confirmPasswordInput.value);
            }
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (newPasswordInput) {
                checkPasswordMatch(newPasswordInput.value, this.value);
            }
        });
    }
}

function checkPasswordStrength(password) {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;
    
    const strengthBar = strengthIndicator.querySelector('.strength-fill');
    const strengthText = strengthIndicator.querySelector('.strength-text');
    
    if (!password) {
        strengthBar.className = 'strength-fill';
        strengthBar.style.width = '0%';
        strengthText.textContent = 'Enter a password';
        strengthText.className = 'strength-text';
        return;
    }
    
    let strength = 0;
    let strengthLabel = '';
    let strengthClass = '';
    
    // Calculate strength
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    if (strength <= 2) {
        strengthLabel = 'Weak';
        strengthClass = 'weak';
    } else if (strength <= 4) {
        strengthLabel = 'Fair';
        strengthClass = 'fair';
    } else if (strength <= 5) {
        strengthLabel = 'Good';
        strengthClass = 'good';
    } else {
        strengthLabel = 'Strong';
        strengthClass = 'strong';
    }
    
    strengthBar.className = `strength-fill ${strengthClass}`;
    strengthText.textContent = strengthLabel;
    strengthText.className = `strength-text ${strengthClass}`;
}

function updatePasswordRequirements(password) {
    const requirements = {
        'req-length': password.length >= 8,
        'req-upper': /[A-Z]/.test(password),
        'req-lower': /[a-z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };
    
    Object.keys(requirements).forEach(reqId => {
        const reqElement = document.getElementById(reqId);
        if (reqElement) {
            if (requirements[reqId]) {
                reqElement.classList.add('met');
            } else {
                reqElement.classList.remove('met');
            }
        }
    });
}

function checkPasswordMatch(newPassword, confirmPassword) {
    const matchIndicator = document.getElementById('passwordMatch');
    if (!matchIndicator) return;
    
    if (!confirmPassword) {
        matchIndicator.textContent = '';
        matchIndicator.className = 'password-match';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchIndicator.textContent = '✓ Passwords match';
        matchIndicator.className = 'password-match match';
    } else {
        matchIndicator.textContent = '✗ Passwords do not match';
        matchIndicator.className = 'password-match no-match';
    }
}

function isPasswordStrong(password) {
    return password.length >= 8 &&
           /[A-Z]/.test(password) &&
           /[a-z]/.test(password) &&
           /[0-9]/.test(password) &&
           /[^A-Za-z0-9]/.test(password);
}

function resetPasswordStrengthIndicator() {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (strengthIndicator) {
        const strengthBar = strengthIndicator.querySelector('.strength-fill');
        const strengthText = strengthIndicator.querySelector('.strength-text');
        
        strengthBar.className = 'strength-fill';
        strengthBar.style.width = '0%';
        strengthText.textContent = 'Enter a password';
        strengthText.className = 'strength-text';
    }
    
    const matchIndicator = document.getElementById('passwordMatch');
    if (matchIndicator) {
        matchIndicator.textContent = '';
        matchIndicator.className = 'password-match';
    }
    
    // Reset requirements
    const requirements = ['req-length', 'req-upper', 'req-lower', 'req-number', 'req-special'];
    requirements.forEach(reqId => {
        const reqElement = document.getElementById(reqId);
        if (reqElement) {
            reqElement.classList.remove('met');
        }
    });
}

// Photo Upload Features
function initPhotoUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('profile_photo');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    
    if (!uploadArea || !fileInput) return;
    
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });
    
    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });
    
    function handleFileSelect(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showResponseModal('error', 'Invalid File Type', 'Please select a valid image file (JPG, PNG, or WEBP).');
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            showResponseModal('error', 'File Too Large', 'File size must be less than 2MB.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            uploadArea.style.display = 'none';
            uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function cancelUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const uploadPreview = document.getElementById('uploadPreview');
    const fileInput = document.getElementById('profile_photo');
    
    if (uploadArea) uploadArea.style.display = 'block';
    if (uploadPreview) uploadPreview.style.display = 'none';
    if (fileInput) fileInput.value = '';
}

function updateProfilePhoto(photoPath) {
    const currentPhoto = document.getElementById('currentPhoto');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('removePhotoBtn');
    
    if (currentPhoto) {
        currentPhoto.src = photoPath;
        currentPhoto.style.display = 'block';
    }
    
    if (photoPlaceholder) {
        photoPlaceholder.style.display = 'none';
    }
    
    // Show remove button if not already visible
    if (!removeBtn) {
        const photoActions = document.querySelector('.photo-actions');
        if (photoActions) {
            const newRemoveBtn = document.createElement('button');
            newRemoveBtn.type = 'button';
            newRemoveBtn.id = 'removePhotoBtn';
            newRemoveBtn.className = 'btn btn-danger btn-sm';
            newRemoveBtn.innerHTML = '<i class="fas fa-trash"></i> Remove Photo';
            newRemoveBtn.addEventListener('click', function() {
                confirmAction(
                    'Remove Profile Photo',
                    'Are you sure you want to remove your profile photo? This action cannot be undone.',
                    () => handlePhotoRemoval()
                );
            });
            photoActions.appendChild(newRemoveBtn);
        }
    }
}

function removeProfilePhoto() {
    const currentPhoto = document.getElementById('currentPhoto');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('removePhotoBtn');
    
    if (currentPhoto) {
        currentPhoto.style.display = 'none';
    }
    
    if (photoPlaceholder) {
        photoPlaceholder.style.display = 'flex';
    }
    
    if (removeBtn) {
        removeBtn.remove();
    }
}

// Modal System
function initModalSystem() {
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

function showResponseModal(type, title, message) {
    const modal = document.getElementById('responseModal');
    const modalTitle = document.getElementById('responseModalTitle');
    const modalIcon = document.getElementById('responseModalIcon');
    const modalMessage = document.getElementById('responseModalMessage');
    
    if (!modal) return;
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Update icon and styling based on type
    modalIcon.className = 'modal-icon';
    switch (type) {
        case 'success':
            modalIcon.classList.add('success');
            modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            modalIcon.classList.add('error');
            modalIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'warning':
            modalIcon.classList.add('warning');
            modalIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            break;
        default:
            modalIcon.classList.add('info');
            modalIcon.innerHTML = '<i class="fas fa-info-circle"></i>';
    }
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function confirmAction(title, message, callback) {
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('confirmModalTitle');
    const modalMessage = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalAction');
    
    if (!modal) return;
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Remove existing listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Add new listener
    newConfirmBtn.addEventListener('click', function() {
        closeModal('confirmModal');
        if (callback) callback();
    });
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Form Change Detection
function initFormChangeDetection() {
    const forms = document.querySelectorAll('form');
    forms.forEach((form, index) => {
        captureFormState(form, index);
        
        // Add change listeners
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => markFormChanged(index));
            input.addEventListener('change', () => markFormChanged(index));
        });
    });
}

function captureFormState(form, index) {
    const formData = new FormData(form);
    formChangeDetection[index] = {
        initial: Object.fromEntries(formData.entries()),
        changed: false
    };
}

function markFormChanged(formIndex) {
    if (formChangeDetection[formIndex]) {
        formChangeDetection[formIndex].changed = true;
    }
}

function resetFormChangeDetection() {
    Object.keys(formChangeDetection).forEach(key => {
        formChangeDetection[key].changed = false;
    });
}

function hasUnsavedChanges() {
    return Object.values(formChangeDetection).some(form => form.changed);
}

// Responsive Features
function initResponsiveFeatures() {
    initResponsiveAdminManagement();
    initMobileNavScroll();
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            initResponsiveAdminManagement();
        }, 150);
    });
}

function initResponsiveAdminManagement() {
    const desktopTable = document.querySelector('.desktop-table');
    const mobileCards = document.querySelector('.mobile-cards');
    
    if (!desktopTable || !mobileCards) return;
    
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        desktopTable.style.display = 'none';
        mobileCards.style.display = 'block';
    } else {
        desktopTable.style.display = 'block';
        mobileCards.style.display = 'none';
    }
}

function initMobileNavScroll() {
    const navTabs = document.querySelector('.nav-tabs');
    if (!navTabs) return;
    
    function ensureActiveTabVisible() {
        const activeTab = navTabs.querySelector('.nav-tab.active');
        if (!activeTab) return;
        
        const navRect = navTabs.getBoundingClientRect();
        const tabRect = activeTab.getBoundingClientRect();
        
        if (tabRect.left < navRect.left || tabRect.right > navRect.right) {
            activeTab.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }
    
    // Ensure active tab is visible on tab change
    const tabLinks = document.querySelectorAll('.nav-tab');
    tabLinks.forEach(tab => {
        tab.addEventListener('click', function() {
            setTimeout(ensureActiveTabVisible, 100);
        });
    });
    
    setTimeout(ensureActiveTabVisible, 100);
}

// Utility Functions
function showButtonLoading(button) {
    if (!button) return;
    
    button.disabled = true;
    button.classList.add('loading');
    
    const originalContent = button.innerHTML;
    button.dataset.originalContent = originalContent;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
}

function hideButtonLoading(button) {
    if (!button) return;
    
    button.disabled = false;
    button.classList.remove('loading');
    
    if (button.dataset.originalContent) {
        button.innerHTML = button.dataset.originalContent;
        delete button.dataset.originalContent;
    }
}

function updateUIWithNewData(data) {
    if (data.full_name) {
        const nameElements = document.querySelectorAll('[data-user-name]');
        nameElements.forEach(el => {
            el.textContent = data.full_name;
        });
        
        // Update topbar name if exists
        const topbarName = document.querySelector('.admin-info .admin-name');
        if (topbarName) {
            topbarName.textContent = data.full_name;
        }
    }
    
    if (data.email) {
        const emailElements = document.querySelectorAll('[data-user-email]');
        emailElements.forEach(el => {
            el.textContent = data.email;
        });
    }
}

function loadCurrentAdminData() {
    // This would typically load current admin data from server
    // For now, we'll just capture what's already in the form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        const fullName = document.getElementById('full_name')?.value || '';
        const email = document.getElementById('email')?.value || '';
        
        currentAdminData = {
            full_name: fullName,
            email: email
        };
    }
}

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save active form
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const form = activeTab.querySelector('form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.click();
                }
            }
        }
    }
});

// Prevent accidental page refresh with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Export functions for global access
window.ProfileSettings = {
    showResponseModal,
    confirmAction,
    closeModal,
    toggleAdminStatus,
    deleteAdmin,
    cancelUpload
};