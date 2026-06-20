// Manage Bookings JavaScript - Mavic's Resort

document.addEventListener('DOMContentLoaded', function() {
    // Initialize page
    initializeBookingsPage();
    
    // Add event listeners
    setupEventListeners();
    
    // Initialize tooltips and other UI elements
    initializeUI();
    
    // Add modal initialization
    initializeModals();
    
    // Initialize logout functionality
    initializeLogout();
});

// Global variables
let currentBookingId = null;
let confirmCallback = null;

/**
 * Initialize logout functionality
 */
function initializeLogout() {
    // Handle logout button click
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutModal();
        });
    }
    
    // Handle logout confirmation
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', function() {
            performLogout();
        });
    }
    
    // Handle logout cancellation
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', function() {
            closeModal('logoutModal');
        });
    }
}

/**
 * Show logout confirmation modal
 */
function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        openModal('logoutModal');
    } else {
        // If modal doesn't exist, logout directly
        if (confirm('Are you sure you want to logout?')) {
            performLogout();
        }
    }
}

/**
 * Perform logout
 */
function performLogout() {
    // Show loading state
    showNotification('Logging out...', 'info', 2000);
    
    // Redirect to logout handler
    window.location.href = 'logout.php';
}

/**
 * Initialize the bookings page
 */
function initializeBookingsPage() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.stat-card, .filters-card, .table-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });
    
    // Initialize status select colors
    updateStatusSelectColors();
    
    // Auto-refresh data every 30 seconds
    setInterval(refreshBookingsData, 30000);
    
    console.log('Manage Bookings page initialized');
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Search input with debounce
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 500);
        });
    }
    
    // Filter form submission
    const filterForm = document.querySelector('.filters-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
    
    // Modal click outside to close
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
}

/**
 * Initialize UI elements
 */
function initializeUI() {
    // Initialize table row hover effects
    const tableRows = document.querySelectorAll('.bookings-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Initialize action button animations
    const actionButtons = document.querySelectorAll('.btn-action');
    actionButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.1)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

/**
 * Update status select colors based on their values
 */
function updateStatusSelectColors() {
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.setAttribute('value', select.value);
    });
}

/**
 * Update booking status via AJAX
 */
function updateStatus(selectElement) {
    const bookingId = selectElement.dataset.bookingId;
    const newStatus = selectElement.value;
    const oldStatus = selectElement.getAttribute('data-old-status') || selectElement.defaultSelected;
    
    // Show loading state
    selectElement.style.opacity = '0.7';
    selectElement.style.pointerEvents = 'none';
    
    // Update color immediately for better UX
    selectElement.setAttribute('value', newStatus);
    
    // Send AJAX request
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&booking_id=${bookingId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Status updated successfully', 'success');
            selectElement.setAttribute('data-old-status', newStatus);
            
            // Update stats if needed
            updateStatsAfterStatusChange(oldStatus, newStatus);
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showNotification('Failed to update status: ' + error.message, 'error');
        
        // Revert the select value
        selectElement.value = oldStatus;
        selectElement.setAttribute('value', oldStatus);
    })
    .finally(() => {
        selectElement.style.opacity = '1';
        selectElement.style.pointerEvents = 'auto';
    });
}

/**
 * View booking details
 */
