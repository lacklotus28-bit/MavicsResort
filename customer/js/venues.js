// Fixed venues.js - Enhanced error handling for missing venues

// Global variables
let venues = [];
let filteredVenues = [];
let currentModal = null;
let currentCalendar = null;
let selectedVenue = null;
let selectedDate = null;

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Venues page initializing...');
    initializeVenuesPage();
    
    // Add debugging helper
    window.debugVenues = function() {
        console.log('=== VENUES DEBUG INFO ===');
        console.log('Venues loaded:', venues.length);
        console.log('Filtered venues:', filteredVenues.length);
        console.log('Venue IDs:', venues.map(v => v.id));
        console.log('Current modal:', currentModal);
        console.log('Selected venue:', selectedVenue);
        console.log('Current calendar:', currentCalendar);
        console.log('AuthManager available:', typeof AuthManager !== 'undefined');
        
        if (venues.length > 0) {
            console.log('Sample venue:', venues[0]);
        }
        
        // Check modal elements
        const venueModal = document.getElementById('venueModal');
        const availabilityModal = document.getElementById('availabilityModal');
        console.log('Venue modal found:', !!venueModal);
        console.log('Availability modal found:', !!availabilityModal);
    };
    
    console.log('Venues page initialized. Run debugVenues() for debug info.');
});

function initializeVenuesPage() {
    loadVenues();
    setupEventListeners();
    setupModalEvents();
}

function setupEventListeners() {
    // Filter change events
    const filterElements = ['eventType', 'capacity', 'priceRange'];
    filterElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });
    
    // Modal close events
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
    
    // Click outside modal to close
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
}

