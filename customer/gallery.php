<?php
// Gallery Page - Mavic's Resort
$pageTitle = "Gallery - Mavic's Resort";
$pageDescription = "Browse through our stunning collection of resort venues, facilities, and memorable events at Mavic's Resort.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/gallery.css', 'styles/gallery-fixes.css', 'styles/gallery-page.css'];
$pageJSFiles = ['js/gallery-page.js'];

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main id="main-content">
    <!-- Page Header -->
    <section class="page-header">
        <div class="header-overlay"></div>
        <div class="container">
            <div class="header-content">
                <h1 class="page-title">Our Gallery</h1>
                <p class="page-subtitle">Explore the beauty and elegance of Mavic's Resort</p>
                <nav class="breadcrumb">
                    <a href="index.php">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Gallery</span>
                </nav>
            </div>
        </div>
    </section>

    <!-- Gallery Controls -->
    <section class="gallery-controls-section py-4">
        <div class="container">
            <div class="controls-wrapper">
                <!-- Search Bar -->
                <div class="search-container">
                    <input type="text" 
                           id="gallerySearch" 
                           class="search-input" 
                           placeholder="Search images by title, description, or tags...">
                    <button class="search-btn" onclick="searchGallery()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Filter Buttons -->
                <div class="gallery-filter">
                    <button class="filter-btn active" data-filter="all" onclick="filterGallery('all')">
                        <i class="fas fa-th"></i> All Photos
                    </button>
                    <button class="filter-btn" data-filter="venues" onclick="filterGallery('venues')">
                        <i class="fas fa-building"></i> Venues
                    </button>
                    <button class="filter-btn" data-filter="events" onclick="filterGallery('events')">
                        <i class="fas fa-calendar-alt"></i> Events
                    </button>
                    <button class="filter-btn" data-filter="facilities" onclick="filterGallery('facilities')">
                        <i class="fas fa-swimming-pool"></i> Facilities
                    </button>
                    <button class="filter-btn" data-filter="food" onclick="filterGallery('food')">
                        <i class="fas fa-utensils"></i> Food & Dining
                    </button>
                    <button class="filter-btn" data-filter="nature" onclick="filterGallery('nature')">
                        <i class="fas fa-leaf"></i> Nature
                    </button>
                </div>

                <!-- View Options & Sort -->
                <div class="view-controls">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" onclick="changeView('grid')" title="Grid View">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="masonry" onclick="changeView('masonry')" title="Masonry View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="list" onclick="changeView('list')" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>

                    <select id="sortBy" class="sort-select" onchange="sortGallery(this.value)">
                        <option value="featured">Featured First</option>
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title">Title A-Z</option>
                    </select>
                </div>
            </div>

            <!-- Gallery Info Bar -->
            <div class="gallery-info-bar">
                <p class="result-count">
                    Showing <span id="visibleCount">0</span> of <span id="totalCount">0</span> images
                </p>
                <p class="filter-status" id="filterStatus"></p>
            </div>
        </div>
    </section>

    <!-- Gallery Grid Section -->
    <section class="full-gallery-section py-4">
        <div class="container">
            <!-- Loading State -->
            <div id="galleryLoading" class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading gallery...</p>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-grid" id="fullGalleryGrid">
                <!-- Images will be loaded dynamically -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-images"></i>
                <h3>No Images Found</h3>
                <p>Try adjusting your search or filter criteria.</p>
                <button class="btn btn-outline" onclick="clearAllFilters()">
                    <i class="fas fa-redo"></i> Clear Filters
                </button>
            </div>

            <!-- Load More Button -->
            <div class="load-more-container" id="loadMoreContainer" style="display: none;">
                <button class="btn btn-primary btn-large" onclick="loadMoreImages()">
                    <i class="fas fa-plus-circle"></i> Load More Images
                </button>
            </div>
        </div>
    </section>

    <!-- Featured Section -->
    <section class="featured-gallery-section py-4">
        <div class="container">
            <div class="section-header text-center">
                <h2>Featured Highlights</h2>
                <p>Our most stunning captures</p>
            </div>
            
            <div class="featured-grid" id="featuredGrid">
                <!-- Featured images will be loaded here -->
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="gallery-cta-section py-4">
        <div class="container">
            <div class="cta-content text-center">
                <h2>Ready to Experience This Beauty in Person?</h2>
                <p>Book your event at Mavic's Resort and create your own memorable moments.</p>
                <div class="cta-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="booking.php" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-plus"></i> Book Now
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Get Started
                        </a>
                    <?php endif; ?>
                    <a href="venues.php" class="btn btn-outline btn-large">
                        <i class="fas fa-eye"></i> View Venues
                    </a>
                    <a href="contact.php" class="btn btn-outline btn-large">
                        <i class="fas fa-phone"></i> Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Gallery Lightbox Modal -->
<div id="galleryLightbox" class="lightbox-modal">
    <div class="lightbox-overlay" onclick="closeLightbox()"></div>
    <div class="lightbox-content">
        <button class="lightbox-close" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <button class="lightbox-prev" onclick="previousImage()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="lightbox-next" onclick="nextImage()">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="lightbox-image-container">
            <img id="lightboxImage" src="" alt="">
            <div class="lightbox-info">
                <div class="lightbox-caption" id="lightboxCaption"></div>
                <div class="lightbox-meta" id="lightboxMeta"></div>
            </div>
        </div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
        <div class="lightbox-thumbnails" id="lightboxThumbnails"></div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
