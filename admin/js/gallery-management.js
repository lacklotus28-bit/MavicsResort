// Gallery Management JavaScript - Mavic's Resort
document.addEventListener('DOMContentLoaded', function() {
    // Initialize gallery management
    initializeGallery();
    setupEventListeners();
    setupDragAndDrop();
    
    // Ensure all modals are hidden on page load
    closeAllModals();
});

let selectedFiles = [];
let currentEditImageId = null;
let galleryImages = {};

function initializeGallery() {
    // Load gallery data into memory for quick access
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach(item => {
        const id = item.dataset.id;
        const img = item.querySelector('img');
        const title = item.querySelector('h4').textContent;
        const category = item.querySelector('.image-category').textContent;
        const date = item.querySelector('.image-date').textContent;
        
        galleryImages[id] = {
            id: id,
            title: title,
            category: category,
            date: date,
            src: img.src,
            alt: img.alt
        };
    });

    // Initialize masonry-like layout
    initializeMasonryLayout();
}

function setupEventListeners() {
    // Filter form submission
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            showLoadingOverlay();
        });
    }

    // Real-time search
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterGallery();
            }, 300);
        });
    }

    // Category and sort change
    const categorySelect = document.getElementById('category');
    const sortSelect = document.getElementById('sort');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', filterGallery);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', filterGallery);
    }

    // File input change - improved to prevent duplicates
    const fileInput = document.getElementById('gallery_images');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            console.log('File input changed, files:', e.target.files.length);
            handleFileSelect(e.target.files);
        });
    }

    // Modal close events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });

    // Keyboard events
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

function setupDragAndDrop() {
    const dropZone = document.querySelector('.upload-drop-zone');
    if (!dropZone) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.classList.add('dragover');
    }

    function unhighlight() {
        dropZone.classList.remove('dragover');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFileSelect(files);
    }
}

function handleFileSelect(files) {
    // Clear previous selections first
    selectedFiles = [];
    
    // Filter valid files
    const validFiles = Array.from(files).filter(file => {
        return file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024; // 5MB limit
    });

    if (validFiles.length === 0) {
        showAlert('No valid image files selected. Please select images under 5MB.', 'warning');
        return;
    }

    // Add valid files to selectedFiles array
    selectedFiles = validFiles;
    
    console.log('Selected files:', selectedFiles.length);
    
    displayFilePreview();
}

function displayFilePreview() {
    const previewContainer = document.getElementById('file-preview');
    
    // Clear existing previews first
    previewContainer.innerHTML = '';

    console.log('Displaying previews for:', selectedFiles.length, 'files');

    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Double-check that we're not adding duplicates
            const existingPreviews = previewContainer.querySelectorAll('.preview-item');
            if (existingPreviews.length >= selectedFiles.length) {
                return; // Prevent adding more previews than files
            }
            
            const previewItem = createPreviewItem(file, e.target.result, index);
            previewContainer.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    });
}

function createPreviewItem(file, src, index) {
    const item = document.createElement('div');
    item.className = 'preview-item';
    item.innerHTML = `
        <button type="button" class="preview-remove" onclick="removeFile(${index})">
            <i class="fas fa-times"></i>
        </button>
        <img src="${src}" alt="Preview" class="preview-image">
        <div class="preview-info">
            <div class="preview-title">${file.name}</div>
            <div class="preview-size">${formatFileSize(file.size)}</div>
        </div>
        <div class="preview-fields">
            <input type="text" name="titles[]" placeholder="Image title" value="${getFileNameWithoutExtension(file.name)}">
            <input type="text" name="descriptions[]" placeholder="Description (optional)">
            <input type="text" name="alt_texts[]" placeholder="Alt text" value="${getFileNameWithoutExtension(file.name)}">
            <label class="checkbox-group">
                <input type="checkbox" name="featured[]" value="${index}">
                <span>Featured</span>
            </label>
        </div>
    `;
    return item;
}

