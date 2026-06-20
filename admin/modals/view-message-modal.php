<!-- View Message Modal -->
<div id="viewMessageModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-envelope-open"></i> Message Details</h3>
            <button class="modal-close" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body" id="viewMessageContent">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading message...</p>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeViewModal()">
                <i class="fas fa-times"></i> Close
            </button>
            <button class="btn-primary" id="markReadBtn" onclick="markAsRead()">
                <i class="fas fa-check"></i> Mark as Read
            </button>
            <button class="btn-success" id="replyFromViewBtn" onclick="openReplyFromView()">
                <i class="fas fa-reply"></i> Reply
            </button>
        </div>
    </div>
</div>

<style>
.message-detail-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.message-detail-card h4 {
    color: #667eea;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-detail-card p {
    color: #333;
    line-height: 1.6;
    margin: 0;
}

.sender-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .sender-info {
        grid-template-columns: 1fr;
    }
}

.info-box {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid #667eea;
    word-break: break-word;
}

.info-box label {
    font-size: 0.85rem;
    color: #666;
    display: block;
    margin-bottom: 0.25rem;
}

.info-box .value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
}

.message-content-box {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    max-height: 400px;
    overflow-y: auto;
}

.message-meta-info {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #666;
}
</style>