function setupModalEvents() {
    // Prevent modal content clicks from closing modal
    const modalContents = document.querySelectorAll('.modal-content');
    modalContents.forEach(content => {
        content.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// Main venue loading function
async function loadVenues() {
    showLoadingState();
    
    try {
        const response = await fetch('api/venues.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data); // Debug log
        
        if (data.success && data.venues) {
            venues = data.venues.map(venue => ({
                id: venue.id,
                name: venue.name || 'Unnamed Venue',
                description: venue.description || 'No description available',
                image: venue.featured_image || 'images/venues/default.jpg',
                images: venue.images || [venue.featured_image || 'images/venues/default.jpg'],
                capacity: parseInt(venue.capacity) || 0,
                price_per_hour: parseFloat(venue.price_per_hour) || 0,
                amenities: venue.amenities || [],
                total_bookings: parseInt(venue.total_bookings) || 0,
                status: venue.status || 'available',
                packages: venue.packages || []
            }));
            
            console.log('Processed venues:', venues.map(v => ({ id: v.id, name: v.name, status: v.status })));
            
            filteredVenues = [...venues];
            renderVenues();
            
            // Load packages for each venue
            await loadVenuePackages();
            
        } else {
            throw new Error(data.message || 'Failed to load venues');
        }
        
    } catch (error) {
        console.error('Error loading venues:', error);
        showErrorState();
    }
}

async function loadVenuePackages() {
    for (let venue of venues) {
        try {
            const response = await fetch(`api/packages.php?venue_id=${venue.id}`);
            const data = await response.json();
            
            if (data.success && data.packages) {
                venue.packages = data.packages;
            }
        } catch (error) {
            console.error(`Error loading packages for venue ${venue.id}:`, error);
        }
    }
    
    // Re-render venues with package information
    renderVenues();
}

function showLoadingState() {
    const loadingState = document.getElementById('loading-state');
    const venuesGrid = document.getElementById('venues-grid');
    const errorState = document.getElementById('error-state');
    const noResults = document.getElementById('no-results');
    
    if (loadingState) loadingState.style.display = 'block';
    if (venuesGrid) venuesGrid.style.display = 'none';
    if (errorState) errorState.style.display = 'none';
    if (noResults) noResults.style.display = 'none';
}

function showErrorState() {
    const loadingState = document.getElementById('loading-state');
    const venuesGrid = document.getElementById('venues-grid');
    const errorState = document.getElementById('error-state');
    const noResults = document.getElementById('no-results');
    
    if (loadingState) loadingState.style.display = 'none';
    if (venuesGrid) venuesGrid.style.display = 'none';
    if (errorState) errorState.style.display = 'block';
    if (noResults) noResults.style.display = 'none';
}

function showNoResults() {
    const loadingState = document.getElementById('loading-state');
    const venuesGrid = document.getElementById('venues-grid');
    const errorState = document.getElementById('error-state');
    const noResults = document.getElementById('no-results');
    
    if (loadingState) loadingState.style.display = 'none';
    if (venuesGrid) venuesGrid.style.display = 'none';
    if (errorState) errorState.style.display = 'none';
    if (noResults) noResults.style.display = 'block';
}

function renderVenues() {
    const venuesGrid = document.getElementById('venues-grid');
    const loadingState = document.getElementById('loading-state');
    const errorState = document.getElementById('error-state');
    const noResults = document.getElementById('no-results');
    
    if (!venuesGrid) {
        console.error('Venues grid element not found');
        return;
    }
    
    if (filteredVenues.length === 0) {
        showNoResults();
        return;
    }
    
    if (loadingState) loadingState.style.display = 'none';
    if (errorState) errorState.style.display = 'none';
    if (noResults) noResults.style.display = 'none';
    venuesGrid.style.display = 'grid';
    
    venuesGrid.innerHTML = filteredVenues.map(venue => createVenueCard(venue)).join('');
    
    // Add click listeners to venue cards and buttons
    document.querySelectorAll('.venue-card').forEach(card => {
        const venueId = parseInt(card.dataset.venueId);
        
        if (isNaN(venueId)) {
            console.error('Invalid venue ID in card:', card.dataset.venueId);
            return;
        }
        
        // View details button
        const viewDetailsBtn = card.querySelector('.btn-view-details');
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                console.log('View Details clicked for venue:', venueId);
                openVenueDetails(venueId);
            });
        }
        
        // Check availability button
        const checkAvailBtn = card.querySelector('.btn-check-availability');
        if (checkAvailBtn) {
            checkAvailBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                console.log('Check Availability clicked for venue:', venueId);
                openAvailabilityCalendar(venueId);
            });
        }
        
        // Card click (excluding buttons)
        card.addEventListener('click', function(e) {
            // Check if click is on a button or inside button
            if (!e.target.classList.contains('btn-view-details') && 
                !e.target.classList.contains('btn-check-availability') &&
                !e.target.closest('.btn-view-details') &&
                !e.target.closest('.btn-check-availability')) {
                console.log('Card clicked for venue:', venueId);
                openVenueDetails(venueId);
            }
        });
    });
}

