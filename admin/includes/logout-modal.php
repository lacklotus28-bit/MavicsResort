<?php
// Fixed Logout Modal Include - Mavic's Resort
?>
<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-sign-out-alt"></i> Confirm Logout</h3>
            <button class="modal-close" onclick="closeLogoutModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout from the admin panel?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
                Yes, Logout
            </button>
            <button class="btn btn-secondary" onclick="closeLogoutModal()">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
// Logout Modal Functions
function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmLogout() {
    // Show loading state
    const modal = document.querySelector('#logoutModal .modal');
    modal.innerHTML = `
        <div class="modal-body" style="text-align: center; padding: 2rem;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 1rem;">Logging out...</p>
        </div>
    `;
    
    // Redirect to logout handler
    window.location.href = 'logout.php';
}

// Add event listener for logout button
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutModal();
        });
    }
    
    // Close modal when clicking overlay
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('logoutModal').classList.contains('show')) {
            closeLogoutModal();
        }
    });
});
</script>