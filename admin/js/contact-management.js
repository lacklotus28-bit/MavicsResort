// Contact Management JavaScript

let currentMessageId = null;
let currentMessageData = null;

// View Message Functions
function viewMessage(id) {
    currentMessageId = id;
    const modal = document.getElementById('viewMessageModal');
    const content = document.getElementById('viewMessageContent');
    
    // Show modal with loading state
    content.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading message...</p>
        </div>
    `;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Fetch message data
    fetch(`api/get-message.php?id=${id}`)
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Get response text first to check if it's valid JSON
            return response.text();
        })
        .then(text => {
            // Try to parse as JSON
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('Response is not valid JSON:', text);
                throw new Error('Server returned invalid JSON. Check PHP errors.');
            }
        })
        .then(data => {
            if (data.success) {
                currentMessageData = data.message;
                displayMessageDetails(data.message);
            } else {
                console.error('API Error:', data);
                showErrorModal(data.message + (data.error ? '<br><small>' + data.error + '</small>' : ''));
                closeViewModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Failed to load message details: ' + error.message);
            closeViewModal();
        });
}

function displayMessageDetails(message) {
    const content = document.getElementById('viewMessageContent');
    const statusBadge = getStatusBadge(message.status);
    
    content.innerHTML = `
        <div class="sender-info">
            <div class="info-box">
                <label><i class="fas fa-user"></i> From</label>
                <div class="value">${escapeHtml(message.name)}</div>
            </div>
            <div class="info-box">
                <label><i class="fas fa-envelope"></i> Email</label>
                <div class="value">${escapeHtml(message.email)}</div>
            </div>
            ${message.phone ? `
            <div class="info-box">
                <label><i class="fas fa-phone"></i> Phone</label>
                <div class="value">${escapeHtml(message.phone)}</div>
            </div>
            ` : ''}
            <div class="info-box">
                <label><i class="fas fa-tag"></i> Subject</label>
                <div class="value">${escapeHtml(message.subject)}</div>
            </div>
        </div>
        
        <div class="message-detail-card">
            <h4><i class="fas fa-comment-alt"></i> Message</h4>
            <div class="message-content-box">
                ${escapeHtml(message.message).replace(/\n/g, '<br>')}
            </div>
        </div>
        
        ${message.admin_reply ? `
        <div class="message-detail-card">
            <h4><i class="fas fa-reply"></i> Your Reply</h4>
            <div class="message-content-box">
                ${escapeHtml(message.admin_reply).replace(/\n/g, '<br>')}
            </div>
            ${message.replied_by_name ? `
            <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                <i class="fas fa-user"></i> Replied by: ${escapeHtml(message.replied_by_name)}
            </p>
            ` : ''}
        </div>
        ` : ''}
        
        <div class="message-meta-info">
            <div>
                <i class="fas fa-clock"></i>
                Received: ${formatDate(message.created_at)}
            </div>
            <div>
                Status: ${statusBadge}
            </div>
            ${message.read_at ? `
            <div>
                <i class="fas fa-eye"></i>
                Read: ${formatDate(message.read_at)}
            </div>
            ` : ''}
            ${message.replied_at ? `
            <div>
                <i class="fas fa-reply"></i>
                Replied: ${formatDate(message.replied_at)}
            </div>
            ` : ''}
        </div>
    `;
    
    // Update button visibility
    const markReadBtn = document.getElementById('markReadBtn');
    const replyBtn = document.getElementById('replyFromViewBtn');
    
    if (message.status === 'read' || message.status === 'replied') {
        markReadBtn.style.display = 'none';
    } else {
        markReadBtn.style.display = 'inline-flex';
    }
}

function closeViewModal() {
    const modal = document.getElementById('viewMessageModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentMessageId = null;
}

function markAsRead() {
    if (!currentMessageId) return;
    
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('message_id', currentMessageId);
    
    fetch('manage-contact.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showSuccessModal('Message marked as read', () => {
            location.reload();
        });
        closeViewModal();
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Failed to mark message as read');
    });
}

function openReplyFromView() {
    if (!currentMessageData) return;
    closeViewModal();
    replyMessage(currentMessageId);
}

// Reply Message Functions
function replyMessage(id) {
    currentMessageId = id;
    
    // If we don't have the message data, fetch it first
    if (!currentMessageData || currentMessageData.id != id) {
        fetch(`api/get-message.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentMessageData = data.message;
                    openReplyModal(data.message);
                } else {
                    showErrorModal(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Failed to load message details');
            });
    } else {
        openReplyModal(currentMessageData);
    }
}

