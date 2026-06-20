// Homepage JavaScript - Enhanced Image Handling Version

// Hero Slider Variables
let currentSlide = 1;
let currentTestimonial = 1;
const totalSlides = 3;
const totalTestimonials = 3;
let slideInterval;
let testimonialInterval;

// Initialize homepage functionality
document.addEventListener('DOMContentLoaded', function() {
    initHeroSlider();
    initTestimonialCarousel();
    loadVenuesPreview();
    initStatsAnimation();
    initScrollAnimations();
});

// Hero Slider Functions
function initHeroSlider() {
    // Start auto-slide
    slideInterval = setInterval(nextSlide, 5000);
    
    // Pause on hover
    const heroSection = document.querySelector('.hero');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });
        
        heroSection.addEventListener('mouseleave', () => {
            slideInterval = setInterval(nextSlide, 5000);
        });
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            previousSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        }
    });
}

function nextSlide() {
    currentSlide = currentSlide >= totalSlides ? 1 : currentSlide + 1;
    showSlide(currentSlide);
}

function previousSlide() {
    currentSlide = currentSlide <= 1 ? totalSlides : currentSlide - 1;
    showSlide(currentSlide);
}

function currentSlideIndex(n) {
    currentSlide = n;
    showSlide(currentSlide);
}

function showSlide(n) {
    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.hero-indicators .indicator');
    
    // Remove active class from all slides
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(indicator => indicator.classList.remove('active'));
    
    // Add active class to current slide
    if (slides[n - 1]) {
        slides[n - 1].classList.add('active');
    }
    if (indicators[n - 1]) {
        indicators[n - 1].classList.add('active');
    }
    
    // Reset auto-slide timer
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
}

// Testimonial Carousel Functions
function initTestimonialCarousel() {
    const testimonialSection = document.querySelector('.testimonials-section');
    if (testimonialSection) {
        testimonialInterval = setInterval(nextTestimonial, 7000);
        
        testimonialSection.addEventListener('mouseenter', () => {
            clearInterval(testimonialInterval);
        });
        
        testimonialSection.addEventListener('mouseleave', () => {
            testimonialInterval = setInterval(nextTestimonial, 7000);
        });
    }
}

function nextTestimonial() {
    currentTestimonial = currentTestimonial >= totalTestimonials ? 1 : currentTestimonial + 1;
    showTestimonial(currentTestimonial);
}

function previousTestimonial() {
    currentTestimonial = currentTestimonial <= 1 ? totalTestimonials : currentTestimonial - 1;
    showTestimonial(currentTestimonial);
}

function currentTestimonialIndex(n) {
    currentTestimonial = n;
    showTestimonial(currentTestimonial);
}

function showTestimonial(n) {
    const testimonials = document.querySelectorAll('.testimonial-item');
    const indicators = document.querySelectorAll('.testimonial-indicators .indicator');
    
    testimonials.forEach(testimonial => testimonial.classList.remove('active'));
    indicators.forEach(indicator => indicator.classList.remove('active'));
    
    if (testimonials[n - 1]) {
        testimonials[n - 1].classList.add('active');
    }
    if (indicators[n - 1]) {
        indicators[n - 1].classList.add('active');
    }
    
    clearInterval(testimonialInterval);
    testimonialInterval = setInterval(nextTestimonial, 7000);
}

// Enhanced Load Venues Preview with better debugging
async function loadVenuesPreview() {
    const venuesContainer = document.getElementById('venues-carousel');
    
    if (!venuesContainer) {
        console.error('Venues container not found');
        return;
    }
    
    try {
        // Show loading state
        venuesContainer.innerHTML = `
            <div class="loading-container text-center">
                <div class="loading-spinner"></div>
                <p>Loading venues...</p>
            </div>
        `;
        
        console.log('Fetching venues from API...');
        
        // Fetch venues from database
        const venues = await fetchVenuesFromDatabase();
        
        console.log('Venues received:', venues);
        
        if (venues && venues.length > 0) {
            renderVenuesPreview(venues);
        } else {
            showVenuesError('No venues available at the moment.');
        }
    } catch (error) {
        console.error('Error loading venues:', error);
        showVenuesError('Failed to load venues. Please try again later.');
    }
}

