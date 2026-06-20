<?php
// Page configuration
$pageTitle = "Welcome to Mavic's Resort";
$pageDescription = "Experience luxury and comfort at Mavic's Resort. Book your perfect venue for weddings, corporate events, birthdays, and special occasions.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/home.css', 'styles/gallery.css', 'styles/gallery-fixes.css'];
$pageJSFiles = ['js/home.js', 'js/gallery.js'];

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main id="main-content">
    <!-- Hero Section -->
    <section id="hero-section" class="hero">
        <div class="hero-slider">
            <div class="hero-slide active" style="background-image: url('images/venues/hero-1.jpg');">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="container">
                        <div class="hero-text">
                            <h1 class="hero-title">Welcome to Mavic's Resort</h1>
                            <p class="hero-subtitle">Where Dreams Meet Reality</p>
                            <p class="hero-description">
                                Create unforgettable memories in our stunning venues. From intimate gatherings 
                                to grand celebrations, we provide the perfect setting for your special occasions.
                            </p>
                            <div class="hero-buttons">
                                <a href="venues.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-eye"></i> Explore Venues
                                </a>
                                <?php if ($isLoggedIn): ?>
                                    <a href="booking.php" class="btn btn-outline btn-large">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="#" onclick="AuthManager.requireAuth()" class="btn btn-outline btn-large">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hero-slide" style="background-image: url('images/venues/hero-2.jpg');">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="container">
                        <div class="hero-text">
                            <h1 class="hero-title">Exceptional Venues</h1>
                            <p class="hero-subtitle">For Every Special Occasion</p>
                            <p class="hero-description">
                                Our beautifully designed venues offer the perfect backdrop for weddings, 
                                corporate events, birthdays, and all your memorable celebrations.
                            </p>
                            <div class="hero-buttons">
                                <a href="venues.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-images"></i> View Gallery
                                </a>
                                <a href="contact.php" class="btn btn-outline btn-large">
                                    <i class="fas fa-phone"></i> Contact Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hero-slide" style="background-image: url('images/venues/hero-3.jpg');">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="container">
                        <div class="hero-text">
                            <h1 class="hero-title">Premium Service</h1>
                            <p class="hero-subtitle">Tailored to Your Needs</p>
                            <p class="hero-description">
                                Our dedicated team ensures every detail is perfect, providing personalized 
                                service that exceeds your expectations and brings your vision to life.
                            </p>
                            <div class="hero-buttons">
                                <a href="about.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-info-circle"></i> Learn More
                                </a>
                                <?php if (!$isLoggedIn): ?>
                                    <a href="register.php" class="btn btn-outline btn-large">
                                        <i class="fas fa-user-plus"></i> Get Started
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hero Navigation -->
        <div class="hero-navigation">
            <button class="hero-nav-btn prev" onclick="previousSlide()" aria-label="Previous slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="hero-nav-btn next" onclick="nextSlide()" aria-label="Next slide">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Hero Indicators -->
        <div class="hero-indicators">
            <button class="indicator active" onclick="currentSlide(1)" aria-label="Slide 1"></button>
            <button class="indicator" onclick="currentSlide(2)" aria-label="Slide 2"></button>
            <button class="indicator" onclick="currentSlide(3)" aria-label="Slide 3"></button>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <a href="#gallery-section" onclick="scrollTo('gallery-section', 80)">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery-section" class="gallery-section py-4">
        <div class="container">
            <div class="section-header text-center">
                <h2>Explore Our Gallery</h2>
                <p>Browse through our stunning collection of venues and memorable events</p>
            </div>
            
            <!-- Gallery Filter -->
            <div class="gallery-filter">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th"></i> All Photos
                </button>
                <button class="filter-btn" data-filter="venues">
                    <i class="fas fa-building"></i> Venues
                </button>
                <button class="filter-btn" data-filter="events">
                    <i class="fas fa-calendar-alt"></i> Events
                </button>
                <button class="filter-btn" data-filter="facilities">
                    <i class="fas fa-swimming-pool"></i> Facilities
                </button>
            </div>
            
            <!-- Gallery Grid -->
            <div class="gallery-grid" id="gallery-grid">
                <!-- Gallery items will be loaded dynamically -->
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Loading gallery...</p>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="gallery.php" class="btn btn-primary">
                    <i class="fas fa-images"></i> View Full Gallery
                </a>
            </div>
        </div>
    </section>

    <!-- Venues Preview Section -->
    <section id="venues-preview" class="venues-preview py-4">
        <div class="container">
            <div class="section-header text-center">
                <h2>Our Beautiful Venues</h2>
                <p>Explore our carefully curated spaces designed to make your events extraordinary</p>
            </div>
            
            <div class="venues-carousel" id="venues-carousel">
                <!-- Venues will be loaded dynamically -->
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Loading venues...</p>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="venues.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View All Venues
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section py-4">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number" data-target="500">0</div>
                    <div class="stat-label">Happy Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-target="1000">0</div>
                    <div class="stat-label">Events Hosted</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-target="15">0</div>
                    <div class="stat-label">Venue Options</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-target="5">0</div>
                    <div class="stat-label">Years Experience</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-4">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Plan Your Perfect Event?</h2>
                <p>Let us help you create unforgettable memories. Get in touch with our event specialists today.</p>
                <div class="cta-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="booking.php" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-plus"></i> Start Booking
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    <?php endif; ?>
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
            <img id="lightboxImage" src="" alt="Gallery Image">
            <div class="lightbox-caption" id="lightboxCaption"></div>
        </div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