function openReplyModal(message) {
    const modal = document.getElementById('replyMessageModal');
    
    // Populate form
    document.getElementById('replyMessageId').value = message.id;
    document.getElementById('replyTo').value = message.email;
    document.getElementById('replySubject').value = `Re: ${message.subject}`;
    document.getElementById('replyMessage').value = '';
    
    // Populate original message summary
    const summary = document.getElementById('originalMessageSummary');
    summary.innerHTML = `
        <div class="summary-item">
            <strong>From:</strong>
            <span>${escapeHtml(message.name)}</span>
        </div>
        <div class="summary-item">
            <strong>Subject:</strong>
            <span>${escapeHtml(message.subject)}</span>
        </div>
        <div class="summary-item">
            <strong>Date:</strong>
            <span>${formatDate(message.created_at)}</span>
        </div>
        <div class="summary-item" style="grid-column: 1 / -1;">
            <strong>Message:</strong>
            <span>${escapeHtml(message.message.substring(0, 200))}${message.message.length > 200 ? '...' : ''}</span>
        </div>
    `;
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Focus on reply textarea
    setTimeout(() => {
        document.getElementById('replyMessage').focus();
    }, 300);
}

function closeReplyModal() {
    const modal = document.getElementById('replyMessageModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Reset form
    document.getElementById('replyMessageForm').reset();
}

// Handle Reply Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyMessageForm');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('sendReplyBtn');
            const originalContent = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            const formData = new FormData(replyForm);
            
            // Temporarily use debug endpoint
            fetch('api/send-reply-debug.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Get response text first
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('Response is not valid JSON:', text);
                    throw new Error('Server returned invalid response. Check server logs.');
                }
            })
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message, () => {
                        location.reload();
                    });
                    closeReplyModal();
                } else {
                    showErrorModal(data.message || 'An error occurred while sending the reply');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Failed to send reply: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
        });
        
        // Character counter for reply message
        const replyTextarea = document.getElementById('replyMessage');
        const charCounter = document.getElementById('replyCharCounter');
        
        if (replyTextarea && charCounter) {
            replyTextarea.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = 5000;
                charCounter.textContent = `${length} / ${maxLength} characters`;
                
                if (length > maxLength * 0.9) {
                    charCounter.style.color = '#dc3545';
                } else {
                    charCounter.style.color = '#666';
                }
            });
        }
    }
});

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusBadge(status) {
    const badges = {
        'unread': '<span class="status-badge status-unread">Unread</span>',
        'read': '<span class="status-badge status-read">Read</span>',
        'replied': '<span class="status-badge status-replied">Replied</span>',
        'archived': '<span class="status-badge status-archived">Archived</span>'
    };
    return badges[status] || status;
}

// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        const modal = e.target.closest('.custom-modal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.custom-modal.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Confirmation Functions
function confirmDeleteMessage(messageId, senderName) {
    confirmDelete(`this message from "${senderName}"`, function() {
        deleteMessage(messageId);
    });
}

function confirmArchiveMessage(messageId, senderName) {
    confirmArchive(`this message from "${senderName}"`, function() {
        archiveMessage(messageId);
    });
}

function deleteMessage(messageId) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('message_id', messageId);
    
    fetch('manage-contact.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showSuccessModal('Message deleted successfully', () => {
            location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Failed to delete message');
    });
}

function archiveMessage(messageId) {
    const formData = new FormData();
    formData.append('action', 'archive');
    formData.append('message_id', messageId);
    
    fetch('manage-contact.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showSuccessModal('Message archived successfully', () => {
            location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Failed to archive message');
    });
}