function createVenueCard(venue) {
    if (!venue || !venue.id) {
        console.error('Invalid venue data:', venue);
        return '';
    }
    
    const statusClass = getVenueStatusClass(venue.status);
    const statusText = getVenueStatusText(venue.status);
    const rating = generateRating();
    const packagePreview = venue.packages ? venue.packages.slice(0, 2) : [];
    
    return `
        <div class="venue-card" data-venue-id="${venue.id}">
            <div class="venue-image">
                <img src="${venue.image}" 
                     alt="${venue.name}" 
                     onerror="handleVenueImageError(this, '${venue.name}', ${venue.id})">
                <div class="venue-image-overlay"></div>
                <div class="venue-badge">Featured</div>
                <div class="venue-status ${statusClass}">${statusText}</div>
                <div class="image-gallery">
                    <i class="fas fa-images"></i>
                    ${venue.images.length} photos
                </div>
            </div>
            
            <div class="venue-info">
                <div class="venue-header">
                    <div>
                        <h3>${venue.name}</h3>
                        <div class="venue-rating">
                            <span class="stars">${rating.stars}</span>
                            <span>(${rating.count})</span>
                        </div>
                    </div>
                </div>
                
                <p class="venue-description">${venue.description}</p>
                
                <div class="venue-meta">
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span>Up to ${venue.capacity} guests</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>San Nicolas, Batangas</span>
                    </div>
                </div>
                
                <div class="venue-price">
                    <div class="price-label">Starting from</div>
                    <div class="price-value">
                        ${formatCurrency(venue.price_per_hour)}
                        <span class="price-unit">per hour</span>
                    </div>
                </div>
                
                ${venue.amenities.length > 0 ? `
                    <div class="venue-amenities">
                        <div class="amenities-list">
                            ${venue.amenities.slice(0, 4).map(amenity => `
                                <span class="amenity-tag">${amenity}</span>
                            `).join('')}
                            ${venue.amenities.length > 4 ? `<span class="amenity-tag">+${venue.amenities.length - 4} more</span>` : ''}
                        </div>
                    </div>
                ` : ''}
                
                ${packagePreview.length > 0 ? `
                    <div class="packages-preview">
                        <div class="packages-title">
                            <i class="fas fa-box"></i>
                            Available Packages
                        </div>
                        <div class="packages-list">
                            ${packagePreview.map(pkg => `
                                <div class="package-preview">
                                    <div class="package-name">${pkg.name}</div>
                                    <div class="package-price">${formatCurrency(pkg.price)}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <div class="venue-actions">
                    <button class="btn-view-details">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button class="btn-check-availability" data-venue-id="${venue.id}">
                        <i class="fas fa-calendar-alt"></i> Check Availability
                    </button>
                </div>
            </div>
        </div>
    `;
}

function getVenueStatusClass(status) {
    switch (status) {
        case 'available': return 'available';
        case 'maintenance': return 'limited';
        default: return 'available';
    }
}

function getVenueStatusText(status) {
    switch (status) {
        case 'available': return 'Available';
        case 'maintenance': return 'Limited';
        case 'unavailable': return 'Booked';
        default: return 'Available';
    }
}

function generateRating() {
    // Generate random rating for demo purposes
    const rating = (Math.random() * 2 + 3).toFixed(1); // Between 3.0 and 5.0
    const stars = '★'.repeat(Math.floor(rating)) + (rating % 1 >= 0.5 ? '☆' : '');
    const count = Math.floor(Math.random() * 100) + 20;
    
    return {
        stars: stars,
        count: count
    };
}

// Filter functionality
function applyFilters() {
    const eventType = document.getElementById('eventType')?.value || '';
    const capacity = document.getElementById('capacity')?.value || '';
    const priceRange = document.getElementById('priceRange')?.value || '';
    
    filteredVenues = venues.filter(venue => {
        // Event type filter (basic implementation)
        if (eventType && !venue.description.toLowerCase().includes(eventType)) {
            // In a real implementation, you'd have venue categories
        }
        
        // Capacity filter
        if (capacity) {
            const [min, max] = capacity.split('-').map(Number);
            if (max && (venue.capacity < min || venue.capacity > max)) {
                return false;
            } else if (!max && capacity.includes('+')) {
                const minCapacity = parseInt(capacity);
                if (venue.capacity < minCapacity) {
                    return false;
                }
            }
        }
        
        // Price range filter
        if (priceRange) {
            const [min, max] = priceRange.split('-').map(Number);
            if (max && (venue.price_per_hour < min || venue.price_per_hour > max)) {
                return false;
            } else if (!max && priceRange.includes('+')) {
                const minPrice = parseInt(priceRange);
                if (venue.price_per_hour < minPrice) {
                    return false;
                }
            }
        }
        
        return true;
    });
    
    renderVenues();
}

function clearFilters() {
    const eventType = document.getElementById('eventType');
    const capacity = document.getElementById('capacity');
    const priceRange = document.getElementById('priceRange');
    
    if (eventType) eventType.value = '';
    if (capacity) capacity.value = '';
    if (priceRange) priceRange.value = '';
    
    filteredVenues = [...venues];
    renderVenues();
}

// Enhanced venue details modal with fallback to API
async function openVenueDetails(venueId) {
    console.log('Opening venue details for ID:', venueId);
    
    // First try to find venue in loaded venues
    let venue = venues.find(v => v.id === venueId);
    
    // If not found, try to fetch from API
    if (!venue) {
        console.log('Venue not found in local array, fetching from API...');
        try {
            const response = await fetch(`api/venues.php?id=${venueId}`);
            const data = await response.json();
            
            if (data.success && data.venue) {
                venue = {
                    id: data.venue.id,
                    name: data.venue.name || 'Unnamed Venue',
                    description: data.venue.description || 'No description available',
                    image: data.venue.featured_image || 'images/venues/default.jpg',
                    images: data.venue.images || [data.venue.featured_image || 'images/venues/default.jpg'],
                    capacity: parseInt(data.venue.capacity) || 0,
                    price_per_hour: parseFloat(data.venue.price_per_hour) || 0,
                    amenities: data.venue.amenities || [],
                    total_bookings: parseInt(data.venue.total_bookings) || 0,
                    status: data.venue.status || 'available',
                    packages: data.venue.packages || []
                };
            }
        } catch (error) {
            console.error('Error fetching venue from API:', error);
        }
    }
    
    if (!venue) {
        console.error('Venue not found:', venueId);
        showAlert('Venue not found or currently unavailable', 'error');
        return;
    }
    
    console.log('Found venue:', venue);
    selectedVenue = venue;
    
    const modal = document.getElementById('venueModal');
    const modalContent = document.getElementById('modalContent');
    const modalVenueName = document.getElementById('modalVenueName');
    
    if (!modal || !modalContent || !modalVenueName) {
        console.error('Modal elements not found');
        return;
    }
    
    modalVenueName.textContent = venue.name;
    
    modalContent.innerHTML = `
        <div class="venue-detail-images">
            <div class="main-image">
                <img src="${venue.image}" alt="${venue.name}" id="mainImage">
            </div>
            <div class="thumbnail-grid">
                ${venue.images && venue.images.length > 1 ? venue.images.slice(1, 3).map((img, index) => `
                    <div class="thumbnail">
                        <img src="${img}" alt="${venue.name} ${index + 2}" onclick="changeMainImage('${img}')">
                    </div>
                `).join('') : '<p style="text-align: center; color: #666;">No additional images</p>'}
            </div>
        </div>
        
        <div class="venue-detail-info">
            <div class="venue-detail-main">
                <h2>${venue.name}</h2>
                <p class="description">${venue.description}</p>
                
                <div class="detail-amenities">
                    <h4><i class="fas fa-check-circle"></i> Amenities & Features</h4>
                    <div class="amenities-grid">
                        ${venue.amenities && venue.amenities.length > 0 ? venue.amenities.map(amenity => `
                            <div class="amenity-item">
                                <i class="fas fa-check"></i>
                                <span>${amenity}</span>
                            </div>
                        `).join('') : '<p>No amenities listed</p>'}
                    </div>
                </div>
            </div>
            
            <div class="venue-detail-sidebar">
                <div class="capacity-info">
                    <div class="info-label">Maximum Capacity</div>
                    <div class="info-value">${venue.capacity} guests</div>
                </div>
                
                <div class="pricing-info">
                    <div class="info-label">Starting Price</div>
                    <div class="info-value">${formatCurrency(venue.price_per_hour)}/hour</div>
                </div>
                
                <button class="btn btn-primary" onclick="openAvailabilityCalendar(${venue.id})" style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-calendar-alt"></i> Check Availability
                </button>
                
                ${typeof AuthManager !== 'undefined' && AuthManager.isLoggedIn && AuthManager.isLoggedIn() ? `
                    <button class="btn btn-outline" onclick="proceedToBooking(${venue.id})" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-bookmark"></i> Book Now
                    </button>
                ` : `
                    <button class="btn btn-outline" onclick="requireAuth()" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-sign-in-alt"></i> Login to Book
                    </button>
                `}
            </div>
        </div>
        
        ${venue.packages && venue.packages.length > 0 ? `
            <div class="packages-section">
                <h3><i class="fas fa-box"></i> Available Packages</h3>
                <div class="packages-grid">
                    ${venue.packages.map((pkg, index) => `
                        <div class="package-card ${index === 1 ? 'featured' : ''}">
                            <div class="package-name">${pkg.name}</div>
                            <div class="package-price">${formatCurrency(pkg.price)}</div>
                            <div class="package-duration">${pkg.duration_hours || 8} hours included</div>
                            
                            <ul class="package-features">
                                ${(pkg.inclusions || ['Venue rental', 'Basic setup', 'Cleaning service']).map(feature => `
                                    <li><i class="fas fa-check"></i> ${feature}</li>
                                `).join('')}
                            </ul>
                            
                            <button class="package-btn" onclick="selectPackage(${venue.id}, ${pkg.id})">
                                <i class="fas fa-calendar-plus"></i> Select Package
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '<div style="text-align: center; padding: 20px; color: #666;"><p>No packages available for this venue</p></div>'}
    `;
    
    openModal(modal);
    console.log('Venue modal opened');
}

// Enhanced availability calendar with venue validation
async function openAvailabilityCalendar(venueId) {
    console.log('Opening availability calendar for venue ID:', venueId);
    
    // First try to find venue in loaded venues
    let venue = venues.find(v => v.id === venueId);
    
    // If not found, try to fetch from API
    if (!venue) {
        console.log('Venue not found in local array, fetching from API...');
        try {
            const response = await fetch(`api/venues.php?id=${venueId}`);
            const data = await response.json();
            
            if (data.success && data.venue) {
                venue = {
                    id: data.venue.id,
                    name: data.venue.name || 'Unnamed Venue',
                    status: data.venue.status || 'available'
                };
            }
        } catch (error) {
            console.error('Error fetching venue from API:', error);
        }
    }
    
    if (!venue) {
        console.error('Venue not found:', venueId);
        showAlert('Venue not found or currently unavailable', 'error');
        return;
    }
    
    console.log('Found venue for calendar:', venue);
    selectedVenue = venue;
    
    const modal = document.getElementById('availabilityModal');
    const calendarContainer = document.getElementById('availabilityCalendar');
    
    if (!modal || !calendarContainer) {
        console.error('Availability modal elements not found');
        return;
    }
    
    // Close venue details modal if open
    closeVenueModal();
    
    // Generate calendar for current month
    generateCalendar(calendarContainer, new Date(), venueId);
    
    openModal(modal);
    console.log('Availability calendar opened');
}

function requireAuth() {
    if (typeof AuthManager !== 'undefined' && AuthManager.requireAuth) {
        AuthManager.requireAuth();
    } else {
        showAlert('Please login to access this feature.', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
    }
}

function changeMainImage(src) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = src;
    }
}

function generateCalendar(container, date, venueId) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date();
    
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    
    container.innerHTML = `
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button onclick="previousMonth(${venueId})">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="nextMonth(${venueId})">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-title">${monthNames[month]} ${year}</div>
            </div>
            
            <div class="calendar-body">
                <div class="calendar-grid">
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    ${generateCalendarDays(year, month, firstDayOfMonth, daysInMonth, today, venueId)}
                </div>
            </div>
        </div>
    `;
    
    currentCalendar = { year, month, venueId };
    
    // Load actual availability data
    loadAvailabilityData(venueId, year, month);
}

function generateCalendarDays(year, month, firstDayOfMonth, daysInMonth, today, venueId) {
    let daysHTML = '';
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < firstDayOfMonth; i++) {
        const prevMonth = month === 0 ? 11 : month - 1;
        const prevYear = month === 0 ? year - 1 : year;
        const prevMonthDays = new Date(prevYear, prevMonth + 1, 0).getDate();
        const day = prevMonthDays - firstDayOfMonth + i + 1;
        
        daysHTML += `<div class="calendar-day other-month">${day}</div>`;
    }
    
    // Add days of the current month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const isToday = currentDate.toDateString() === today.toDateString();
        const isPast = currentDate < today;
        
        let className = 'calendar-day';
        let clickHandler = '';
        let dataDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        if (isToday) {
            className += ' today';
        }
        
        if (isPast) {
            className += ' past';
        } else {
            // Default to available, will be updated by loadAvailabilityData
            className += ' available';
            clickHandler = `onclick="selectDate(${year}, ${month}, ${day}, ${venueId})"`;
        }
        
        daysHTML += `<div class="${className}" data-date="${dataDate}" ${clickHandler}>${day}</div>`;
    }
    
    return daysHTML;
}