function viewBooking(bookingId) {
    currentBookingId = bookingId;
    
    // Show loading modal
    showLoadingModal('Loading booking details...');
    
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_booking_details&booking_id=${bookingId}`
    })
    .then(response => response.json())
    .then(booking => {
        if (booking) {
            displayBookingDetails(booking);
        } else {
            throw new Error('Booking not found');
        }
    })
    .catch(error => {
        console.error('Error loading booking:', error);
        closeModal('bookingModal');
        showNotification('Failed to load booking details', 'error');
    });
}

/**
 * Display booking details in modal
 */
function displayBookingDetails(booking) {
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    // Calculate days until event
    const eventDate = new Date(booking.booking_date);
    const today = new Date();
    const daysUntilEvent = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
    
    // Calculate booking age
    const bookingDate = new Date(booking.created_at);
    const bookingAge = Math.floor((today - bookingDate) / (1000 * 60 * 60 * 24));
    
    modalTitle.innerHTML = `
        <div class="modal-title-content">
            <div class="booking-header">
                <span class="booking-id">Booking #${String(booking.id).padStart(4, '0')}</span>
                <span class="status-badge ${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
            </div>
            <div class="booking-subtitle">
                ${escapeHtml(booking.event_type)} at ${escapeHtml(booking.venue_name)}
            </div>
        </div>
    `;
    
    modalContent.innerHTML = `
        <!-- Event Timeline -->
        <div class="event-timeline">
            <div class="timeline-item ${daysUntilEvent < 0 ? 'past' : daysUntilEvent <= 7 ? 'upcoming' : 'future'}">
                <div class="timeline-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="timeline-content">
                    <h4>${daysUntilEvent < 0 ? 'Event Completed' : daysUntilEvent === 0 ? 'Event Today!' : `${daysUntilEvent} days to event`}</h4>
                    <p>${formatDate(booking.booking_date)} • ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</p>
                </div>
            </div>
        </div>

        <div class="booking-detail-grid">
            <!-- Customer Information -->
            <div class="detail-section">
                <h4><i class="fas fa-user-circle"></i> Customer Information</h4>
                <div class="customer-card">
                    <div class="customer-avatar-large">
                        ${getInitials(booking.customer_name)}
                    </div>
                    <div class="customer-info-detailed">
                        <h5>${escapeHtml(booking.customer_name)}</h5>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:${escapeHtml(booking.email)}">${escapeHtml(booking.email)}</a>
                            </div>
                            ${booking.phone ? `
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:${escapeHtml(booking.phone)}">${escapeHtml(booking.phone)}</a>
                                </div>
                            ` : ''}
                            ${booking.address ? `
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${escapeHtml(booking.address)}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Details -->
            <div class="detail-section">
                <h4><i class="fas fa-calendar-check"></i> Event Details</h4>
                <div class="detail-cards">
                    <div class="info-card">
                        <div class="info-icon event">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Event Type</span>
                            <span class="info-value">${escapeHtml(booking.event_type)}</span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon guests">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Guest Count</span>
                            <span class="info-value">${booking.guest_count} guests</span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon duration">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <span class="info-label">Duration</span>
                            <span class="info-value">${calculateDuration(booking.start_time, booking.end_time)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Venue Information -->
            <div class="detail-section">
                <h4><i class="fas fa-building"></i> Venue Information</h4>
                <div class="venue-card">
                    <div class="venue-header">
                        <h5>${escapeHtml(booking.venue_name)}</h5>
                        <span class="capacity-badge">
                            <i class="fas fa-users"></i>
                            Capacity: ${booking.capacity} guests
                        </span>
                    </div>
                    ${booking.package_name ? `
                        <div class="package-info">
                            <i class="fas fa-box"></i>
                            <span>Package: ${escapeHtml(booking.package_name)}</span>
                        </div>
                    ` : '<p class="no-package">No package selected</p>'}
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="detail-section">
                <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                <div class="payment-summary">
                    <div class="payment-row total">
                        <span class="payment-label">Total Amount</span>
                        <span class="payment-value">₱${formatCurrency(booking.total_amount)}</span>
                    </div>
                    <div class="payment-row">
                        <span class="payment-label">Down Payment</span>
                        <span class="payment-value">₱${formatCurrency(booking.down_payment || 0)}</span>
                    </div>
                    <div class="payment-row balance">
                        <span class="payment-label">Remaining Balance</span>
                        <span class="payment-value">₱${formatCurrency((booking.total_amount - (booking.down_payment || 0)))}</span>
                    </div>
                    <div class="payment-status">
                        <span class="payment-status-badge ${booking.payment_status || 'pending'}">
                            <i class="fas fa-${getPaymentIcon(booking.payment_status || 'pending')}"></i>
                            ${(booking.payment_status || 'pending').charAt(0).toUpperCase() + (booking.payment_status || 'pending').slice(1)}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Booking Metadata -->
            <div class="detail-section">
                <h4><i class="fas fa-info-circle"></i> Booking Information</h4>
                <div class="metadata-grid">
                    <div class="metadata-item">
                        <i class="fas fa-plus-circle"></i>
                        <div>
                            <span class="metadata-label">Created</span>
                            <span class="metadata-value">${formatDate(booking.created_at)} (${bookingAge} days ago)</span>
                        </div>
                    </div>
                    ${booking.updated_at && booking.updated_at !== booking.created_at ? `
                        <div class="metadata-item">
                            <i class="fas fa-edit"></i>
                            <div>
                                <span class="metadata-label">Last Updated</span>
                                <span class="metadata-value">${formatDate(booking.updated_at)}</span>
                            </div>
                        </div>
                    ` : ''}
                    <div class="metadata-item">
                        <i class="fas fa-hashtag"></i>
                        <div>
                            <span class="metadata-label">Reference</span>
                            <span class="metadata-value">#${String(booking.id).padStart(4, '0')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        ${booking.special_requests ? `
            <div class="special-requests-section">
                <h4><i class="fas fa-comment-dots"></i> Special Requests</h4>
                <div class="special-requests-content">
                    <p>${escapeHtml(booking.special_requests)}</p>
                </div>
            </div>
        ` : ''}
        
        <!-- Action Buttons -->
        <div class="modal-action-grid">
            <button class="btn btn-primary" onclick="editBooking(${booking.id})">
                <i class="fas fa-edit"></i>
                Edit Booking
            </button>
            <button class="btn btn-info" onclick="printBookingDetails(${booking.id})">
                <i class="fas fa-print"></i>
                Print Details
            </button>
            <button class="btn btn-outline" onclick="closeModal('bookingModal')">
                <i class="fas fa-times"></i>
                Close
            </button>
        </div>
    `;
    
    openModal('bookingModal');
}


/**
 * Edit booking - opens edit modal with current booking data
 */
function editBooking(bookingId) {
    currentBookingId = bookingId;
    
    // Show loading modal
    showLoadingModal('Loading booking data for editing...');
    
    // Fetch booking details and available venues/packages
    Promise.all([
        fetch('manage-bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_booking_details&booking_id=${bookingId}`
        }).then(response => response.json()),
        
        fetch('manage-bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_venues_packages`
        }).then(response => response.json())
    ])
    .then(([booking, venuesData]) => {
        if (booking && booking.id) {
            displayEditBookingModal(booking, venuesData);
        } else {
            throw new Error('Booking not found');
        }
    })
    .catch(error => {
        console.error('Error loading booking for edit:', error);
        closeModal('bookingModal');
        showNotification('Failed to load booking data: ' + error.message, 'error');
    });
}

/**
 * Display edit booking modal
 */
