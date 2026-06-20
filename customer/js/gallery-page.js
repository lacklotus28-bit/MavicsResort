// Full Gallery Page JavaScript - Mavic's Resort
let allGalleryImages = [];
let displayedImages = [];
let currentImageIndex = 0;
let currentFilter = 'all';
let currentSort = 'featured';
let currentView = 'grid';
let searchTerm = '';
let imagesPerPage = 12;
let currentPage = 1;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadFullGallery();
    setupGalleryControls();
    setupSearchDebounce();
});

// Load all gallery images
async function loadFullGallery() {
    const loadingEl = document.getElementById('galleryLoading');
    const galleryGrid = document.getElementById('fullGalleryGrid');
    
    try {
        // Show loading
        if (loadingEl) loadingEl.style.display = 'flex';
        if (galleryGrid) galleryGrid.style.display = 'none';
        
        // Fetch all images
        const response = await fetch('../admin/api/get-gallery.php?limit=100');
        
        if (!response.ok) {
            throw new Error('Failed to fetch gallery');
        }
        
        const data = await response.json();
        
        console.log('Full Gallery API Response:', data);
        
        if (data.success && data.images && data.images.length > 0) {
            allGalleryImages = data.images;
            displayedImages = [...allGalleryImages];
            
            // Update counts
            updateGalleryCounts();
            
            // Render gallery
            renderFullGallery();
            
            // Load featured images
            loadFeaturedImages();
            
        } else {
            showEmptyState();
        }
        
    } catch (error) {
        console.error('Error loading gallery:', error);
        showPlaceholderGallery();
    } finally {
        // Hide loading
        if (loadingEl) loadingEl.style.display = 'none';
        if (galleryGrid) galleryGrid.style.display = 'grid';
    }
}

// Render gallery grid
function renderFullGallery() {
    const galleryGrid = document.getElementById('fullGalleryGrid');
    if (!galleryGrid) return;
    
    // Apply filters and sorting
    applyFiltersAndSort();
    
    // Calculate pagination
    const startIndex = 0;
    const endIndex = currentPage * imagesPerPage;
    const imagesToShow = displayedImages.slice(startIndex, endIndex);
    
    if (imagesToShow.length === 0) {
        showEmptyState();
        return;
    }
    
    // Hide empty state
    hideEmptyState();
    
    // Render images
    galleryGrid.innerHTML = imagesToShow.map((img, index) => {
        const globalIndex = allGalleryImages.findIndex(i => i.id === img.id);
        return createGalleryItem(img, globalIndex);
    }).join('');
    
    // Show/hide load more button
    updateLoadMoreButton();
    
    // Animate items
    animateGalleryItems();
    
    // Update counts
    updateGalleryCounts();
}

// Create gallery item HTML
function createGalleryItem(img, index) {
    const featuredBadge = img.is_featured == 1 ? '<div class="featured-badge"><i class="fas fa-star"></i></div>' : '';
    const categoryBadge = img.category ? `<span class="category-badge">${img.category}</span>` : '';
    
    return `
        <div class="gallery-item ${currentView}-view" data-category="${img.category || 'all'}" data-id="${img.id}">
            <div class="gallery-image" onclick="openLightbox(${index})">
                <img src="${img.image}" 
                     alt="${img.alt_text || img.title || 'Gallery Image'}" 
                     onerror="this.src='images/bg1.jpg'; this.onerror=null;"
                     loading="lazy">
                ${featuredBadge}
                <div class="gallery-overlay">
                    <div class="gallery-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                    <div class="overlay-info">
                        <p class="overlay-category">${categoryBadge}</p>
                    </div>
                </div>
            </div>
            <div class="gallery-caption">
                <h4>${img.title || 'Untitled'}</h4>
                ${img.description ? `<p>${img.description}</p>` : ''}
                ${img.tags ? `<div class="image-tags">${createTagsHTML(img.tags)}</div>` : ''}
            </div>
        </div>
    `;
}