function removeFile(index) {
    console.log('Removing file at index:', index);
    
    selectedFiles.splice(index, 1);
    
    // Clear and rebuild preview to fix indexing
    displayFilePreview();
    
    // Update file input to match selectedFiles
    const fileInput = document.getElementById('gallery_images');
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    fileInput.files = dt.files;
    
    console.log('Files after removal:', selectedFiles.length);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getFileNameWithoutExtension(filename) {
    return filename.substring(0, filename.lastIndexOf('.')) || filename;
}

// Add this function to completely reset the upload form
function resetUploadForm() {
    selectedFiles = [];
    document.getElementById('uploadForm').reset();
    document.getElementById('file-preview').innerHTML = '';
    
    // Also clear the file input
    const fileInput = document.getElementById('gallery_images');
    if (fileInput) {
        fileInput.value = '';
    }
    
    console.log('Upload form reset');
}

// Modal Functions
function openUploadModal() {
    document.getElementById('uploadModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Use the comprehensive reset function
    resetUploadForm();
}

function openImageModal(imageId) {
    const image = galleryImages[imageId];
    if (!image) return;

    document.getElementById('image-title').textContent = image.title;
    document.getElementById('modal-image').src = image.src;
    document.getElementById('modal-image').alt = image.alt;
    document.getElementById('image-description').textContent = image.description || 'No description available.';
    document.getElementById('image-category').textContent = image.category || 'Uncategorized';
    document.getElementById('image-tags').textContent = image.tags || 'No tags';
    document.getElementById('image-date').textContent = image.date;

    document.getElementById('imageModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editImage(imageId) {
    currentEditImageId = imageId;
    
    // Fetch image data via AJAX
    fetch(`manage-gallery.php?ajax=get_image&id=${imageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditModal(data.image);
                openEditModal();
            } else {
                showAlert('Error loading image data: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading image data', 'error');
        });
}

function populateEditModal(image) {
    document.getElementById('edit_image_id').value = image.id;
    document.getElementById('edit_title').value = image.title;
    document.getElementById('edit_description').value = image.description || '';
    document.getElementById('edit_category').value = image.category || 'general';
    document.getElementById('edit_tags').value = image.tags || '';
    document.getElementById('edit_alt_text').value = image.alt_text || '';
    document.getElementById('edit_is_featured').checked = image.is_featured == 1;
}

function openEditModal() {
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentEditImageId = null;
}

let pendingDeleteImageId = null;

function deleteImage(imageId) {
    pendingDeleteImageId = imageId;
    openDeleteConfirmModal();
}

function openDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Set up the confirm button
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.onclick = function() {
        if (pendingDeleteImageId) {
            performImageDelete(pendingDeleteImageId);
            closeDeleteConfirmModal();
        }
    };
}

function closeDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    pendingDeleteImageId = null;
}

function performImageDelete(imageId) {
    showLoadingOverlay();

    // Create form data for AJAX request
    const formData = new FormData();
    formData.append('ajax', 'delete_image');
    formData.append('image_id', imageId);

    fetch('manage-gallery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingOverlay();
        
        if (data.success) {
            // Remove the image item from the DOM
            const imageItem = document.querySelector(`[data-id="${imageId}"]`);
            if (imageItem) {
                imageItem.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    imageItem.remove();
                    
                    // Update stats
                    updateImageStats();
                    
                    // Check if gallery is now empty
                    const remainingItems = document.querySelectorAll('.gallery-item');
                    if (remainingItems.length === 0) {
                        toggleEmptyState(true);
                    }
                }, 300);
            }
            
            showAlert(data.message, 'success');
        } else {
            showAlert('Error deleting image: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error:', error);
        showAlert('Error deleting image', 'error');
    });
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
    document.body.style.overflow = 'auto';
    
    // Clear any pending delete operation
    pendingDeleteImageId = null;
}

// Filter and Search Functions
function filterGallery() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const selectedCategory = document.getElementById('category').value;
    const sortBy = document.getElementById('sort').value;

    const galleryItems = document.querySelectorAll('.gallery-item');
    let visibleItems = [];

    galleryItems.forEach(item => {
        const title = item.querySelector('h4').textContent.toLowerCase();
        const category = item.querySelector('.image-category').textContent.toLowerCase();
        
        let showItem = true;

        // Filter by search term
        if (searchTerm && !title.includes(searchTerm) && !category.includes(searchTerm)) {
            showItem = false;
        }

        // Filter by category
        if (selectedCategory !== 'all' && category !== selectedCategory.toLowerCase()) {
            showItem = false;
        }

        if (showItem) {
            item.style.display = 'block';
            visibleItems.push(item);
        } else {
            item.style.display = 'none';
        }
    });

    // Sort visible items
    if (sortBy !== 'newest') {
        sortGalleryItems(visibleItems, sortBy);
    }

    // Show/hide empty state
    toggleEmptyState(visibleItems.length === 0);
}

function sortGalleryItems(items, sortBy) {
    const container = document.querySelector('.gallery-grid');
    
    items.sort((a, b) => {
        const aTitle = a.querySelector('h4').textContent;
        const bTitle = b.querySelector('h4').textContent;
        const aDate = a.querySelector('.image-date').textContent;
        const bDate = b.querySelector('.image-date').textContent;
        const aFeatured = a.querySelector('.featured-badge') !== null;
        const bFeatured = b.querySelector('.featured-badge') !== null;

        switch (sortBy) {
            case 'title':
                return aTitle.localeCompare(bTitle);
            case 'oldest':
                return new Date(aDate) - new Date(bDate);
            case 'featured':
                if (aFeatured && !bFeatured) return -1;
                if (!aFeatured && bFeatured) return 1;
                return new Date(bDate) - new Date(aDate);
            default:
                return 0;
        }
    });

    // Reorder DOM elements
    items.forEach(item => {
        container.appendChild(item);
    });
}

function toggleEmptyState(show) {
    let emptyState = document.querySelector('.empty-state');
    
    if (show && !emptyState) {
        emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No Images Found</h3>
            <p>Try adjusting your search criteria or filters.</p>
            <button type="button" class="btn btn-outline" onclick="clearFilters()">
                Clear Filters
            </button>
        `;
        document.querySelector('.gallery-grid').appendChild(emptyState);
    } else if (!show && emptyState) {
        emptyState.remove();
    }
}

function clearFilters() {
    document.getElementById('search').value = '';
    document.getElementById('category').value = 'all';
    document.getElementById('sort').value = 'newest';
    filterGallery();
}

// Utility Functions
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
}

function hideLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 300);
    }, 5000);
}

