-- Add image column to about_page_content table
ALTER TABLE about_page_content 
ADD COLUMN section_image VARCHAR(255) NULL AFTER section_data;