function displayEditBookingModal(booking, venuesData) {
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.innerHTML = `
        <div class="modal-title-content">
            <div class="booking-header">
                <span class="booking-id">Edit Booking #${String(booking.id).padStart(4, '0')}</span>
                <span class="status-badge ${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
            </div>
            <div class="booking-subtitle">
                Modify booking details
            </div>
        </div>
    `;
    
    const venues = venuesData.venues || [];
    const packages = venuesData.packages || [];
    
    modalContent.innerHTML = `
        <form id="editBookingForm" class="edit-booking-form">
            <input type="hidden" name="booking_id" value="${booking.id}">
            
            <div class="form-grid">
                <!-- Customer Information (Read-only for now) -->
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> Customer Information</h4>
                    <div class="customer-display">
                        <div class="customer-avatar-small">
                            ${getInitials(booking.customer_name)}
                        </div>
                        <div class="customer-info">
                            <strong>${escapeHtml(booking.customer_name)}</strong>
                            <span>${escapeHtml(booking.email)}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Event Details -->
                <div class="form-section">
                    <h4><i class="fas fa-calendar-check"></i> Event Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_event_type">Event Type <span class="required">*</span></label>
                            <select id="edit_event_type" name="event_type" required>
                                <option value="Wedding Reception" ${booking.event_type === 'Wedding Reception' ? 'selected' : ''}>Wedding Reception</option>
                                <option value="Birthday Party" ${booking.event_type === 'Birthday Party' ? 'selected' : ''}>Birthday Party</option>
                                <option value="Corporate Event" ${booking.event_type === 'Corporate Event' ? 'selected' : ''}>Corporate Event</option>
                                <option value="Anniversary" ${booking.event_type === 'Anniversary' ? 'selected' : ''}>Anniversary</option>
                                <option value="Reunion" ${booking.event_type === 'Reunion' ? 'selected' : ''}>Reunion</option>
                                <option value="Graduation" ${booking.event_type === 'Graduation' ? 'selected' : ''}>Graduation</option>
                                <option value="Baptism" ${booking.event_type === 'Baptism' ? 'selected' : ''}>Baptism</option>
                                <option value="Conference" ${booking.event_type === 'Conference' ? 'selected' : ''}>Conference</option>
                                <option value="Other" ${!['Wedding Reception', 'Birthday Party', 'Corporate Event', 'Anniversary', 'Reunion', 'Graduation', 'Baptism', 'Conference'].includes(booking.event_type) ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_guest_count">Guest Count <span class="required">*</span></label>
                            <input type="number" id="edit_guest_count" name="guest_count" value="${booking.guest_count}" min="1" max="500" required>
                        </div>
                    </div>
                </div>
                
                <!-- Venue & Package -->
                <div class="form-section">
                    <h4><i class="fas fa-building"></i> Venue & Package</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_venue_id">Venue <span class="required">*</span></label>
                            <select id="edit_venue_id" name="venue_id" required onchange="updateVenueCapacity(); checkVenueAvailability();">
                                <option value="">Select a venue</option>
                                ${venues.map(venue => `
                                    <option value="${venue.id}" 
                                            data-capacity="${venue.capacity}" 
                                            data-price="${venue.price_per_hour}"
                                            ${venue.id == booking.venue_id ? 'selected' : ''}>
                                        ${escapeHtml(venue.name)} (Capacity: ${venue.capacity})
                                    </option>
                                `).join('')}
                            </select>
                            <div id="venue_capacity_warning" class="form-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Guest count exceeds venue capacity!
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_package_id">Package (Optional)</label>
                            <select id="edit_package_id" name="package_id">
                                <option value="">No package</option>
                                ${packages.map(pkg => `
                                    <option value="${pkg.id}" 
                                            data-venue-id="${pkg.venue_id}"
                                            data-price="${pkg.price}"
                                            ${pkg.id == booking.package_id ? 'selected' : ''}>
                                        ${escapeHtml(pkg.name)} - ₱${formatCurrency(pkg.price)}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Date & Time -->
                <div class="form-section">
                    <h4><i class="fas fa-clock"></i> Date & Time</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_booking_date">Event Date <span class="required">*</span></label>
                            <input type="date" id="edit_booking_date" name="booking_date" value="${booking.booking_date}" required onchange="checkVenueAvailability();">
                            <div id="date_availability_status" class="availability-status"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_start_time">Start Time <span class="required">*</span></label>
                            <input type="time" id="edit_start_time" name="start_time" value="${booking.start_time}" required onchange="calculateDurationAndCost();">
                        </div>
                        <div class="form-group">
                            <label for="edit_end_time">End Time <span class="required">*</span></label>
                            <input type="time" id="edit_end_time" name="end_time" value="${booking.end_time}" required onchange="calculateDurationAndCost();">
                        </div>
                    </div>
                    <div class="duration-display">
                        <i class="fas fa-clock"></i>
                        <span id="duration_text">Duration: ${calculateDuration(booking.start_time, booking.end_time)}</span>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="form-section">
                    <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
                    <div class="payment-summary-edit">
                        <div class="cost-breakdown">
                            <div class="cost-item">
                                <span>Base Cost:</span>
                                <span id="base_cost">₱${formatCurrency(booking.total_amount)}</span>
                            </div>
                            <div class="cost-item total">
                                <span>Total Amount:</span>
                                <span id="total_amount">₱${formatCurrency(booking.total_amount)}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_down_payment">Down Payment</label>
                                <input type="number" id="edit_down_payment" name="down_payment" value="${booking.down_payment || 0}" step="0.01" min="0" onchange="calculateBalance();">
                            </div>
                            <div class="form-group">
                                <label for="edit_payment_status">Payment Status</label>
                                <select id="edit_payment_status" name="payment_status">
                                    <option value="unpaid" ${booking.payment_status === 'unpaid' ? 'selected' : ''}>Unpaid</option>
                                    <option value="partial" ${booking.payment_status === 'partial' ? 'selected' : ''}>Partial</option>
                                    <option value="paid" ${booking.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="balance-display">
                            <span>Remaining Balance: </span>
                            <span id="balance_amount">₱${formatCurrency((booking.total_amount - (booking.down_payment || 0)))}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Status -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Booking Status</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select id="edit_status" name="status">
                                <option value="pending" ${booking.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="confirmed" ${booking.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                <option value="cancelled" ${booking.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                <option value="completed" ${booking.status === 'completed' ? 'selected' : ''}>Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <div class="form-section full-width">
                    <h4><i class="fas fa-comment-dots"></i> Special Requests</h4>
                    <div class="form-group">
                        <textarea id="edit_special_requests" name="special_requests" rows="3" placeholder="Any special requests or notes...">${booking.special_requests || ''}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="save_booking_btn">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
                <button type="button" class="btn btn-outline" onclick="closeModal('bookingModal')">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    `;
    
    // Setup form event listeners
    setupEditBookingForm();
    
    // Initialize calculations
    updateVenueCapacity();
    calculateDurationAndCost();
    filterPackagesByVenue();
    
    openModal('bookingModal');
}

/**
 * Setup edit booking form event listeners
 */
function setupEditBookingForm() {
    const form = document.getElementById('editBookingForm');
    if (!form) return;
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        saveBookingChanges();
    });
    
    // Guest count validation
    document.getElementById('edit_guest_count').addEventListener('input', updateVenueCapacity);
    
    // Venue change handler
    document.getElementById('edit_venue_id').addEventListener('change', function() {
        updateVenueCapacity();
        filterPackagesByVenue();
        calculateDurationAndCost();
    });
    
    // Package change handler
    document.getElementById('edit_package_id').addEventListener('change', calculateDurationAndCost);
    
    // Down payment change handler
    document.getElementById('edit_down_payment').addEventListener('input', calculateBalance);
}

/**
 * Update venue capacity warning
 */
function updateVenueCapacity() {
    const venueSelect = document.getElementById('edit_venue_id');
    const guestInput = document.getElementById('edit_guest_count');
    const warning = document.getElementById('venue_capacity_warning');
    
    if (!venueSelect || !guestInput || !warning) return;
    
    const selectedOption = venueSelect.options[venueSelect.selectedIndex];
    const capacity = selectedOption ? parseInt(selectedOption.dataset.capacity) : 0;
    const guestCount = parseInt(guestInput.value) || 0;
    
    if (capacity > 0 && guestCount > capacity) {
        warning.style.display = 'block';
        warning.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            Guest count (${guestCount}) exceeds venue capacity (${capacity})!
        `;
    } else {
        warning.style.display = 'none';
    }
}

/**
 * Filter packages by selected venue
 */
function filterPackagesByVenue() {
    const venueSelect = document.getElementById('edit_venue_id');
    const packageSelect = document.getElementById('edit_package_id');
    
    if (!venueSelect || !packageSelect) return;
    
    const selectedVenueId = venueSelect.value;
    const packageOptions = packageSelect.querySelectorAll('option');
    
    packageOptions.forEach(option => {
        if (option.value === '') {
            // Always show "No package" option
            option.style.display = 'block';
        } else {
            const packageVenueId = option.dataset.venueId;
            option.style.display = (packageVenueId === selectedVenueId) ? 'block' : 'none';
        }
    });
    
    // Reset package selection if current selection is not valid for new venue
    const currentPackageOption = packageSelect.options[packageSelect.selectedIndex];
    if (currentPackageOption && currentPackageOption.dataset.venueId && currentPackageOption.dataset.venueId !== selectedVenueId) {
        packageSelect.value = '';
    }
}

/**
 * Calculate duration and cost
 */
function calculateDurationAndCost() {
    const venueSelect = document.getElementById('edit_venue_id');
    const packageSelect = document.getElementById('edit_package_id');
    const startTime = document.getElementById('edit_start_time').value;
    const endTime = document.getElementById('edit_end_time').value;
    
    const durationText = document.getElementById('duration_text');
    const baseCost = document.getElementById('base_cost');
    const totalAmount = document.getElementById('total_amount');
    
    if (!startTime || !endTime) {
        durationText.textContent = 'Duration: Please select both start and end times';
        return;
    }
    
    // Calculate duration
    const duration = calculateDuration(startTime, endTime);
    durationText.textContent = `Duration: ${duration}`;
    
    // Calculate cost
    let cost = 0;
    const selectedVenueOption = venueSelect.options[venueSelect.selectedIndex];
    const selectedPackageOption = packageSelect.options[packageSelect.selectedIndex];
    
    if (selectedVenueOption && selectedVenueOption.dataset.price) {
        const venueHourlyRate = parseFloat(selectedVenueOption.dataset.price);
        const hours = calculateHours(startTime, endTime);
        cost = venueHourlyRate * hours;
    }
    
    if (selectedPackageOption && selectedPackageOption.dataset.price) {
        cost += parseFloat(selectedPackageOption.dataset.price);
    }
    
    baseCost.textContent = `₱${formatCurrency(cost)}`;
    totalAmount.textContent = `₱${formatCurrency(cost)}`;
    
    // Update balance calculation
    calculateBalance();
}

/**
 * Calculate hours between start and end time
 */
function calculateHours(startTime, endTime) {
    if (!startTime || !endTime) return 0;
    
    const start = new Date(`1970-01-01T${startTime}`);
    const end = new Date(`1970-01-01T${endTime}`);
    
    let diff = end.getTime() - start.getTime();
    
    // Handle next day scenario
    if (diff < 0) {
        diff += 24 * 60 * 60 * 1000;
    }
    
    return Math.ceil(diff / (1000 * 60 * 60)); // Round up to nearest hour
}

/**
 * Calculate balance
 */
function calculateBalance() {
    const totalElement = document.getElementById('total_amount');
    const downPaymentInput = document.getElementById('edit_down_payment');
    const balanceElement = document.getElementById('balance_amount');
    
    if (!totalElement || !downPaymentInput || !balanceElement) return;
    
    const total = parseFloat(totalElement.textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const downPayment = parseFloat(downPaymentInput.value) || 0;
    const balance = Math.max(0, total - downPayment);
    
    balanceElement.textContent = `₱${formatCurrency(balance)}`;
}

/**
 * Check venue availability for selected date
 */
function checkVenueAvailability() {
    const venueSelect = document.getElementById('edit_venue_id');
    const dateInput = document.getElementById('edit_booking_date');
    const statusDiv = document.getElementById('date_availability_status');
    
    if (!venueSelect || !dateInput || !statusDiv) return;
    
    const venueId = venueSelect.value;
    const selectedDate = dateInput.value;
    
    if (!venueId || !selectedDate) {
        statusDiv.innerHTML = '';
        return;
    }
    
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
    
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_availability&venue_id=${venueId}&date=${selectedDate}&exclude_booking=${currentBookingId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            statusDiv.innerHTML = '<i class="fas fa-check-circle" style="color: green;"></i> Venue is available';
            statusDiv.className = 'availability-status available';
        } else {
            statusDiv.innerHTML = '<i class="fas fa-times-circle" style="color: red;"></i> Venue is not available on this date';
            statusDiv.className = 'availability-status unavailable';
        }
    })
    .catch(error => {
        console.error('Error checking availability:', error);
        statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Unable to check availability';
        statusDiv.className = 'availability-status error';
    });
}

/**
 * Save booking changes
 */
function saveBookingChanges() {
    const form = document.getElementById('editBookingForm');
    const submitBtn = document.getElementById('save_booking_btn');
    
    if (!form || !submitBtn) return;
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Check venue capacity
    const warning = document.getElementById('venue_capacity_warning');
    if (warning && warning.style.display !== 'none') {
        if (!confirm('Guest count exceeds venue capacity. Do you want to continue anyway?')) {
            return;
        }
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Prepare form data
    const formData = new FormData(form);
    formData.append('action', 'update_booking');
    
    fetch('manage-bookings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Booking updated successfully!', 'success');
            closeModal('bookingModal');
            
            // Refresh the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Failed to update booking');
        }
    })
    .catch(error => {
        console.error('Error updating booking:', error);
        showNotification('Failed to update booking: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    });
}

/**
 * Delete booking with confirmation
 */
function deleteBooking(bookingId) {
    currentBookingId = bookingId;
    
    showConfirmModal(
        'Delete Booking',
        'Are you sure you want to delete this booking? This action cannot be undone.',
        'danger',
        () => {
            performDeleteBooking(bookingId);
        }
    );
}

/**
 * Perform the actual booking deletion
 */
function performDeleteBooking(bookingId) {
    showLoadingModal('Deleting booking...');
    
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete_booking&booking_id=${bookingId}`
    })
    .then(response => response.json())
    .then(data => {
        closeModal('bookingModal');
        
        if (data.success) {
            showNotification('Booking deleted successfully', 'success');
            
            // Remove the row from the table with animation
            const row = document.querySelector(`[data-booking-id="${bookingId}"]`)?.closest('tr');
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-100%)';
                setTimeout(() => {
                    row.remove();
                    updateStatsAfterDeletion();
                }, 300);
            } else {
                // If row not found, reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            throw new Error(data.message || 'Failed to delete booking');
        }
    })
    .catch(error => {
        console.error('Error deleting booking:', error);
        closeModal('bookingModal');
        showNotification('Failed to delete booking: ' + error.message, 'error');
    });
}

/**
 * Open add new booking modal
 */
function openAddBookingModal() {
    currentBookingId = null;
    
    // Show loading modal
    showLoadingModal('Loading form data...');
    
    // Fetch venues, packages, and customers
    Promise.all([
        fetch('manage-bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_venues_packages`
        }).then(response => response.json()),
        
        fetch('manage-bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_customers`
        }).then(response => response.json())
    ])
    .then(([venuesData, customersData]) => {
        displayAddBookingModal(venuesData, customersData);
    })
    .catch(error => {
        console.error('Error loading form data:', error);
        closeModal('bookingModal');
        showNotification('Failed to load form data: ' + error.message, 'error');
    });
}