function initializeMasonryLayout() {
    // Simple masonry-like layout using CSS Grid
    const grid = document.querySelector('.gallery-grid');
    if (!grid) return;

    // This is handled by CSS Grid, but we could add more complex logic here
    // For example, adjusting row spans based on image aspect ratios
}

// Form validation
function validateUploadForm() {
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('gallery_images');
    
    if (!fileInput.files.length && selectedFiles.length === 0) {
        showAlert('Please select at least one image to upload.', 'warning');
        return false;
    }

    return true;
}

// Handle form submissions
document.addEventListener('submit', function(e) {
    if (e.target.id === 'uploadForm') {
        if (!validateUploadForm()) {
            e.preventDefault();
            return false;
        }
        showLoadingOverlay();
    }

    if (e.target.id === 'editForm') {
        showLoadingOverlay();
    }
});

// Keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+U or Cmd+U: Upload images
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            e.preventDefault();
            openUploadModal();
        }
        
        // Delete key: Delete selected image (when an image is focused)
        if (e.key === 'Delete') {
            const focusedItem = document.activeElement.closest('.gallery-item');
            if (focusedItem) {
                const imageId = focusedItem.dataset.id;
                deleteImage(imageId);
            }
        }
    });
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupKeyboardShortcuts();
    
    // Clean up URL parameters after showing success/error messages
    cleanupURLParameters();
    
    // Setup performance monitoring
    if ('performance' in window) {
        window.addEventListener('load', () => {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log(`Gallery loaded in ${perfData.loadEventEnd - perfData.loadEventStart}ms`);
        });
    }
});

// Clean up URL parameters to prevent form resubmission issues
function cleanupURLParameters() {
    const url = new URL(window.location);
    const hasStatusParams = url.searchParams.has('status') && url.searchParams.has('message');
    
    if (hasStatusParams) {
        // Remove status parameters after a brief delay to allow message display
        setTimeout(() => {
            const cleanUrl = new URL(window.location);
            cleanUrl.searchParams.delete('status');
            cleanUrl.searchParams.delete('message');
            
            // Update URL without page reload
            window.history.replaceState({}, document.title, cleanUrl.toString());
        }, 3000);
    }
};

console.log('Gallery Management System initialized successfully');