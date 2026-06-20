// Messages Page JavaScript - Complete Implementation

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all message cards as expanded by default
    const messageBodies = document.querySelectorAll('.message-body');
    messageBodies.forEach(body => {
        body.style.display = 'block';
    });
    
    // Highlight message if coming from redirect
    highlightMessageFromURL();
    
    // Setup filter buttons
    setupFilters();
    
    // Update notification counts
    updateNotificationCounts();
    
    // Setup search input
    setupSearch();
});

// Setup search input
function setupSearch() {
    const searchInput = document.getElementById('messageSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchMessages(this.value);
        });
    }
}

// Toggle message expand/collapse
function toggleMessage(button) {
    const messageCard = button.closest('.message-card');
    const messageBody = messageCard.querySelector('.message-body');
    const buttonText = button.querySelector('span');
    const buttonIcon = button.querySelector('i');
    
    if (messageCard.classList.contains('expanded')) {
        // Collapse
        messageCard.classList.remove('expanded');
        messageBody.style.display = 'none';
        buttonText.textContent = 'Show Details';
        buttonIcon.className = 'fas fa-chevron-down';
    } else {
        // Expand
        messageCard.classList.add('expanded');
        messageBody.style.display = 'block';
        buttonText.textContent = 'Hide Details';
        buttonIcon.className = 'fas fa-chevron-up';
    }
}

// Setup filter functionality
function setupFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const messageCards = document.querySelectorAll('.message-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get filter value
            const filter = this.getAttribute('data-filter');
            
            // Clear search when filtering
            const searchInput = document.getElementById('messageSearch');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Remove no results message
            removeNoResultsMessage();
            
            // Filter messages
            messageCards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all') {
                    card.classList.remove('hidden');
                    card.style.display = 'block';
                } else if (filter === 'replied') {
                    if (status === 'replied') {
                        card.classList.remove('hidden');
                        card.style.display = 'block';
                    } else {
                        card.classList.add('hidden');
                        card.style.display = 'none';
                    }
                } else if (filter === 'pending') {
                    if (status === 'new' || status === 'unread' || status === 'read') {
                        card.classList.remove('hidden');
                        card.style.display = 'block';
                    } else {
                        card.classList.add('hidden');
                        card.style.display = 'none';
                    }
                } else if (filter === 'read') {
                    if (status === 'read') {
                        card.classList.remove('hidden');
                        card.style.display = 'block';
                    } else {
                        card.classList.add('hidden');
                        card.style.display = 'none';
                    }
                }
            });
            
            // Animate visible cards
            const visibleCards = document.querySelectorAll('.message-card:not(.hidden)');
            visibleCards.forEach((card, index) => {
                card.style.animation = 'none';
                setTimeout(() => {
                    card.style.animation = `fadeInUp 0.4s ease ${index * 0.1}s backwards`;
                }, 10);
            });
        });
    });
}

// Search/Filter messages by text
function searchMessages(searchTerm) {
    const messages = document.querySelectorAll('.message-card');
    const lowerSearch = searchTerm.trim().toLowerCase();
    const clearBtn = document.querySelector('.clear-search');
    
    // Show/hide clear button
    if (clearBtn) {
        clearBtn.style.display = searchTerm.trim() ? 'block' : 'none';
    }
    
    let visibleCount = 0;
    
    messages.forEach(message => {
        const subject = message.querySelector('.message-subject').textContent.toLowerCase();
        const body = message.querySelector('.original-message p').textContent.toLowerCase();
        const adminReply = message.querySelector('.reply-content p');
        const replyText = adminReply ? adminReply.textContent.toLowerCase() : '';
        
        if (!lowerSearch || subject.includes(lowerSearch) || body.includes(lowerSearch) || replyText.includes(lowerSearch)) {
            message.style.display = 'block';
            message.classList.remove('hidden');
            visibleCount++;
        } else {
            message.style.display = 'none';
            message.classList.add('hidden');
        }
    });
    
    // Show "no results" message if no messages visible
    showNoResultsMessage(visibleCount, lowerSearch);
}

// Show/hide no results message
function showNoResultsMessage(visibleCount, searchTerm) {
    const messagesList = document.querySelector('.messages-list');
    let noResults = messagesList.querySelector('.no-search-results');
    
    if (visibleCount === 0 && searchTerm) {
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'no-search-results';
            noResults.style.cssText = `
                text-align: center;
                padding: 60px 20px;
                background: white;
                border-radius: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                margin-bottom: 20px;
            `;
            noResults.innerHTML = `
                <div style="font-size: 4rem; color: #cbd5e0; margin-bottom: 20px;">
                    <i class="fas fa-search"></i>
                </div>
                <h3 style="color: #2d3748; margin-bottom: 10px;">No Messages Found</h3>
                <p style="color: #718096;">No messages match "${searchTerm}"</p>
                <button onclick="document.getElementById('messageSearch').value=''; searchMessages('');" 
                    style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-times" style="margin-right: 5px;"></i> Clear Search
                </button>
            `;
            messagesList.appendChild(noResults);
        }
    } else {
        removeNoResultsMessage();
    }
}

// Remove no results message
function removeNoResultsMessage() {
    const noResults = document.querySelector('.no-search-results');
    if (noResults) {
        noResults.remove();
    }
}