async function loadAvailabilityData(venueId, year, month) {
    try {
        const response = await fetch(`api/availability.php?venue_id=${venueId}&year=${year}&month=${month + 1}`);
        const data = await response.json();
        
        if (data.success && data.calendar) {
            updateCalendarWithAvailability(data.calendar);
        }
    } catch (error) {
        console.error('Error loading availability data:', error);
        // Calendar will show default available state
    }
}

function updateCalendarWithAvailability(availabilityData) {
    Object.keys(availabilityData).forEach(dateString => {
        const dayElement = document.querySelector(`[data-date="${dateString}"]`);
        const availability = availabilityData[dateString];
        
        if (dayElement) {
            // Remove existing status classes
            dayElement.classList.remove('available', 'booked', 'blocked', 'past');
            
            // Add appropriate status class
            dayElement.classList.add(availability.status);
            
            // Update click handler based on status
            if (availability.status === 'available') {
                const [year, month, day] = dateString.split('-').map(Number);
                const venueId = currentCalendar.venueId;
                dayElement.setAttribute('onclick', `selectDate(${year}, ${month - 1}, ${day}, ${venueId})`);
                dayElement.style.cursor = 'pointer';
            } else {
                dayElement.removeAttribute('onclick');
                dayElement.style.cursor = 'not-allowed';
            }
            
            // Add tooltip with details if available
            if (availability.details) {
                let tooltipText = '';
                if (availability.status === 'booked') {
                    tooltipText = `Booked - ${availability.details.event_type || 'Event'}`;
                } else if (availability.status === 'blocked') {
                    tooltipText = `Unavailable - ${availability.details.reason || 'Maintenance'}`;
                }
                
                if (tooltipText) {
                    dayElement.setAttribute('title', tooltipText);
                }
            }
        }
    });
}

