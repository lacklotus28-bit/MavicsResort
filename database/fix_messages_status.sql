-- Fix Messages Status Mismatch
-- This script standardizes the status values in contact_messages table

-- Step 1: Update existing records from 'new' to 'unread'
UPDATE contact_messages 
SET status = 'unread' 
WHERE status = 'new';

-- Step 2: Modify the enum to remove 'new' and use 'unread' instead
ALTER TABLE contact_messages 
MODIFY COLUMN status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread';

-- Verify the changes
SELECT id, name, email, subject, status, created_at 
FROM contact_messages 
ORDER BY created_at DESC;
