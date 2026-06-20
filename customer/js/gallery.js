// Gallery functionality for Customer Site
let galleryImages = [];
let currentImageIndex = 0;
let currentFilter = 'all';

// Load gallery when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadGallery();
    setupGalleryFilters();
});

async function loadGallery() {
    const galleryGrid = document.getElementById('gallery-grid');
    
    if (!galleryGrid) return;
    
    try {
        // Show loading state
        galleryGrid.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading gallery...</p>
            </div>
        `;
        
        // Fetch gallery images from API
        const response = await fetch('../admin/api/get-gallery.php?limit=12');
        
        if (!response.ok) {
            throw new Error('Failed to fetch gallery');
        }
        
        const data = await response.json();
        
        console.log('Gallery API Response:', data);
        
        if (data.success && data.images && data.images.length > 0) {
            galleryImages = data.images;
            renderGallery(galleryImages);
        } else {
            // Show placeholder gallery if no images
            console.log('No images found, showing placeholder gallery');
            showPlaceholderGallery();
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
        showPlaceholderGallery();
    }
}

function showPlaceholderGallery() {
    const galleryGrid = document.getElementById('gallery-grid');
    if (!galleryGrid) return;
    
    // Create placeholder images based on available assets
    const placeholderImages = [
        { 
            id: 1, 
            title: 'Conference Room', 
            category: 'venues', 
            description: 'Modern conference room',
            image: '../admin/images/venues/venue_1758760558_0.jpg' 
        },
        { 
            id: 2, 
            title: 'Poolside Terrace', 
            category: 'venues',
            description: 'Beautiful outdoor venue',
            image: '../admin/images/venues/venue_1758760259_0.jpg' 
        },
        { 
            id: 3, 
            title: 'Resort View', 
            category: 'facilities',
            description: 'Scenic resort grounds',
            image: 'images/bg1.jpg' 
        },
        { 
            id: 4, 
            title: 'Event Venue', 
            category: 'events',
            description: 'Perfect for special occasions',
            image: 'images/venues/default.jpg' 
        },
        { 
            id: 5, 
            title: 'Venue Interior', 
            category: 'venues',
            description: 'Elegant interior design',
            image: '../admin/images/venues/default-venue.jpg' 
        },
        { 
            id: 6, 
            title: 'Resort Facilities', 
            category: 'facilities',
            description: 'World-class amenities',
            image: 'images/bg1.jpg' 
        }
    ];
    
    galleryImages = placeholderImages;
    renderGallery(placeholderImages);
}

function renderGallery(images) {
    const galleryGrid = document.getElementById('gallery-grid');
    if (!galleryGrid) return;
    
    if (images.length === 0) {
        galleryGrid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <p>No images found in this category</p>
            </div>
        `;
        return;
    }
    
    galleryGrid.innerHTML = images.map((img, index) => `
        <div class="gallery-item" data-category="${img.category || 'all'}" onclick="openLightbox(${index})">
            <div class="gallery-image">
                <img src="${img.image}" 
                     alt="${img.alt_text || img.title || 'Gallery Image'}" 
                     onerror="this.src='images/bg1.jpg'; this.onerror=null;"
                     loading="lazy">
                <div class="gallery-overlay">
                    <div class="gallery-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
            </div>
            <div class="gallery-caption">
                <h4>${img.title || 'Untitled'}</h4>
                ${img.description ? `<p>${img.description}</p>` : ''}
            </div>
        </div>
    `).join('');
    
    // Animate items
    setTimeout(() => {
        document.querySelectorAll('.gallery-item').forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animate-in');
            }, index * 50);
        });
    }, 100);
}

function setupGalleryFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    if (filterButtons.length === 0) return;
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Get filter value
            const filter = this.dataset.filter;
            currentFilter = filter;
            
            // Filter images
            filterGallery(filter);
        });
    });
}

function filterGallery(filter) {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        const category = item.dataset.category;
        
        if (filter === 'all' || category === filter) {
            item.style.display = 'block';
            item.classList.remove('animate-in');
            setTimeout(() => item.classList.add('animate-in'), 50);
        } else {
            item.style.display = 'none';
            item.classList.remove('animate-in');
        }
    });
    
    // Check if any items are visible
    const visibleItems = Array.from(galleryItems).filter(item => item.style.display !== 'none');
    
    if (visibleItems.length === 0) {
        const galleryGrid = document.getElementById('gallery-grid');
        if (galleryGrid && !galleryGrid.querySelector('.empty-state')) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-images"></i>
                <p>No images found in this category</p>
            `;
            galleryGrid.appendChild(emptyState);
        }
    } else {
        // Remove empty state if it exists
        const emptyState = document.querySelector('.gallery-grid .empty-state');
        if (emptyState && emptyState.parentElement.className === 'gallery-grid') {
            emptyState.remove();
        }
    }
}

// Lightbox functions
function openLightbox(index) {
    currentImageIndex = index;
    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    if (!lightbox || !lightboxImage) {
        console.error('Lightbox elements not found');
        return;
    }
    
    const image = galleryImages[index];
    
    if (!image) {
        console.error('Image not found at index:', index);
        return;
    }
    
    lightboxImage.src = image.image;
    lightboxImage.alt = image.alt_text || image.title || 'Gallery Image';
    lightboxCaption.textContent = image.title || 'Untitled';
    lightboxCounter.textContent = `${index + 1} / ${galleryImages.length}`;
    
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('galleryLightbox');
    if (!lightbox) return;
    
    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto';
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    updateLightboxImage();
}

function previousImage() {
    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    updateLightboxImage();
}

function updateLightboxImage() {
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    if (!lightboxImage) return;
    
    const image = galleryImages[currentImageIndex];
    
    if (!image) return;
    
    // Fade out
    lightboxImage.style.opacity = '0';
    
    setTimeout(() => {
        lightboxImage.src = image.image;
        lightboxImage.alt = image.alt_text || image.title || 'Gallery Image';
        lightboxCaption.textContent = image.title || 'Untitled';
        lightboxCounter.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
        
        // Fade in
        lightboxImage.style.opacity = '1';
    }, 200);
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('galleryLightbox');
    if (!lightbox || !lightbox.classList.contains('active')) return;
    
    if (e.key === 'ArrowLeft') {
        previousImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    } else if (e.key === 'Escape') {
        closeLightbox();
    }
});

// Export functions to window for onclick handlers
window.openLightbox = openLightbox;
window.closeLightbox = closeLightbox;
window.nextImage = nextImage;
window.previousImage = previousImage;

console.log('Gallery module loaded successfully');