function selectDate(year, month, day, venueId) {
    const selectedDate = new Date(year, month, day);
    const dateString = selectedDate.toISOString().split('T')[0];
    
    // Remove previous selection
    document.querySelectorAll('.calendar-day.selected').forEach(dayEl => {
        dayEl.classList.remove('selected');
    });
    
    // Add selection to clicked day
    event.target.classList.add('selected');
    
    // Store selected date
    selectedDate = selectedDate;
    
    // Show confirmation dialog
    const venue = venues.find(v => v.id === venueId) || selectedVenue;
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    const formattedDate = selectedDate.toLocaleDateString('en-US', options);
    
    showAlert(`Selected: ${venue ? venue.name : 'Venue'} on ${formattedDate}`, 'info', 3000);
    
    // Show booking options after a brief delay
    setTimeout(() => {
        const proceed = confirm(`Would you like to proceed with booking ${venue ? venue.name : 'this venue'} for ${formattedDate}?`);
        if (proceed) {
            proceedToBooking(venueId, selectedDate);
        }
    }, 1000);
}

function previousMonth(venueId) {
    if (!currentCalendar) return;
    
    let { year, month } = currentCalendar;
    month--;
    
    if (month < 0) {
        month = 11;
        year--;
    }
    
    const newDate = new Date(year, month, 1);
    const container = document.getElementById('availabilityCalendar');
    generateCalendar(container, newDate, venueId);
}