/**
 * Display add new booking modal
 */
function displayAddBookingModal(venuesData, customersData) {
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.innerHTML = `
        <div class="modal-title-content">
            <div class="booking-header">
                <span class="booking-id">Add New Booking</span>
                <span class="status-badge pending">New</span>
            </div>
            <div class="booking-subtitle">
                Create a new venue booking
            </div>
        </div>
    `;
    
    const venues = venuesData.venues || [];
    const packages = venuesData.packages || [];
    const customers = customersData.customers || [];
    
    // Get current date for minimum date validation
    const today = new Date();
    const todayString = today.toISOString().split('T')[0];
    
    modalContent.innerHTML = `
        <form id="addBookingForm" class="edit-booking-form">
            <div class="form-grid">
                <!-- Customer Selection -->
                <div class="form-section">
                    <h4><i class="fas fa-user"></i> Customer Information</h4>
                    <div class="customer-selection">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="add_customer_type">Customer Type <span class="required">*</span></label>
                                <select id="add_customer_type" name="customer_type" required onchange="toggleCustomerFields()">
                                    <option value="">Select customer type</option>
                                    <option value="existing">Existing Customer</option>
                                    <option value="new">New Customer</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Existing Customer Selection -->
                        <div id="existing_customer_section" class="customer-type-section" style="display: none;">
                            <div class="form-group">
                                <label for="add_customer_id">Select Customer <span class="required">*</span></label>
                                <select id="add_customer_id" name="customer_id">
                                    <option value="">Choose a customer</option>
                                    ${customers.map(customer => `
                                        <option value="${customer.id}" 
                                                data-name="${escapeHtml(customer.first_name + ' ' + customer.last_name)}"
                                                data-email="${escapeHtml(customer.email)}"
                                                data-phone="${escapeHtml(customer.phone || '')}">
                                            ${escapeHtml(customer.first_name + ' ' + customer.last_name)} - ${escapeHtml(customer.email)}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                        
                        <!-- New Customer Fields -->
                        <div id="new_customer_section" class="customer-type-section" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="add_first_name">First Name <span class="required">*</span></label>
                                    <input type="text" id="add_first_name" name="first_name" maxlength="50">
                                </div>
                                <div class="form-group">
                                    <label for="add_last_name">Last Name <span class="required">*</span></label>
                                    <input type="text" id="add_last_name" name="last_name" maxlength="50">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="add_email">Email <span class="required">*</span></label>
                                    <input type="email" id="add_email" name="email" maxlength="100">
                                </div>
                                <div class="form-group">
                                    <label for="add_phone">Phone</label>
                                    <input type="tel" id="add_phone" name="phone" maxlength="15">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="add_address">Address</label>
                                    <textarea id="add_address" name="address" rows="2" maxlength="200" placeholder="Customer address (optional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Event Details -->
                <div class="form-section">
                    <h4><i class="fas fa-calendar-check"></i> Event Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_event_type">Event Type <span class="required">*</span></label>
                            <select id="add_event_type" name="event_type" required>
                                <option value="">Select event type</option>
                                <option value="Wedding Reception">Wedding Reception</option>
                                <option value="Birthday Party">Birthday Party</option>
                                <option value="Corporate Event">Corporate Event</option>
                                <option value="Anniversary">Anniversary</option>
                                <option value="Reunion">Reunion</option>
                                <option value="Graduation">Graduation</option>
                                <option value="Baptism">Baptism</option>
                                <option value="Conference">Conference</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_guest_count">Guest Count <span class="required">*</span></label>
                            <input type="number" id="add_guest_count" name="guest_count" min="1" max="500" required>
                        </div>
                    </div>
                </div>
                
                <!-- Venue & Package -->
                <div class="form-section">
                    <h4><i class="fas fa-building"></i> Venue & Package</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_venue_id">Venue <span class="required">*</span></label>
                            <select id="add_venue_id" name="venue_id" required onchange="updateVenueCapacityAdd(); checkVenueAvailabilityAdd();">
                                <option value="">Select a venue</option>
                                ${venues.map(venue => `
                                    <option value="${venue.id}" 
                                            data-capacity="${venue.capacity}" 
                                            data-price="${venue.price_per_hour}">
                                        ${escapeHtml(venue.name)} (Capacity: ${venue.capacity})
                                    </option>
                                `).join('')}
                            </select>
                            <div id="add_venue_capacity_warning" class="form-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Guest count exceeds venue capacity!
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="add_package_id">Package (Optional)</label>
                            <select id="add_package_id" name="package_id">
                                <option value="">No package</option>
                                ${packages.map(pkg => `
                                    <option value="${pkg.id}" 
                                            data-venue-id="${pkg.venue_id}"
                                            data-price="${pkg.price}">
                                        ${escapeHtml(pkg.name)} - ₱${formatCurrency(pkg.price)}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Date & Time -->
                <div class="form-section">
                    <h4><i class="fas fa-clock"></i> Date & Time</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_booking_date">Event Date <span class="required">*</span></label>
                            <input type="date" id="add_booking_date" name="booking_date" required min="${todayString}" onchange="checkVenueAvailabilityAdd();">
                            <div id="add_date_availability_status" class="availability-status"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_start_time">Start Time <span class="required">*</span></label>
                            <input type="time" id="add_start_time" name="start_time" required onchange="calculateDurationAndCostAdd();">
                        </div>
                        <div class="form-group">
                            <label for="add_end_time">End Time <span class="required">*</span></label>
                            <input type="time" id="add_end_time" name="end_time" required onchange="calculateDurationAndCostAdd();">
                        </div>
                    </div>
                    <div class="duration-display">
                        <i class="fas fa-clock"></i>
                        <span id="add_duration_text">Duration: Select times to calculate</span>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="form-section">
                    <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
                    <div class="payment-summary-edit">
                        <div class="cost-breakdown">
                            <div class="cost-item">
                                <span>Base Cost:</span>
                                <span id="add_base_cost">₱0.00</span>
                            </div>
                            <div class="cost-item total">
                                <span>Total Amount:</span>
                                <span id="add_total_amount">₱0.00</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="add_down_payment">Down Payment</label>
                                <input type="number" id="add_down_payment" name="down_payment" step="0.01" min="0" onchange="calculateBalanceAdd();">
                            </div>
                            <div class="form-group">
                                <label for="add_payment_status">Payment Status</label>
                                <select id="add_payment_status" name="payment_status">
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partial">Partial</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="balance-display">
                            <span>Remaining Balance: </span>
                            <span id="add_balance_amount">₱0.00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <div class="form-section full-width">
                    <h4><i class="fas fa-comment-dots"></i> Special Requests</h4>
                    <div class="form-group">
                        <textarea id="add_special_requests" name="special_requests" rows="3" maxlength="500" placeholder="Any special requests or notes..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="create_booking_btn">
                    <i class="fas fa-plus"></i>
                    Create Booking
                </button>
                <button type="button" class="btn btn-outline" onclick="closeModal('bookingModal')">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    `;
    
    // Setup form event listeners
    setupAddBookingForm();
    
    openModal('bookingModal');
}

/**
 * Setup add booking form event listeners
 */
function setupAddBookingForm() {
    const form = document.getElementById('addBookingForm');
    if (!form) return;
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        createNewBooking();
    });
    
    // Customer type change handler
    document.getElementById('add_customer_type').addEventListener('change', toggleCustomerFields);
    
    // Guest count validation
    document.getElementById('add_guest_count').addEventListener('input', updateVenueCapacityAdd);
    
    // Venue change handler
    document.getElementById('add_venue_id').addEventListener('change', function() {
        updateVenueCapacityAdd();
        filterPackagesByVenueAdd();
        calculateDurationAndCostAdd();
        checkVenueAvailabilityAdd();
    });
    
    // Package change handler
    document.getElementById('add_package_id').addEventListener('change', calculateDurationAndCostAdd);
    
    // Down payment change handler
    document.getElementById('add_down_payment').addEventListener('input', calculateBalanceAdd);
    
    // Email validation for new customers
    document.getElementById('add_email').addEventListener('blur', validateEmailAdd);
}

