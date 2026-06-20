<?php
// Page configuration
$pageTitle = "Venues & Packages - Mavic's Resort";
$pageDescription = "Discover our beautiful venues and event packages. Choose from intimate gatherings to grand celebrations with real-time availability.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/venues.css'];
$pageJSFiles = ['js/venues.js'];

// Include header
include 'includes/header.php';

// Get venue ID if specified
$selectedVenueId = isset($_GET['id']) ? intval($_GET['id']) : null;
?>

<!-- Main Content -->
<main id="main-content">
    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-bg">
            <img src="images/venues/venues-header.jpg" alt="Venues Header" class="header-bg-image">
            <div class="page-header-overlay"></div>
        </div>
        <div class="container">
            <div class="page-header-content">
                <h1>Venues & Packages</h1>
                <p>Discover the perfect setting for your special occasion</p>
                <nav class="breadcrumb">
                    <a href="index.php">Home</a>
                    <span>/</span>
                    <span>Venues & Packages</span>
                </nav>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-bar">
                <div class="filter-group">
                    <label for="eventType">Event Type:</label>
                    <select id="eventType" class="form-control">
                        <option value="">All Events</option>
                        <option value="wedding">Wedding Reception</option>
                        <option value="corporate">Corporate Event</option>
                        <option value="birthday">Birthday Party</option>
                        <option value="conference">Conference</option>
                        <option value="social">Social Gathering</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="capacity">Guest Capacity:</label>
                    <select id="capacity" class="form-control">
                        <option value="">Any Size</option>
                        <option value="1-50">1-50 guests</option>
                        <option value="51-100">51-100 guests</option>
                        <option value="101-200">101-200 guests</option>
                        <option value="201+">201+ guests</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="priceRange">Price Range:</label>
                    <select id="priceRange" class="form-control">
                        <option value="">Any Price</option>
                        <option value="0-1000">Under ₱1,000/hour</option>
                        <option value="1000-2000">₱1,000-₱2,000/hour</option>
                        <option value="2000-5000">₱2,000-₱5,000/hour</option>
                        <option value="5000+">₱5,000+/hour</option>
                    </select>
                </div>
                
                <button class="btn btn-primary filter-btn" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <button class="btn btn-outline clear-btn" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
    </section>

    <!-- Venues Grid -->
    <section class="venues-section py-4">
        <div class="container">
            <!-- Loading State -->
            <div id="loading-state" class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading venues...</p>
            </div>
            
            <!-- Error State -->
            <div id="error-state" class="error-container" style="display: none;">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Unable to Load Venues</h3>
                <p>We're having trouble loading our venues. Please try again.</p>
                <button class="btn btn-primary" onclick="loadVenues()">
                    <i class="fas fa-refresh"></i> Retry
                </button>
            </div>
            
            <!-- Venues Grid -->
            <div id="venues-grid" class="venues-grid" style="display: none;">
                <!-- Venues will be loaded dynamically -->
            </div>
            
            <!-- No Results -->
            <div id="no-results" class="no-results" style="display: none;">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>No Venues Found</h3>
                <p>Try adjusting your filters to see more venues.</p>
                <button class="btn btn-outline" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
        </div>
    </section>
</main>

<!-- Venue Details Modal -->
<div id="venueModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalVenueName">Venue Details</h3>
                <button class="modal-close" onclick="closeVenueModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Availability Calendar Modal -->
<div id="availabilityModal" class="modal">
    <div class="modal-dialog calendar-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Check Availability</h3>
                <button class="modal-close" onclick="closeAvailabilityModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="availabilityCalendar">
                    <!-- Calendar will be loaded dynamically -->
                </div>
                <div class="calendar-legend">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color booked"></span>
                        <span>Booked</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color today"></span>
                        <span>Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Load venues when page is ready
    loadVenues();
    
    // Handle direct venue link (if ID in URL)
    const urlParams = new URLSearchParams(window.location.search);
    const venueId = urlParams.get('id');
    if (venueId) {
        // Delay to allow venues to load first
        setTimeout(() => {
            openVenueDetails(parseInt(venueId));
        }, 1000);
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>