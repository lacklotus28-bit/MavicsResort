// Manage Users JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize event listeners
    initializeFilters();
    initializeForms();
    initializeCheckboxes();
});

// Filter functionality
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter = document.getElementById('sortFilter');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    if (sortFilter) {
        sortFilter.addEventListener('change', applyFilters);
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    url.searchParams.set('status', status);
    url.searchParams.set('sort', sort);
    
    window.location.href = url.toString();
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

// Checkbox functionality
function initializeCheckboxes() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkedBoxes.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = `${checkedBoxes.length} user${checkedBoxes.length > 1 ? 's' : ''} selected`;
    } else {
        bulkActions.style.display = 'none';
    }
}

function getSelectedUsers() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// Custom Confirmation Modal
function showConfirmModal(title, message, onConfirm, isDangerous = false) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-${isDangerous ? 'exclamation-triangle' : 'question-circle'}"></i> ${title}</h2>
            </div>
            <div class="modal-body">
                <p style="margin: 0; color: var(--dark-gray);">${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                <button class="btn ${isDangerous ? 'btn-danger' : 'btn-primary'}" id="confirmBtn">
                    <i class="fas fa-${isDangerous ? 'trash' : 'check'}"></i> ${isDangerous ? 'Delete' : 'Confirm'}
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('confirmBtn').onclick = function() {
        modal.remove();
        onConfirm();
    };
    
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };
}

// View User
function viewUser(userId) {
    const modal = document.getElementById('viewUserModal');
    const content = document.getElementById('viewUserContent');
    
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    modal.style.display = 'block';
    
    fetch(`api/get-user-details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = renderUserDetails(data.user);
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading user details</div>';
            console.error('Error:', error);
        });
}

function renderUserDetails(user) {
    return `
        <div class="user-details-grid">
            <div class="detail-item">
                <div class="detail-label">User ID</div>
                <div class="detail-value">#${user.id}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="status-badge status-${user.status}">${user.status.toUpperCase()}</span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Full Name</div>
                <div class="detail-value">${user.first_name} ${user.last_name}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email</div>
                <div class="detail-value">${user.email}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Phone</div>
                <div class="detail-value">${user.phone || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email Verified</div>
                <div class="detail-value">${user.email_verified ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Registration Date</div>
                <div class="detail-value">${new Date(user.created_at).toLocaleDateString()}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Last Login</div>
                <div class="detail-value">${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</div>
            </div>
        </div>
        
        ${user.address ? `
            <div class="detail-item" style="grid-column: 1 / -1;">
                <div class="detail-label">Address</div>
                <div class="detail-value">${user.address}</div>
            </div>
        ` : ''}
        
        <h3 class="section-title"><i class="fas fa-calendar-check"></i> Booking History</h3>
        ${renderBookingHistory(user.bookings)}
        
        <h3 class="section-title"><i class="fas fa-history"></i> Login History</h3>
        ${renderLoginHistory(user.login_history)}
    `;
}

function renderBookingHistory(bookings) {
    if (!bookings || bookings.length === 0) {
        return '<p class="text-muted">No bookings found</p>';
    }
    
    return bookings.map(booking => `
        <div class="booking-history-item">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <strong>${booking.venue_name}</strong>
                    <br>
                    <small class="text-muted">${new Date(booking.booking_date).toLocaleDateString()} - ${booking.event_type}</small>
                </div>
                <span class="status-badge status-${booking.status}">${booking.status}</span>
            </div>
            <div style="margin-top: 0.5rem;">
                <small><strong>Amount:</strong> ₱${parseFloat(booking.total_amount).toLocaleString()}</small>
                <small style="margin-left: 1rem;"><strong>Payment:</strong> ${booking.payment_status}</small>
            </div>
        </div>
    `).join('');
}

function renderLoginHistory(history) {
    if (!history || history.length === 0) {
        return '<p class="text-muted">No login history available</p>';
    }
    
    return `
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>IP Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${history.slice(0, 10).map(log => `
                        <tr>
                            <td>${new Date(log.login_time).toLocaleString()}</td>
                            <td>${log.ip_address}</td>
                            <td>${log.success ? '<span class="text-success">Success</span>' : '<span class="text-danger">Failed</span>'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Edit User
function editUser(userId) {
    fetch(`api/get-user-details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_first_name').value = user.first_name;
                document.getElementById('edit_last_name').value = user.last_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_phone').value = user.phone || '';
                document.getElementById('edit_address').value = user.address || '';
                document.getElementById('edit_status').value = user.status;
                
                document.getElementById('editUserModal').style.display = 'block';
            } else {
                showNotification('Error loading user data: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error loading user data', 'error');
            console.error('Error:', error);
        });
}

// Initialize Forms
function initializeForms() {
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditUser);
    }
    
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddUser);
    }
}

function handleEditUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('api/update-user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showNotification('Error updating user', 'error');
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleAddUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    fetch('api/add-user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showNotification('Error adding user', 'error');
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Open Add User Modal
function openAddUserModal() {
    document.getElementById('addUserForm').reset();
    document.getElementById('addUserModal').style.display = 'block';
}

// Change User Status
function changeUserStatus(userId, status) {
    const statusText = status === 'active' ? 'activate' : 'deactivate';
    
    showConfirmModal(
        'Confirm Status Change',
        `Are you sure you want to ${statusText} this user?`,
        function() {
            fetch('api/change-user-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error changing user status', 'error');
                console.error('Error:', error);
            });
        }
    );
}

// Delete User
function deleteUser(userId) {
    showConfirmModal(
        'Delete User',
        'Are you sure you want to delete this user? This action cannot be undone and will also delete all associated bookings.',
        function() {
            fetch('api/delete-user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error deleting user', 'error');
                console.error('Error:', error);
            });
        },
        true
    );
}

// Bulk Actions
function bulkChangeStatus(status) {
    const userIds = getSelectedUsers();
    
    if (userIds.length === 0) {
        showNotification('Please select at least one user', 'warning');
        return;
    }
    
    const statusText = status === 'active' ? 'activate' : 'deactivate';
    
    showConfirmModal(
        'Bulk Status Change',
        `Are you sure you want to ${statusText} ${userIds.length} user(s)?`,
        function() {
            fetch('api/bulk-change-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_ids: userIds, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error changing user status', 'error');
                console.error('Error:', error);
            });
        }
    );
}

function bulkDelete() {
    const userIds = getSelectedUsers();
    
    if (userIds.length === 0) {
        showNotification('Please select at least one user', 'warning');
        return;
    }
    
    showConfirmModal(
        'Bulk Delete Users',
        `Are you sure you want to delete ${userIds.length} user(s)? This action cannot be undone and will also delete all their associated data.`,
        function() {
            fetch('api/bulk-delete-users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_ids: userIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error deleting users', 'error');
                console.error('Error:', error);
            });
        },
        true
    );
}

function bulkExport() {
    const userIds = getSelectedUsers();
    
    if (userIds.length === 0) {
        showNotification('Please select at least one user', 'warning');
        return;
    }
    
    window.location.href = `api/export-users.php?ids=${userIds.join(',')}`;
}

// Export Users
function exportUsers() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = 'api/export-users.php?all=1';
    
    if (search) {
        url += `&search=${encodeURIComponent(search)}`;
    }
    
    if (status) {
        url += `&status=${encodeURIComponent(status)}`;
    }
    
    window.location.href = url;
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info'}`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideInRight 0.3s ease;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Modal Functions
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});