/**
 * Toggle customer fields based on customer type selection
 */
function toggleCustomerFields() {
    const customerType = document.getElementById('add_customer_type').value;
    const existingSection = document.getElementById('existing_customer_section');
    const newSection = document.getElementById('new_customer_section');
    
    // Hide both sections first
    existingSection.style.display = 'none';
    newSection.style.display = 'none';
    
    // Clear required attributes
    document.getElementById('add_customer_id').required = false;
    document.getElementById('add_first_name').required = false;
    document.getElementById('add_last_name').required = false;
    document.getElementById('add_email').required = false;
    
    if (customerType === 'existing') {
        existingSection.style.display = 'block';
        document.getElementById('add_customer_id').required = true;
    } else if (customerType === 'new') {
        newSection.style.display = 'block';
        document.getElementById('add_first_name').required = true;
        document.getElementById('add_last_name').required = true;
        document.getElementById('add_email').required = true;
    }
}

/**
 * Update venue capacity warning for add form
 */
function updateVenueCapacityAdd() {
    const venueSelect = document.getElementById('add_venue_id');
    const guestInput = document.getElementById('add_guest_count');
    const warning = document.getElementById('add_venue_capacity_warning');
    
    if (!venueSelect || !guestInput || !warning) return;
    
    const selectedOption = venueSelect.options[venueSelect.selectedIndex];
    const capacity = selectedOption ? parseInt(selectedOption.dataset.capacity) : 0;
    const guestCount = parseInt(guestInput.value) || 0;
    
    if (capacity > 0 && guestCount > capacity) {
        warning.style.display = 'block';
        warning.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            Guest count (${guestCount}) exceeds venue capacity (${capacity})!
        `;
    } else {
        warning.style.display = 'none';
    }
}

/**
 * Filter packages by selected venue for add form
 */
function filterPackagesByVenueAdd() {
    const venueSelect = document.getElementById('add_venue_id');
    const packageSelect = document.getElementById('add_package_id');
    
    if (!venueSelect || !packageSelect) return;
    
    const selectedVenueId = venueSelect.value;
    const packageOptions = packageSelect.querySelectorAll('option');
    
    packageOptions.forEach(option => {
        if (option.value === '') {
            // Always show "No package" option
            option.style.display = 'block';
        } else {
            const packageVenueId = option.dataset.venueId;
            option.style.display = (packageVenueId === selectedVenueId) ? 'block' : 'none';
        }
    });
    
    // Reset package selection
    packageSelect.value = '';
}

/**
 * Calculate duration and cost for add form
 */
function calculateDurationAndCostAdd() {
    const venueSelect = document.getElementById('add_venue_id');
    const packageSelect = document.getElementById('add_package_id');
    const startTime = document.getElementById('add_start_time').value;
    const endTime = document.getElementById('add_end_time').value;
    
    const durationText = document.getElementById('add_duration_text');
    const baseCost = document.getElementById('add_base_cost');
    const totalAmount = document.getElementById('add_total_amount');
    
    if (!startTime || !endTime) {
        durationText.textContent = 'Duration: Please select both start and end times';
        return;
    }
    
    // Calculate duration
    const duration = calculateDuration(startTime, endTime);
    durationText.textContent = `Duration: ${duration}`;
    
    // Calculate cost
    let cost = 0;
    const selectedVenueOption = venueSelect.options[venueSelect.selectedIndex];
    const selectedPackageOption = packageSelect.options[packageSelect.selectedIndex];
    
    if (selectedVenueOption && selectedVenueOption.dataset.price) {
        const venueHourlyRate = parseFloat(selectedVenueOption.dataset.price);
        const hours = calculateHours(startTime, endTime);
        cost = venueHourlyRate * hours;
    }
    
    if (selectedPackageOption && selectedPackageOption.dataset.price) {
        cost += parseFloat(selectedPackageOption.dataset.price);
    }
    
    baseCost.textContent = `₱${formatCurrency(cost)}`;
    totalAmount.textContent = `₱${formatCurrency(cost)}`;
    
    // Update balance calculation
    calculateBalanceAdd();
}

/**
 * Calculate balance for add form
 */
function calculateBalanceAdd() {
    const totalElement = document.getElementById('add_total_amount');
    const downPaymentInput = document.getElementById('add_down_payment');
    const balanceElement = document.getElementById('add_balance_amount');
    
    if (!totalElement || !downPaymentInput || !balanceElement) return;
    
    const total = parseFloat(totalElement.textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const downPayment = parseFloat(downPaymentInput.value) || 0;
    const balance = Math.max(0, total - downPayment);
    
    balanceElement.textContent = `₱${formatCurrency(balance)}`;
    
    // Auto-update payment status
    const paymentStatusSelect = document.getElementById('add_payment_status');
    if (downPayment === 0) {
        paymentStatusSelect.value = 'unpaid';
    } else if (downPayment >= total) {
        paymentStatusSelect.value = 'paid';
    } else {
        paymentStatusSelect.value = 'partial';
    }
}

/**
 * Check venue availability for add form
 */
function checkVenueAvailabilityAdd() {
    const venueSelect = document.getElementById('add_venue_id');
    const dateInput = document.getElementById('add_booking_date');
    const statusDiv = document.getElementById('add_date_availability_status');
    
    if (!venueSelect || !dateInput || !statusDiv) return;
    
    const venueId = venueSelect.value;
    const selectedDate = dateInput.value;
    
    if (!venueId || !selectedDate) {
        statusDiv.innerHTML = '';
        return;
    }
    
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
    
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_availability&venue_id=${venueId}&date=${selectedDate}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            statusDiv.innerHTML = '<i class="fas fa-check-circle" style="color: green;"></i> Venue is available';
            statusDiv.className = 'availability-status available';
        } else {
            statusDiv.innerHTML = '<i class="fas fa-times-circle" style="color: red;"></i> Venue is not available on this date';
            statusDiv.className = 'availability-status unavailable';
        }
    })
    .catch(error => {
        console.error('Error checking availability:', error);
        statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Unable to check availability';
        statusDiv.className = 'availability-status error';
    });
}

/**
 * Validate email for new customers
 */
function validateEmailAdd() {
    const emailInput = document.getElementById('add_email');
    const email = emailInput.value.trim();
    
    if (!email) return;
    
    // Check if email already exists
    fetch('manage-bookings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_email&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            emailInput.setCustomValidity('This email is already registered. Please use the "Existing Customer" option.');
            showNotification('Email already exists. Please select "Existing Customer" to use this email.', 'warning');
        } else {
            emailInput.setCustomValidity('');
        }
    })
    .catch(error => {
        console.error('Error checking email:', error);
    });
}

/**
 * Create new booking
 */
function createNewBooking() {
    const form = document.getElementById('addBookingForm');
    const submitBtn = document.getElementById('create_booking_btn');
    
    if (!form || !submitBtn) return;
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Additional validations
    const customerType = document.getElementById('add_customer_type').value;
    if (!customerType) {
        showNotification('Please select a customer type', 'error');
        return;
    }
    
    // Check venue capacity
    const warning = document.getElementById('add_venue_capacity_warning');
    if (warning && warning.style.display !== 'none') {
        if (!confirm('Guest count exceeds venue capacity. Do you want to continue anyway?')) {
            return;
        }
    }
    
    // Check venue availability
    const availabilityStatus = document.getElementById('add_date_availability_status');
    if (availabilityStatus && availabilityStatus.classList.contains('unavailable')) {
        showNotification('Selected venue is not available for the chosen date', 'error');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Booking...';
    
    // Prepare form data
    const formData = new FormData(form);
    formData.append('action', 'create_booking');
    
    fetch('manage-bookings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Booking created successfully!', 'success');
            closeModal('bookingModal');
            
            // Refresh the page to show the new booking
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Failed to create booking');
        }
    })
    .catch(error => {
        console.error('Error creating booking:', error);
        showNotification('Failed to create booking: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Booking';
    });
}

/**
 * Export bookings data
 */
function exportBookings() {
    // Get current filter parameters
    const searchParams = new URLSearchParams(window.location.search);
    const exportUrl = `export-bookings.php?${searchParams.toString()}`;
    
    showNotification('Preparing export... Download will start shortly.', 'info');
    
    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `bookings-export-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Perform search with current filter values
 */
function performSearch() {
    const form = document.querySelector('.filters-form');
    const formData = new FormData(form);
    const searchParams = new URLSearchParams();
    
    // Add non-empty form values to search params
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            searchParams.append(key, value);
        }
    }
    
    // Update URL and reload page
    const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
    window.location.href = newUrl;
}