// Create tags HTML
function createTagsHTML(tags) {
    if (!tags) return '';
    const tagArray = tags.split(',').map(t => t.trim()).filter(t => t);
    return tagArray.map(tag => `<span class="tag"><i class="fas fa-tag"></i> ${tag}</span>`).join('');
}

// Filter gallery
function filterGallery(category) {
    currentFilter = category;
    currentPage = 1;
    
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === category);
    });
    
    // Update filter status
    updateFilterStatus();
    
    // Re-render gallery
    renderFullGallery();
}

// Search gallery
function searchGallery() {
    const searchInput = document.getElementById('gallerySearch');
    searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    currentPage = 1;
    
    // Update filter status
    updateFilterStatus();
    
    // Re-render gallery
    renderFullGallery();
}

// Sort gallery
function sortGallery(sortBy) {
    currentSort = sortBy;
    renderFullGallery();
}

// Change view
function changeView(view) {
    currentView = view;
    
    // Update active view button
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    
    // Update gallery class
    const galleryGrid = document.getElementById('fullGalleryGrid');
    if (galleryGrid) {
        galleryGrid.className = `gallery-grid ${view}-view`;
    }
    
    // Re-render with new view
    renderFullGallery();
}

// Apply filters and sorting
function applyFiltersAndSort() {
    // Start with all images
    let filtered = [...allGalleryImages];
    
    // Apply category filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(img => img.category === currentFilter);
    }
    
    // Apply search filter
    if (searchTerm) {
        filtered = filtered.filter(img => {
            const title = (img.title || '').toLowerCase();
            const description = (img.description || '').toLowerCase();
            const tags = (img.tags || '').toLowerCase();
            const category = (img.category || '').toLowerCase();
            
            return title.includes(searchTerm) || 
                   description.includes(searchTerm) || 
                   tags.includes(searchTerm) ||
                   category.includes(searchTerm);
        });
    }
    
    // Apply sorting
    filtered.sort((a, b) => {
        switch (currentSort) {
            case 'featured':
                if (a.is_featured !== b.is_featured) {
                    return b.is_featured - a.is_featured;
                }
                return new Date(b.created_at) - new Date(a.created_at);
            
            case 'newest':
                return new Date(b.created_at) - new Date(a.created_at);
            
            case 'oldest':
                return new Date(a.created_at) - new Date(b.created_at);
            
            case 'title':
                return (a.title || '').localeCompare(b.title || '');
            
            default:
                return 0;
        }
    });
    
    displayedImages = filtered;
}

// Load more images
function loadMoreImages() {
    currentPage++;
    renderFullGallery();
    
    // Scroll to new content
    setTimeout(() => {
        const newItems = document.querySelectorAll('.gallery-item');
        if (newItems.length > 0) {
            const lastOldItem = newItems[Math.min(newItems.length - 1, (currentPage - 1) * imagesPerPage)];
            if (lastOldItem) {
                lastOldItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }, 100);
}

// Update load more button
function updateLoadMoreButton() {
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    if (!loadMoreContainer) return;
    
    const totalDisplayed = currentPage * imagesPerPage;
    const hasMore = displayedImages.length > totalDisplayed;
    
    loadMoreContainer.style.display = hasMore ? 'flex' : 'none';
}

// Update gallery counts
function updateGalleryCounts() {
    const visibleCount = document.getElementById('visibleCount');
    const totalCount = document.getElementById('totalCount');
    
    const displayed = Math.min(currentPage * imagesPerPage, displayedImages.length);
    
    if (visibleCount) visibleCount.textContent = displayed;
    if (totalCount) totalCount.textContent = displayedImages.length;
}

// Update filter status
function updateFilterStatus() {
    const filterStatus = document.getElementById('filterStatus');
    if (!filterStatus) return;
    
    let status = [];
    
    if (currentFilter !== 'all') {
        status.push(`Category: <strong>${currentFilter}</strong>`);
    }
    
    if (searchTerm) {
        status.push(`Search: <strong>"${searchTerm}"</strong>`);
    }
    
    if (status.length > 0) {
        filterStatus.innerHTML = status.join(' | ') + 
            ' <button class="clear-filters-btn" onclick="clearAllFilters()"><i class="fas fa-times"></i> Clear</button>';
        filterStatus.style.display = 'block';
    } else {
        filterStatus.style.display = 'none';
    }
}

// Clear all filters
function clearAllFilters() {
    currentFilter = 'all';
    searchTerm = '';
    currentPage = 1;
    
    // Reset UI
    const searchInput = document.getElementById('gallerySearch');
    if (searchInput) searchInput.value = '';
    
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === 'all');
    });
    
    updateFilterStatus();
    renderFullGallery();
}

