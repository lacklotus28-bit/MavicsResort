<!-- Confirmation Modal -->
<div id="confirmModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-sm">
        <div class="modal-header confirm-header">
            <h3>
                <i class="fas fa-exclamation-triangle" id="confirmModalIcon"></i>
                <span id="confirmModalTitle">Confirm Action</span>
            </h3>
            <button class="modal-close" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="confirm-content">
                <div class="confirm-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p id="confirmModalMessage">Are you sure you want to proceed?</p>
                <p class="confirm-warning" id="confirmModalWarning" style="display: none;"></p>
            </div>
        </div>
        
        <div class="modal-footer confirm-footer">
            <button class="btn-secondary" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i>
                Cancel
            </button>
            <button class="btn-danger" id="confirmModalBtn">
                <i class="fas fa-check"></i>
                Confirm
            </button>
        </div>
    </div>
</div>

<style>
.confirm-header {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    border-bottom: 3px solid #ffc107;
}

.confirm-header h3 {
    color: #856404;
}

.confirm-content {
    padding: 2rem 1rem;
    text-align: center;
}

.confirm-content .confirm-icon {
    font-size: 4rem;
    color: #ffc107;
    margin-bottom: 1.5rem;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
}

.confirm-content p {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.confirm-content .confirm-warning {
    font-size: 0.9rem;
    color: #856404;
    background: #fff3cd;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 4px solid #ffc107;
    margin-top: 1rem;
}

.confirm-footer {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.confirm-footer .btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.confirm-footer .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.confirm-footer .btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.confirm-footer .btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.confirm-footer .btn-danger:active {
    transform: translateY(0);
}

/* Delete specific styling */
.confirm-modal-delete .confirm-header {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-bottom: 3px solid #dc3545;
}

.confirm-modal-delete .confirm-header h3 {
    color: #721c24;
}

.confirm-modal-delete .confirm-icon {
    color: #dc3545 !important;
}

.confirm-modal-delete .confirm-icon i::before {
    content: "\f2ed"; /* trash icon */
}
</style>

<script>
// Confirmation Modal Functions
let confirmModalCallback = null;

function showConfirmModal(options = {}) {
    const {
        title = 'Confirm Action',
        message = 'Are you sure you want to proceed?',
        warning = null,
        confirmText = 'Confirm',
        confirmIcon = 'fa-check',
        type = 'warning', // 'warning' or 'delete'
        onConfirm = null
    } = options;
    
    const modal = document.getElementById('confirmModal');
    const modalContainer = modal.querySelector('.modal-container');
    const titleElement = document.getElementById('confirmModalTitle');
    const messageElement = document.getElementById('confirmModalMessage');
    const warningElement = document.getElementById('confirmModalWarning');
    const confirmBtn = document.getElementById('confirmModalBtn');
    const icon = document.getElementById('confirmModalIcon');
    
    // Set type-specific styling
    if (type === 'delete') {
        modalContainer.classList.add('confirm-modal-delete');
        icon.className = 'fas fa-trash-alt';
    } else {
        modalContainer.classList.remove('confirm-modal-delete');
        icon.className = 'fas fa-exclamation-triangle';
    }
    
    // Set content
    titleElement.textContent = title;
    messageElement.textContent = message;
    
    if (warning) {
        warningElement.textContent = warning;
        warningElement.style.display = 'block';
    } else {
        warningElement.style.display = 'none';
    }
    
    // Set button
    confirmBtn.innerHTML = `<i class="fas ${confirmIcon}"></i> ${confirmText}`;
    
    // Store callback
    confirmModalCallback = onConfirm;
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    confirmModalCallback = null;
}

function confirmModalAction() {
    if (confirmModalCallback && typeof confirmModalCallback === 'function') {
        confirmModalCallback();
    }
    closeConfirmModal();
}

// Set up confirm button click
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmModalBtn');
    if (confirmBtn) {
        confirmBtn.onclick = confirmModalAction;
    }
});

// Convenience function for delete confirmation
function confirmDelete(itemName, onConfirm) {
    showConfirmModal({
        title: 'Delete Confirmation',
        message: `Are you sure you want to delete ${itemName}?`,
        warning: 'This action cannot be undone.',
        confirmText: 'Delete',
        confirmIcon: 'fa-trash-alt',
        type: 'delete',
        onConfirm: onConfirm
    });
}

// Convenience function for archive confirmation
function confirmArchive(itemName, onConfirm) {
    showConfirmModal({
        title: 'Archive Confirmation',
        message: `Are you sure you want to archive ${itemName}?`,
        confirmText: 'Archive',
        confirmIcon: 'fa-archive',
        type: 'warning',
        onConfirm: onConfirm
    });
}
</script>