/**
 * Refresh bookings data via AJAX
 */
function refreshBookingsData() {
    // Only refresh if we're on the main bookings page (no modals open)
    if (!document.querySelector('.modal.show')) {
        const currentUrl = window.location.href;
        
        fetch(currentUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the response and update only the table content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.querySelector('.bookings-table tbody');
            const currentTableBody = document.querySelector('.bookings-table tbody');
            
            if (newTableBody && currentTableBody) {
                currentTableBody.innerHTML = newTableBody.innerHTML;
                updateStatusSelectColors();
                setupEventListeners();
                
                // Show subtle notification
                showNotification('Data refreshed', 'success', 2000);
            }
        })
        .catch(error => {
            console.log('Auto-refresh failed:', error);
            // Fail silently for auto-refresh
        });
    }
}

/**
 * Update stats after status change
 */
function updateStatsAfterStatusChange(oldStatus, newStatus) {
    const stats = {
        pending: document.querySelector('.stat-card .stat-icon.pending + .stat-info .stat-number'),
        confirmed: document.querySelector('.stat-card .stat-icon.confirmed + .stat-info .stat-number'),
        completed: document.querySelector('.stat-card .stat-icon.completed + .stat-info .stat-number')
    };
    
    // Update the counts
    if (stats[oldStatus]) {
        const oldValue = parseInt(stats[oldStatus].textContent);
        stats[oldStatus].textContent = Math.max(0, oldValue - 1);
    }
    
    if (stats[newStatus]) {
        const newValue = parseInt(stats[newStatus].textContent);
        stats[newStatus].textContent = newValue + 1;
    }
}

