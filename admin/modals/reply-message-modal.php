<!-- Reply Message Modal -->
<div id="replyMessageModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> Reply to Message</h3>
            <button class="modal-close" onclick="closeReplyModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="replyMessageForm">
            <div class="modal-body modal-body-scrollable">
                <input type="hidden" id="replyMessageId" name="message_id">
                
                <!-- Original Message Summary -->
                <div class="original-message-summary">
                    <h4><i class="fas fa-info-circle"></i> Original Message</h4>
                    <div class="summary-content" id="originalMessageSummary">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Reply Form -->
                <div class="form-group">
                    <label for="replyTo">
                        <i class="fas fa-envelope"></i>
                        Recipient Email <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="replyTo" 
                        name="reply_to" 
                        class="form-control" 
                        readonly
                    >
                </div>
                
                <div class="form-group">
                    <label for="replySubject">
                        <i class="fas fa-tag"></i>
                        Subject <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="replySubject" 
                        name="reply_subject" 
                        class="form-control" 
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="replyMessage">
                        <i class="fas fa-comment-alt"></i>
                        Your Reply <span class="required">*</span>
                    </label>
                    <textarea 
                        id="replyMessage" 
                        name="reply_message" 
                        class="form-control" 
                        rows="10" 
                        required
                        placeholder="Type your reply message here..."
                    ></textarea>
                    <small class="char-counter" id="replyCharCounter">0 / 5000 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="markAsReplied" name="mark_as_replied" checked>
                        <span>Mark this message as replied</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeReplyModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-primary" id="sendReplyBtn">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.original-message-summary {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.original-message-summary h4 {
    color: #667eea;
    font-size: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-content {
    display: grid;
    gap: 0.75rem;
}

.summary-item {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.summary-item strong {
    min-width: 80px;
    color: #666;
    flex-shrink: 0;
}

.summary-item span {
    color: #333;
    flex: 1;
    word-break: break-word;
}

.char-counter {
    display: block;
    text-align: right;
    color: #666;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label span {
    color: #333;
    font-weight: 500;
}
</style>