// Load featured images
async function loadFeaturedImages() {
    const featuredGrid = document.getElementById('featuredGrid');
    if (!featuredGrid) return;
    
    const featured = allGalleryImages.filter(img => img.is_featured == 1).slice(0, 6);
    
    if (featured.length === 0) {
        featuredGrid.innerHTML = '<p class="no-featured">No featured images yet.</p>';
        return;
    }
    
    featuredGrid.innerHTML = featured.map((img, index) => {
        const globalIndex = allGalleryImages.findIndex(i => i.id === img.id);
        return `
            <div class="featured-item" onclick="openLightbox(${globalIndex})">
                <img src="${img.image}" alt="${img.alt_text || img.title}">
                <div class="featured-overlay">
                    <h4>${img.title}</h4>
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
        `;
    }).join('');
}

// Show placeholder gallery
function showPlaceholderGallery() {
    const placeholderImages = [
        { id: 1, title: 'Conference Room', category: 'venues', description: 'Modern conference room', image: '../admin/images/venues/venue_1758760558_0.jpg', is_featured: 1 },
        { id: 2, title: 'Poolside Terrace', category: 'venues', description: 'Beautiful outdoor venue', image: '../admin/images/venues/venue_1758760259_0.jpg', is_featured: 0 },
        { id: 3, title: 'Resort View', category: 'facilities', description: 'Scenic resort grounds', image: 'images/bg1.jpg', is_featured: 0 },
        { id: 4, title: 'Event Venue', category: 'events', description: 'Perfect for special occasions', image: 'images/venues/default.jpg', is_featured: 0 },
        { id: 5, title: 'Venue Interior', category: 'venues', description: 'Elegant interior design', image: '../admin/images/venues/default-venue.jpg', is_featured: 0 },
        { id: 6, title: 'Resort Facilities', category: 'facilities', description: 'World-class amenities', image: 'images/bg1.jpg', is_featured: 0 }
    ];
    
    allGalleryImages = placeholderImages;
    displayedImages = [...placeholderImages];
    renderFullGallery();
    loadFeaturedImages();
}

// Show/hide empty state
function showEmptyState() {
    const emptyState = document.getElementById('emptyState');
    const galleryGrid = document.getElementById('fullGalleryGrid');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    if (emptyState) emptyState.style.display = 'flex';
    if (galleryGrid) galleryGrid.style.display = 'none';
    if (loadMoreContainer) loadMoreContainer.style.display = 'none';
    
    updateGalleryCounts();
}

function hideEmptyState() {
    const emptyState = document.getElementById('emptyState');
    const galleryGrid = document.getElementById('fullGalleryGrid');
    
    if (emptyState) emptyState.style.display = 'none';
    if (galleryGrid) galleryGrid.style.display = 'grid';
}

// Animate gallery items
function animateGalleryItems() {
    const items = document.querySelectorAll('.gallery-item:not(.animated)');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.classList.add('animated', 'animate-in');
        }, index * 30);
    });
}

// Setup gallery controls
function setupGalleryControls() {
    // Search on Enter key
    const searchInput = document.getElementById('gallerySearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchGallery();
            }
        });
    }
}

// Setup search debounce
function setupSearchDebounce() {
    const searchInput = document.getElementById('gallerySearch');
    if (!searchInput) return;
    
    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchGallery();
        }, 500);
    });
}