/**
 * Update stats after booking deletion
 */
function updateStatsAfterDeletion() {
    const totalStat = document.querySelector('.stat-card .stat-icon.total + .stat-info .stat-number');
    if (totalStat) {
        const currentValue = parseInt(totalStat.textContent);
        totalStat.textContent = Math.max(0, currentValue - 1);
    }
}

/**
 * Show loading modal
 */
function showLoadingModal(message = 'Loading...') {
    const modal = document.getElementById('bookingModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.textContent = 'Please wait';
    modalContent.innerHTML = `
        <div style="text-align: center; padding: var(--spacing-xxl);">
            <div class="spinner" style="margin: 0 auto var(--spacing-lg) auto;"></div>
            <p style="color: var(--medium-gray);">${message}</p>
        </div>
    `;
    
    openModal('bookingModal');
}

/**
 * Show confirmation modal
 */
function showConfirmModal(title, message, type = 'warning', callback = null) {
    const modal = document.getElementById('confirmModal');
    const modalTitle = modal.querySelector('.modal-header h2');
    const modalMessage = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Set button style based on type
    confirmBtn.className = `btn btn-${type}`;
    
    // Store callback
    confirmCallback = callback;
    
    // Update confirm button click handler
    confirmBtn.onclick = function() {
        closeModal('confirmModal');
        if (confirmCallback && typeof confirmCallback === 'function') {
            confirmCallback();
        }
    };
    
    openModal('confirmModal');
}

/**
 * Open modal with proper initialization
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Check if using overlay structure or direct modal structure
        const overlay = modal.classList.contains('modal-overlay') ? modal : modal.closest('.modal-overlay');
        
        if (overlay) {
            // Use overlay structure (like logout modal)
            overlay.style.display = 'flex';
            overlay.classList.add('show');
        } else {
            // Use direct modal structure (like booking modal)
            modal.style.display = 'flex';
            modal.classList.add('show');
        }
        
        // Prevent body scroll
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        
        // Focus management
        const firstFocusable = modal.querySelector('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) {
            setTimeout(() => firstFocusable.focus(), 100);
        }
    }
}

/**
 * Close modal with proper cleanup
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Check if using overlay structure or direct modal structure
        const overlay = modal.classList.contains('modal-overlay') ? modal : modal.closest('.modal-overlay');
        
        if (overlay) {
            // Use overlay structure
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        } else {
            // Use direct modal structure
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        // Re-enable body scroll only if no other modals are open
        setTimeout(() => {
            const openModals = document.querySelectorAll('.modal.show, .modal-overlay.show');
            if (openModals.length === 0) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
            }
        }, 300);
    }
}

/**
 * Initialize modals on page load
 */
function initializeModals() {
    // Ensure all modals are hidden by default
    const modals = document.querySelectorAll('.modal, .modal-overlay');
    modals.forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('show');
    });
    
    // Add click outside to close functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') || e.target.classList.contains('modal-overlay')) {
            closeModal(e.target.id);
        }
    });
    
    // Add escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