function nextMonth(venueId) {
    if (!currentCalendar) return;
    
    let { year, month } = currentCalendar;
    month++;
    
    if (month > 11) {
        month = 0;
        year++;
    }
    
    const newDate = new Date(year, month, 1);
    const container = document.getElementById('availabilityCalendar');
    generateCalendar(container, newDate, venueId);
}

// Booking functions
function selectPackage(venueId, packageId) {
    if (typeof AuthManager !== 'undefined' && !AuthManager.isLoggedIn()) {
        AuthManager.requireAuth();
        return;
    }
    
    const venue = venues.find(v => v.id === venueId) || selectedVenue;
    const selectedPackage = venue ? venue.packages.find(p => p.id === packageId) : null;
    
    if (venue && selectedPackage) {
        // Store selection in sessionStorage for booking page
        sessionStorage.setItem('selectedVenue', JSON.stringify(venue));
        sessionStorage.setItem('selectedPackage', JSON.stringify(selectedPackage));
        
        // Close modals
        closeAllModals();
        
        // Redirect to booking page
        window.location.href = `booking.php?venue=${venueId}&package=${packageId}`;
    }
}

function proceedToBooking(venueId, selectedDateParam = null) {
    if (typeof AuthManager !== 'undefined' && !AuthManager.isLoggedIn()) {
        AuthManager.requireAuth();
        return;
    }
    
    const venue = venues.find(v => v.id === venueId) || selectedVenue;
    
    if (venue) {
        // Store selection in sessionStorage
        sessionStorage.setItem('selectedVenue', JSON.stringify(venue));
        
        if (selectedDateParam) {
            sessionStorage.setItem('selectedDate', selectedDateParam.toISOString());
        }
        
        // Close modals
        closeAllModals();
        
        // Redirect to booking page
        const dateParam = selectedDateParam ? '&date=' + selectedDateParam.toISOString().split('T')[0] : '';
        window.location.href = `booking.php?venue=${venueId}${dateParam}`;
    }
}