// Enhanced lightbox with thumbnails
function openLightbox(index) {
    currentImageIndex = index;
    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxMeta = document.getElementById('lightboxMeta');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    if (!lightbox || !lightboxImage) return;
    
    const image = allGalleryImages[index];
    if (!image) return;
    
    lightboxImage.src = image.image;
    lightboxImage.alt = image.alt_text || image.title || 'Gallery Image';
    lightboxCaption.innerHTML = `
        <h3>${image.title || 'Untitled'}</h3>
        ${image.description ? `<p>${image.description}</p>` : ''}
    `;
    
    // Meta information
    if (lightboxMeta) {
        lightboxMeta.innerHTML = `
            ${image.category ? `<span class="meta-item"><i class="fas fa-folder"></i> ${image.category}</span>` : ''}
            ${image.tags ? `<span class="meta-item"><i class="fas fa-tags"></i> ${image.tags}</span>` : ''}
        `;
    }
    
    lightboxCounter.textContent = `${index + 1} / ${allGalleryImages.length}`;
    
    // Generate thumbnails
    generateLightboxThumbnails(index);
    
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Generate lightbox thumbnails
function generateLightboxThumbnails(currentIndex) {
    const thumbnailsContainer = document.getElementById('lightboxThumbnails');
    if (!thumbnailsContainer) return;
    
    // Show 5 thumbnails: 2 before, current, 2 after
    const start = Math.max(0, currentIndex - 2);
    const end = Math.min(allGalleryImages.length, currentIndex + 3);
    const thumbnails = allGalleryImages.slice(start, end);
    
    thumbnailsContainer.innerHTML = thumbnails.map((img, idx) => {
        const globalIdx = start + idx;
        const isActive = globalIdx === currentIndex ? 'active' : '';
        return `
            <div class="lightbox-thumbnail ${isActive}" onclick="jumpToImage(${globalIdx})">
                <img src="${img.image}" alt="${img.title}">
            </div>
        `;
    }).join('');
}

// Jump to specific image in lightbox
function jumpToImage(index) {
    currentImageIndex = index;
    updateLightboxImage();
}

// Close lightbox
function closeLightbox() {
    const lightbox = document.getElementById('galleryLightbox');
    if (!lightbox) return;
    
    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Next image in lightbox
function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % allGalleryImages.length;
    updateLightboxImage();
}

// Previous image in lightbox
function previousImage() {
    currentImageIndex = (currentImageIndex - 1 + allGalleryImages.length) % allGalleryImages.length;
    updateLightboxImage();
}

// Update lightbox image
function updateLightboxImage() {
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxMeta = document.getElementById('lightboxMeta');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    if (!lightboxImage) return;
    
    const image = allGalleryImages[currentImageIndex];
    if (!image) return;
    
    // Fade out
    lightboxImage.style.opacity = '0';
    
    setTimeout(() => {
        lightboxImage.src = image.image;
        lightboxImage.alt = image.alt_text || image.title || 'Gallery Image';
        
        lightboxCaption.innerHTML = `
            <h3>${image.title || 'Untitled'}</h3>
            ${image.description ? `<p>${image.description}</p>` : ''}
        `;
        
        // Meta information
        if (lightboxMeta) {
            lightboxMeta.innerHTML = `
                ${image.category ? `<span class="meta-item"><i class="fas fa-folder"></i> ${image.category}</span>` : ''}
                ${image.tags ? `<span class="meta-item"><i class="fas fa-tags"></i> ${image.tags}</span>` : ''}
            `;
        }
        
        lightboxCounter.textContent = `${currentImageIndex + 1} / ${allGalleryImages.length}`;
        
        // Update thumbnails
        generateLightboxThumbnails(currentImageIndex);
        
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

// Export functions to window
window.filterGallery = filterGallery;
window.searchGallery = searchGallery;
window.sortGallery = sortGallery;
window.changeView = changeView;
window.loadMoreImages = loadMoreImages;
window.clearAllFilters = clearAllFilters;
window.jumpToImage = jumpToImage;
window.openLightbox = openLightbox;
window.closeLightbox = closeLightbox;
window.nextImage = nextImage;
window.previousImage = previousImage;

console.log('Gallery page module loaded successfully');