/**
 * Close all open modals
 */
function closeAllModals() {
    const openModals = document.querySelectorAll('.modal.show, .modal-overlay.show');
    openModals.forEach(modal => {
        closeModal(modal.id);
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: var(--spacing-md) var(--spacing-lg);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-heavy);
        z-index: 10000;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-md);
    `;
    
    // Set colors based on type
    const colors = {
        success: { bg: '#F0F8F0', border: '#C8E6C8', text: '#2D5016' },
        error: { bg: '#FFEAEA', border: '#F5C6CB', text: '#721C24' },
        warning: { bg: '#FFF8E1', border: '#FFECB3', text: '#7D4F00' },
        info: { bg: '#E8F4F8', border: '#B8E6F1', text: '#0C3B47' }
    };
    
    const color = colors[type] || colors.info;
    notification.style.backgroundColor = color.bg;
    notification.style.border = `1px solid ${color.border}`;
    notification.style.color = color.text;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Utility Functions
 */

/**
 * Get initials from a full name
 */
function getInitials(name) {
    if (!name) return '??';
    
    return name
        .split(' ')
        .map(part => part.charAt(0).toUpperCase())
        .slice(0, 2)  // Take only first 2 initials
        .join('');
}

/**
 * Calculate duration between two times
 */
function calculateDuration(startTime, endTime) {
    if (!startTime || !endTime) return 'Unknown';
    
    const start = new Date(`1970-01-01T${startTime}`);
    const end = new Date(`1970-01-01T${endTime}`);
    
    let diff = end.getTime() - start.getTime();
    
    // Handle next day scenario
    if (diff < 0) {
        diff += 24 * 60 * 60 * 1000;
    }
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours === 0) {
        return `${minutes} minutes`;
    } else if (minutes === 0) {
        return `${hours} hour${hours > 1 ? 's' : ''}`;
    } else {
        return `${hours} hour${hours > 1 ? 's' : ''} ${minutes} min`;
    }
}

/**
 * Get payment status icon
 */
function getPaymentIcon(status) {
    const icons = {
        paid: 'check-circle',
        pending: 'clock',
        overdue: 'exclamation-triangle',
        partial: 'coins'
    };
    return icons[status] || 'credit-card';
}

/**
 * Print booking details
 */
function printBookingDetails(bookingId) {
    // Create a print-friendly version of the booking details
    const modalContent = document.getElementById('modalContent').cloneNode(true);
    
    // Remove action buttons from print version
    const actionGrid = modalContent.querySelector('.modal-action-grid');
    if (actionGrid) {
        actionGrid.remove();
    }
    
    // Create print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Booking Details - #${String(bookingId).padStart(4, '0')}</title>
            <link rel="stylesheet" href="css/global.css">
            <link rel="stylesheet" href="css/manage-bookings.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                @media print {
                    body { margin: 0; padding: 20px; }
                    .modal-content { box-shadow: none !important; max-width: none !important; }
                    .booking-detail-grid { grid-template-columns: 1fr 1fr; gap: 20px; }
                }
            </style>
        </head>
        <body>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Booking Details - Mavic's Resort</h2>
                </div>
                <div class="modal-body">
                    ${modalContent.innerHTML}
                </div>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for content to load, then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format time for display
 */
function formatTime(timeString) {
    if (!timeString) return 'N/A';
    
    const [hours, minutes] = timeString.split(':');
    const time = new Date();
    time.setHours(parseInt(hours), parseInt(minutes));
    
    return time.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
    if (!amount && amount !== 0) return '0.00';
    
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Handle responsive table on mobile
 */
function handleResponsiveTable() {
    const table = document.querySelector('.bookings-table');
    const container = document.querySelector('.table-container');
    
    if (table && container && window.innerWidth < 768) {
        // Add horizontal scroll indicator
        if (table.scrollWidth > container.clientWidth) {
            container.setAttribute('title', 'Scroll horizontally to see more data');
        }
    }
}

// Handle window resize
window.addEventListener('resize', debounce(handleResponsiveTable, 250));

// Add smooth scroll behavior for pagination
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('pagination-btn')) {
        e.preventDefault();
        const href = e.target.getAttribute('href');
        
        // Add loading state
        e.target.style.opacity = '0.7';
        e.target.style.pointerEvents = 'none';
        
        // Navigate after a short delay for better UX
        setTimeout(() => {
            window.location.href = href;
        }, 150);
    }
});

// Console information for developers
console.log('%cMavic\'s Resort - Manage Bookings', 'color: #8B5A3C; font-size: 16px; font-weight: bold;');
console.log('Booking management system loaded successfully.');
console.log('Available functions: viewBooking(), editBooking(), deleteBooking(), updateStatus(), exportBookings()');

// Export functions for global access (if needed)
window.MavicsBookings = {
    viewBooking,
    editBooking,
    deleteBooking,
    updateStatus,
    exportBookings,
    openAddBookingModal,
    refreshBookingsData,
    printBookingDetails,
    showLogoutModal,
    performLogout
};