// Modal management
function openModal(modal) {
    if (!modal) return;
    
    modal.style.display = 'flex';
    modal.style.opacity = '0';
    
    // Trigger animation
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.classList.add('show');
    }, 10);
    
    document.body.style.overflow = 'hidden';
    currentModal = modal;
}

function closeModal(modal) {
    if (!modal) return;
    
    modal.style.opacity = '0';
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        if (currentModal === modal) {
            currentModal = null;
        }
    }, 300);
}

function closeVenueModal() {
    const modal = document.getElementById('venueModal');
    if (modal) closeModal(modal);
}

function closeAvailabilityModal() {
    const modal = document.getElementById('availabilityModal');
    if (modal) closeModal(modal);
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (modal.style.display === 'flex') {
            closeModal(modal);
        }
    });
}

// Utility functions
function formatCurrency(amount, currency = 'PHP') {
    const numAmount = parseFloat(amount) || 0;
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numAmount);
}

function handleVenueImageError(img, venueName, venueId) {
    console.error(`Image failed to load for venue ${venueName} (ID: ${venueId}):`, img.src);
    
    if (img && img.parentNode) {
        const currentSrc = img.src;
        const fileName = currentSrc.split('/').pop();
        
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
            img.src = newPath;
        } else {
            // All alternatives failed, show placeholder
            img.style.display = 'none';
            
            if (!img.parentNode.querySelector('.image-placeholder')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-placeholder';
                placeholder.innerHTML = `
                    <i class="fas fa-image" style="font-size: 3rem; color: var(--primary-brown); margin-bottom: 15px; opacity: 0.6;"></i>
                    <span style="font-size: 1.1rem; color: var(--dark-brown); font-weight: 600;">${venueName}</span>
                    <small style="display: block; margin-top: 8px; opacity: 0.7; color: var(--text-dark);">Image not available</small>
                `;
                placeholder.style.cssText = `
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    background: linear-gradient(135deg, var(--cream), var(--light-brown));
                    color: var(--primary-brown);
                    text-align: center;
                    border: 2px dashed var(--light-brown);
                `;
                img.parentNode.appendChild(placeholder);
            }
        }
    }
}

