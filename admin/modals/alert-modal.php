<!-- Alert Modal (Success/Error) -->
<div id="alertModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-sm">
        <div class="modal-header" id="alertModalHeader">
            <h3 id="alertModalTitle">
                <i class="fas fa-info-circle" id="alertModalIcon"></i>
                <span id="alertModalTitleText">Notification</span>
            </h3>
            <button class="modal-close" onclick="closeAlertModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="alert-content" id="alertModalContent">
                <!-- Alert message will be inserted here -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-primary" onclick="closeAlertModal()" id="alertModalBtn">
                <i class="fas fa-check"></i> OK
            </button>
        </div>
    </div>
</div>

<style>
.alert-content {
    padding: 1rem 0;
    text-align: center;
    font-size: 1.05rem;
    line-height: 1.6;
    color: #333;
}

.alert-content i.alert-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.alert-content.success i.alert-icon {
    color: #28a745;
}

.alert-content.error i.alert-icon {
    color: #dc3545;
}

.alert-content.warning i.alert-icon {
    color: #ffc107;
}

.alert-content.info i.alert-icon {
    color: #17a2b8;
}

.modal-header.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-bottom: 3px solid #28a745;
}

.modal-header.success h3 {
    color: #155724;
}

.modal-header.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-bottom: 3px solid #dc3545;
}

.modal-header.error h3 {
    color: #721c24;
}

.modal-header.warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    border-bottom: 3px solid #ffc107;
}

.modal-header.warning h3 {
    color: #856404;
}

.modal-header.info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border-bottom: 3px solid #17a2b8;
}

.modal-header.info h3 {
    color: #0c5460;
}
</style>

<script>
// Alert Modal Functions
function showAlertModal(type, title, message, callback = null) {
    const modal = document.getElementById('alertModal');
    const header = document.getElementById('alertModalHeader');
    const icon = document.getElementById('alertModalIcon');
    const titleText = document.getElementById('alertModalTitleText');
    const content = document.getElementById('alertModalContent');
    const btn = document.getElementById('alertModalBtn');
    
    // Reset classes
    header.className = 'modal-header ' + type;
    content.className = 'alert-content ' + type;
    
    // Set icon based on type
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    icon.className = 'fas ' + (icons[type] || icons.info);
    
    // Set content
    titleText.textContent = title;
    content.innerHTML = `<i class="fas ${icons[type]} alert-icon"></i><p>${message}</p>`;
    
    // Store callback
    if (callback) {
        btn.onclick = function() {
            closeAlertModal();
            callback();
        };
    } else {
        btn.onclick = closeAlertModal;
    }
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAlertModal() {
    const modal = document.getElementById('alertModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Convenience functions
function showSuccessModal(message, callback = null) {
    showAlertModal('success', 'Success!', message, callback);
}

function showErrorModal(message, callback = null) {
    showAlertModal('error', 'Error', message, callback);
}

function showWarningModal(message, callback = null) {
    showAlertModal('warning', 'Warning', message, callback);
}

function showInfoModal(message, callback = null) {
    showAlertModal('info', 'Information', message, callback);
}
</script>