async function fetchVenuesFromDatabase() {
    try {
        // Use correct API path relative to customer folder
        const apiPath = 'api/venues.php?limit=6';
        
        console.log('Fetching from:', apiPath);
        
        const response = await fetch(apiPath, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Full API Response:', data);
        
        if (data.success && data.venues) {
            // Log image debugging info if available
            if (data.debug_info) {
                console.log('Debug info from API:', data.debug_info);
            }
            
            return data.venues.map(venue => {
                console.log(`Processing venue: ${venue.name}`, {
                    id: venue.id,
                    featured_image: venue.featured_image,
                    images: venue.images
                });
                
                // Ensure image path works from home page
                let imagePath = venue.featured_image || 'images/venues/default.jpg';
                
                // If image path starts with ../, keep it as is
                // Otherwise, prepend the correct path
                if (!imagePath.startsWith('../') && !imagePath.startsWith('http')) {
                    imagePath = '../admin/images/venues/' + imagePath;
                }
                
                return {
                    id: venue.id,
                    name: venue.name || 'Unnamed Venue',
                    description: venue.description || 'No description available',
                    image: imagePath,
                    capacity: parseInt(venue.capacity) || 0,
                    price_per_hour: parseFloat(venue.price_per_hour) || 0,
                    amenities: venue.amenities || [],
                    total_bookings: parseInt(venue.total_bookings) || 0
                };
            });
        } else {
            throw new Error(data.message || 'Failed to fetch venues from database');
        }
        
    } catch (error) {
        console.error('Database fetch error:', error);
        // Return sample data as fallback
        return getSampleVenuesFromDatabase();
    }
}

// Sample venues that match the database structure
function getSampleVenuesFromDatabase() {
    console.log('Loading sample venues (database connection fallback)');
    return [
        {
            id: 3,
            name: 'Conference Room',
            description: 'Modern conference room for business meetings',
            image: '../admin/images/venues/venue_1758760558_0.jpg',
            capacity: 50,
            price_per_hour: 600.00,
            amenities: ['Projector', 'Whiteboard', 'Air Conditioning'],
            total_bookings: 0
        },
        {
            id: 4,
            name: 'Poolside Terrace',
            description: 'Beautiful poolside venue for outdoor events',
            image: '../admin/images/venues/venue_1758760259_0.jpg',
            capacity: 5,
            price_per_hour: 500.00,
            amenities: ['Stage'],
            total_bookings: 0
        }
    ];
}

function renderVenuesPreview(venues) {
    const venuesContainer = document.getElementById('venues-carousel');
    
    if (!venuesContainer) {
        console.error('Venues container not found');
        return;
    }
    
    console.log('Rendering venues:', venues);
    
    // Determine CSS class based on number of venues
    let gridClass = 'venues-grid';
    if (venues.length === 1) {
        gridClass += ' single-venue';
    } else if (venues.length === 2) {
        gridClass += ' two-venues';
    }
    
    const venuesHTML = `
        <div class="${gridClass}">
            ${venues.map(venue => {
                console.log(`Rendering venue ${venue.id}: ${venue.name} with image: ${venue.image}`);
                return `
                    <div class="venue-card" onclick="viewVenueDetails(${venue.id})" style="cursor: pointer;">
                        <div class="venue-image">
                            <img src="${venue.image}" 
                                 alt="${venue.name}" 
                                 onerror="handleImageError(this, '${venue.name}', ${venue.id})"
                                 onload="console.log('Image loaded successfully: ${venue.image}')">
                            <div class="venue-overlay"></div>
                        </div>
                        <div class="venue-info">
                            <h3>${venue.name}</h3>
                            <p>${venue.description}</p>
                            <div class="venue-meta">
                                <span><i class="fas fa-users"></i> Up to ${venue.capacity} guests</span>
                                <span><i class="fas fa-tag"></i> ${formatCurrency(venue.price_per_hour)}/hour</span>
                            </div>
                            ${venue.amenities && venue.amenities.length > 0 ? `
                                <div class="venue-amenities">
                                    <small><i class="fas fa-check"></i> ${venue.amenities.slice(0, 3).join(', ')}</small>
                                    ${venue.amenities.length > 3 ? '<small>...</small>' : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
    
    venuesContainer.innerHTML = venuesHTML;
    console.log('Venues rendered successfully:', venues.length, 'venues');
    
    // Log all image sources for debugging
    const imageElements = venuesContainer.querySelectorAll('img');
    imageElements.forEach((img, index) => {
        console.log(`Image ${index + 1} src:`, img.src);
    });
}

function showVenuesError(message) {
    const venuesContainer = document.getElementById('venues-carousel');
    if (venuesContainer) {
        venuesContainer.innerHTML = `
            <div class="error-container text-center">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--primary-brown, #8B4513); margin-bottom: 1rem;"></i>
                <p style="color: #666; font-size: 1.1rem;">${message}</p>
                <button onclick="loadVenuesPreview()" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh"></i> Try Again
                </button>
            </div>
        `;
    }
}

function viewVenueDetails(venueId) {
    // Check if user is logged in (check session or local storage)
    const isLoggedIn = checkAuthStatus();
    
    if (isLoggedIn) {
        window.location.href = `venues.php?id=${venueId}`;
    } else {
        // Show login prompt or redirect to login
        if (typeof AuthManager !== 'undefined' && AuthManager.requireAuth) {
            if (AuthManager.requireAuth()) {
                window.location.href = `venues.php?id=${venueId}`;
            }
        } else {
            // Simple redirect to login with return URL
            window.location.href = `login.php?return=${encodeURIComponent('venues.php?id=' + venueId)}`;
        }
    }
}

function checkAuthStatus() {
    // Check if user session exists (this would be set by header.php)
    return typeof window.userSession !== 'undefined' && window.userSession !== null;
}

// Statistics Animation - Updated with real database numbers
function initStatsAnimation() {
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStats();
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        observer.observe(statsSection);
    }
}

function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(statNumber => {
        const target = parseInt(statNumber.getAttribute('data-target')) || 0;
        const increment = target / 100;
        let current = 0;
        
        const updateStat = () => {
            if (current < target) {
                current += increment;
                statNumber.textContent = Math.ceil(current);
                setTimeout(updateStat, 20);
            } else {
                statNumber.textContent = target;
            }
        };
        
        updateStat();
    });
}

// Load real stats from database
async function loadRealStats() {
    try {
        const response = await fetch('api/stats.php');
        const data = await response.json();
        
        if (data.success) {
            // Update stat numbers with real data
            const statElements = {
                'clients': document.querySelector('[data-target="500"]'),
                'events': document.querySelector('[data-target="1000"]'),
                'venues': document.querySelector('[data-target="15"]'),
                'years': document.querySelector('[data-target="5"]')
            };
            
            if (statElements.clients && data.stats.total_customers) {
                statElements.clients.setAttribute('data-target', data.stats.total_customers);
            }
            if (statElements.events && data.stats.total_bookings) {
                statElements.events.setAttribute('data-target', data.stats.total_bookings);
            }
            if (statElements.venues && data.stats.total_venues) {
                statElements.venues.setAttribute('data-target', data.stats.total_venues);
            }
        }
    } catch (error) {
        console.log('Could not load real stats, using default values');
    }
}

// Scroll Animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const elementsToAnimate = document.querySelectorAll('.feature-card, .venue-card, .section-header');
    elementsToAnimate.forEach(element => {
        observer.observe(element);
    });
}

// Touch/Swipe Support for Mobile
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const swipeDistance = touchEndX - touchStartX;
    
    if (Math.abs(swipeDistance) > swipeThreshold) {
        if (swipeDistance > 0) {
            // Swipe right - previous slide
            previousSlide();
        } else {
            // Swipe left - next slide
            nextSlide();
        }
    }
}

// Utility Functions
function formatCurrency(amount, currency = 'PHP') {
    const numAmount = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numAmount);
}

// Enhanced Image error handler with better debugging
function handleImageError(img, venueName, venueId) {
    console.error(`Image failed to load for venue ${venueName} (ID: ${venueId}):`, img.src);
    
    if (img && img.parentNode) {
        const currentSrc = img.src;
        const fileName = currentSrc.split('/').pop();
        
        console.log(`Attempting to fix image for: ${fileName}`);
        
        // Try different possible paths
        const altPaths = [
            '../admin/images/venues/' + fileName,
            'admin/images/venues/' + fileName,
            '../images/venues/' + fileName,
            'images/venues/' + fileName,
            '../uploads/venues/' + fileName,
            '../admin/uploads/venues/' + fileName,
            '../admin/images/venues/default.jpg',
            'images/venues/default.jpg'
        ];
        
        let tried = parseInt(img.dataset.tried || '0');
        
        if (tried < altPaths.length) {
            img.dataset.tried = tried + 1;
            const newPath = altPaths[tried];
            console.log(`Trying alternative path ${tried + 1}: ${newPath}`);
            img.src = newPath;
        } else {
            // All alternatives failed, show placeholder
            console.error(`All image paths failed for venue ${venueName}. Creating placeholder.`);
            
            img.style.display = 'none';
            
            if (!img.parentNode.querySelector('.image-placeholder')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-placeholder';
                placeholder.innerHTML = `
                    <i class="fas fa-image" style="font-size: 2rem; color: #8B4513; margin-bottom: 10px;"></i>
                    <span style="font-size: 0.9rem; color: #8B4513;">${venueName}</span>
                    <small style="display: block; margin-top: 5px; opacity: 0.7; color: #666;">Image: ${fileName}</small>
                    <small style="display: block; opacity: 0.5; color: #999;">No image available</small>
                `;
                placeholder.style.cssText = `
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 250px;
                    background: linear-gradient(135deg, #f5f5f5, #e8e8e8);
                    color: #8B4513;
                    text-align: center;
                    border-radius: 8px;
                    border: 2px dashed #ddd;
                `;
                img.parentNode.appendChild(placeholder);
            }
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load real statistics if available
    loadRealStats();
    
    // Add CSS for venue amenities
    const style = document.createElement('style');
    style.textContent = `
        .venue-amenities {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .venue-amenities small {
            color: #666;
            font-size: 0.85rem;
        }
        .venue-amenities i {
            color: var(--primary-brown, #8B4513);
            margin-right: 5px;
        }
        .image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            color: #6c757d;
            text-align: center;
            padding: 20px;
        }
        .image-placeholder i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.6;
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-brown, #8B4513);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
});

// Export functions for global access
window.currentSlide = currentSlideIndex;
window.nextSlide = nextSlide;
window.previousSlide = previousSlide;
window.currentTestimonial = currentTestimonialIndex;
window.nextTestimonial = nextTestimonial;
window.previousTestimonial = previousTestimonial;
window.loadVenuesPreview = loadVenuesPreview;

// Additional debugging functions
function debugVenueImages() {
    console.log('=== VENUE IMAGE DEBUG INFO ===');
    
    // Check if venues container exists
    const container = document.getElementById('venues-carousel');
    console.log('Venues container found:', !!container);
    
    // Check all images on the page
    const allImages = document.querySelectorAll('.venue-image img');
    console.log('Number of venue images found:', allImages.length);
    
    allImages.forEach((img, index) => {
        console.log(`Image ${index + 1}:`, {
            src: img.src,
            alt: img.alt,
            complete: img.complete,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            error: img.onerror ? 'Has error handler' : 'No error handler'
        });
    });
    
    // Test API endpoint directly
    fetch('venues.php?limit=6')
        .then(response => response.json())
        .then(data => {
            console.log('Direct API test result:', data);
        })
        .catch(error => {
            console.error('Direct API test failed:', error);
        });
}

// Call debug function after a delay to allow page to load
setTimeout(() => {
    if (window.location.search.includes('debug=true')) {
        debugVenueImages();
    }
}, 2000);