// Enhanced alert function
function showAlert(message, type = 'info', duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `custom-alert alert-${type}`;
    alertDiv.innerHTML = `
        <div class="alert-content">
            <span class="alert-message">${message}</span>
            <button class="alert-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    // Style the alert
    alertDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 10001;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 400px;
        animation: slideIn 0.3s ease-out;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.4;
    `;
    
    // Set colors based on type
    const colors = {
        success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
        error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
        warning: { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
        info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
    };
    
    const color = colors[type] || colors.info;
    alertDiv.style.backgroundColor = color.bg;
    alertDiv.style.border = `1px solid ${color.border}`;
    alertDiv.style.color = color.text;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, duration);
    }
}

// Add CSS for alert animations
if (!document.querySelector('#alert-styles')) {
    const style = document.createElement('style');
    style.id = 'alert-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .custom-alert .alert-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .custom-alert .alert-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            opacity: 0.7;
        }
        .custom-alert .alert-close:hover {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
}

// Search functionality (future enhancement)
function searchVenues(query) {
    if (!query.trim()) {
        filteredVenues = [...venues];
    } else {
        const searchTerm = query.toLowerCase();
        filteredVenues = venues.filter(venue => 
            venue.name.toLowerCase().includes(searchTerm) ||
            venue.description.toLowerCase().includes(searchTerm) ||
            venue.amenities.some(amenity => amenity.toLowerCase().includes(searchTerm))
        );
    }
    
    renderVenues();
}

// Sorting functionality (future enhancement)
function sortVenues(criteria) {
    switch (criteria) {
        case 'name':
            filteredVenues.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'price-low':
            filteredVenues.sort((a, b) => a.price_per_hour - b.price_per_hour);
            break;
        case 'price-high':
            filteredVenues.sort((a, b) => b.price_per_hour - a.price_per_hour);
            break;
        case 'capacity':
            filteredVenues.sort((a, b) => b.capacity - a.capacity);
            break;
        case 'popular':
            filteredVenues.sort((a, b) => b.total_bookings - a.total_bookings);
            break;
        default:
            break;
    }
    
    renderVenues();
}

// Initialize global functions
window.loadVenues = loadVenues;
window.applyFilters = applyFilters;
window.clearFilters = clearFilters;
window.openVenueDetails = openVenueDetails;
window.openAvailabilityCalendar = openAvailabilityCalendar;
window.closeVenueModal = closeVenueModal;
window.closeAvailabilityModal = closeAvailabilityModal;
window.selectPackage = selectPackage;
window.proceedToBooking = proceedToBooking;
window.changeMainImage = changeMainImage;
window.selectDate = selectDate;
window.previousMonth = previousMonth;
window.nextMonth = nextMonth;
window.handleVenueImageError = handleVenueImageError;
window.searchVenues = searchVenues;
window.sortVenues = sortVenues;
window.showAlert = showAlert;

// Export for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        loadVenues,
        applyFilters,
        clearFilters,
        openVenueDetails,
        openAvailabilityCalendar,
        selectPackage,
        proceedToBooking
    };
}