// Highlight message from URL parameter
function highlightMessageFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    
    if (highlightId) {
        setTimeout(() => {
            const messageCard = document.querySelector(`[data-message-id="${highlightId}"]`);
            if (messageCard) {
                messageCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                messageCard.style.animation = 'highlight 2s ease';
                
                // Expand the message
                const toggleBtn = messageCard.querySelector('.btn-toggle');
                if (toggleBtn && !messageCard.classList.contains('expanded')) {
                    toggleBtn.click();
                }
            }
        }, 500);
    }
}

// Mark message as read (acknowledge admin reply)
async function markAsRead(messageId) {
    if (!confirm('Mark this message as read? This will acknowledge that you have seen the admin reply.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('message_id', messageId);
        
        const response = await fetch('api/mark-message-read.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Message marked as read', 'success');
            
            // Update the message card
            const messageCard = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageCard) {
                messageCard.setAttribute('data-status', 'read');
                messageCard.className = 'message-card read';
                
                // Update status badge
                const statusBadge = messageCard.querySelector('.message-status-badge');
                if (statusBadge) {
                    statusBadge.className = 'message-status-badge status-read';
                    statusBadge.innerHTML = '<i class="fas fa-envelope-open"></i> Read';
                }
                
                // Remove reply actions
                const replyActions = messageCard.querySelector('.reply-actions');
                if (replyActions) {
                    replyActions.remove();
                }
            }
            
            // Update notification counts
            updateNotificationCounts();
        } else {
            showNotification(data.message || 'Failed to mark message as read', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Refresh messages
function refreshMessages() {
    showNotification('Refreshing messages...', 'info');
    
    // Reload the page after a short delay
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Print message
function printMessage(messageId) {
    const messageCard = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageCard) {
        showNotification('Message not found', 'error');
        return;
    }
    
    // Get message details
    const subject = messageCard.querySelector('.message-subject').textContent;
    const date = messageCard.querySelector('.message-date').textContent;
    const originalMessage = messageCard.querySelector('.original-message p').textContent;
    const adminReply = messageCard.querySelector('.reply-content p');
    const adminReplyText = adminReply ? adminReply.textContent : null;
    const replyDate = messageCard.querySelector('.reply-date');
    const replyDateTime = replyDate ? replyDate.textContent : null;
    
    // Create print window
    const printWindow = window.open('', '_blank');
    
    // Build print HTML
    let printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Message - ${subject}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 40px;
                    color: #333;
                    line-height: 1.6;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #667eea;
                }
                .header h1 {
                    color: #667eea;
                    margin: 0;
                }
                .section {
                    margin: 30px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border-left: 4px solid #667eea;
                }
                .section h2 {
                    color: #667eea;
                    margin-top: 0;
                    font-size: 1.2rem;
                }
                .meta {
                    color: #666;
                    font-size: 0.9rem;
                    margin-bottom: 15px;
                }
                .content {
                    white-space: pre-wrap;
                    background: white;
                    padding: 15px;
                    border-radius: 4px;
                }
                .reply-section {
                    background: #e8f5e9;
                    border-left-color: #4caf50;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    color: #666;
                    font-size: 0.9rem;
                }
                @media print {
                    body { padding: 20px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Mavics Resort & Events Place</h1>
                <p>Message Details</p>
            </div>
            
            <div class="section">
                <h2>Subject: ${subject}</h2>
                <div class="meta">Sent: ${date}</div>
                <div class="content">${originalMessage}</div>
            </div>
    `;
    
    if (adminReplyText) {
        printHTML += `
            <div class="section reply-section">
                <h2>Admin Reply</h2>
                <div class="meta">Replied: ${replyDateTime || 'N/A'}</div>
                <div class="content">${adminReplyText}</div>
            </div>
        `;
    }
    
    printHTML += `
            <div class="footer">
                <p>Printed on ${new Date().toLocaleString()}</p>
                <p>Mavics Resort & Events Place - Contact Management System</p>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    Print Message
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
                    Close
                </button>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(printHTML);
    printWindow.document.close();
    
    // Auto-print after content loads
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };
}

// Update notification counts
function updateNotificationCounts() {
    const repliedMessages = document.querySelectorAll('.message-card[data-status="replied"]');
    const newRepliesCount = repliedMessages.length;
    
    // Update badge in hero section
    const badge = document.querySelector('.new-replies-badge');
    if (badge) {
        if (newRepliesCount > 0) {
            badge.textContent = `${newRepliesCount} New`;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // Update stats card
    const repliesStatCard = document.querySelector('.messages-stats .stat-card:nth-child(2) h4');
    if (repliesStatCard) {
        repliesStatCard.textContent = newRepliesCount;
    }
}

// Show notification helper
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 300px;
    `;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    notification.innerHTML = `
        <i class="fas fa-${icon}" style="margin-right: 10px;"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add CSS for notifications animation
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
    
    @keyframes highlight {
        0%, 100% { 
            transform: scale(1); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        50% { 
            transform: scale(1.02); 
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3); 
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

// Export functions for use in HTML onclick attributes
window.toggleMessage = toggleMessage;
window.markAsRead = markAsRead;
window.refreshMessages = refreshMessages;
window.printMessage = printMessage;
window.searchMessages = searchMessages;
