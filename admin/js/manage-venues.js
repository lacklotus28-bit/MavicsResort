// Manage Venues & Packages JavaScript - Mavic's Resort

document.addEventListener('DOMContentLoaded', function() {
    // Initialize page
    initializeVenuesPage();
});

function initializeVenuesPage() {
    // Modal elements
    const venueModal = document.getElementById('venueModal');
    const packageModal = document.getElementById('packageModal');
    const venueForm = document.getElementById('venueForm');
    const packageForm = document.getElementById('packageForm');
    
    // Button elements
    const addVenueBtn = document.getElementById('addVenueBtn');
    const addPackageBtn = document.getElementById('addPackageBtn');
    
    // Filter and search elements
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const viewToggle = document.querySelectorAll('.toggle-btn');
    const venuesGrid = document.getElementById('venuesGrid');
    
    // Event listeners for modals
    setupModalEventListeners();
    
    // Event listeners for forms
    setupFormEventListeners();
    
    // Event listeners for filters and search
    setupFilterEventListeners();
    
    // Event listeners for view toggle
    setupViewToggleListeners();
    
    // Event listeners for venue actions
    setupVenueActionListeners();
    
    // Event listeners for package actions
    setupPackageActionListeners();
}

function setupModalEventListeners() {
    const venueModal = document.getElementById('venueModal');
    const packageModal = document.getElementById('packageModal');
    
    // Venue Modal
    document.getElementById('addVenueBtn').addEventListener('click', () => {
        openVenueModal();
    });
    
    document.getElementById('venueModalClose').addEventListener('click', () => {
        closeModal(venueModal);
    });
    
    document.getElementById('cancelVenueBtn').addEventListener('click', () => {
        closeModal(venueModal);
    });
    
    // Package Modal
    document.getElementById('addPackageBtn').addEventListener('click', () => {
        openPackageModal();
    });
    
    document.getElementById('packageModalClose').addEventListener('click', () => {
        closeModal(packageModal);
    });
    
    document.getElementById('cancelPackageBtn').addEventListener('click', () => {
        closeModal(packageModal);
    });
    
    // Close modals when clicking outside
    venueModal.addEventListener('click', (e) => {
        if (e.target === venueModal) {
            closeModal(venueModal);
        }
    });
    
    packageModal.addEventListener('click', (e) => {
        if (e.target === packageModal) {
            closeModal(packageModal);
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (venueModal.classList.contains('show')) {
                closeModal(venueModal);
            }
            if (packageModal.classList.contains('show')) {
                closeModal(packageModal);
            }
        }
    });
}

function setupFormEventListeners() {
    const venueForm = document.getElementById('venueForm');
    const packageForm = document.getElementById('packageForm');
    
    // Venue Form
    venueForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleVenueSubmit(venueForm);
    });
    
    // Package Form
    packageForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handlePackageSubmit(packageForm);
    });
}

function setupFilterEventListeners() {
    const statusFilter = document.getElementById('statusFilter');
    const capacityFilter = document.getElementById('capacityFilter');
    const priceRangeFilter = document.getElementById('priceRangeFilter');
    const searchInput = document.getElementById('searchInput');
    
    // Status filter
    statusFilter.addEventListener('change', () => {
        filterVenues();
    });
    
    // Capacity filter
    capacityFilter.addEventListener('change', () => {
        filterVenues();
    });
    
    // Price range filter
    priceRangeFilter.addEventListener('change', () => {
        filterVenues();
    });
    
    // Search functionality
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterVenues();
        }, 300);
    });
}

function setupViewToggleListeners() {
    const viewToggle = document.querySelectorAll('.toggle-btn');
    const venuesGrid = document.getElementById('venuesGrid');
    
    viewToggle.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            
            // Update active button
            viewToggle.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Update grid view
            if (view === 'list') {
                venuesGrid.classList.add('list-view');
            } else {
                venuesGrid.classList.remove('list-view');
            }
        });
    });
}

function setupVenueActionListeners() {
    // Edit venue buttons
    document.addEventListener('click', async (e) => {
        if (e.target.closest('.edit-venue')) {
            const venueId = e.target.closest('.edit-venue').dataset.venueId;
            await openVenueModal(venueId);
        }
    });
    
    // Delete venue buttons
    document.addEventListener('click', async (e) => {
        if (e.target.closest('.delete-venue')) {
            const venueId = e.target.closest('.delete-venue').dataset.venueId;
            await handleVenueDelete(venueId);
        }
    });
}

function setupPackageActionListeners() {
    // Edit package buttons
    document.addEventListener('click', async (e) => {
        if (e.target.closest('.edit-package')) {
            const packageId = e.target.closest('.edit-package').dataset.packageId;
            await openPackageModal(packageId);
        }
    });
    
    // Delete package buttons
    document.addEventListener('click', async (e) => {
        if (e.target.closest('.delete-package')) {
            const packageId = e.target.closest('.delete-package').dataset.packageId;
            await handlePackageDelete(packageId);
        }
    });
}

// Modal Functions
function openVenueModal(venueId = null) {
    const modal = document.getElementById('venueModal');
    const title = document.getElementById('venueModalTitle');
    const form = document.getElementById('venueForm');
    
    if (venueId) {
        title.textContent = 'Edit Venue';
        loadVenueData(venueId);
    } else {
        title.textContent = 'Add New Venue';
        form.reset();
        document.getElementById('venueId').value = '';
    }
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function openPackageModal(packageId = null) {
    const modal = document.getElementById('packageModal');
    const title = document.getElementById('packageModalTitle');
    const form = document.getElementById('packageForm');
    
    if (packageId) {
        title.textContent = 'Edit Package';
        loadPackageData(packageId);
    } else {
        title.textContent = 'Add New Package';
        form.reset();
        document.getElementById('packageId').value = '';
    }
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Confirmation Modal Functions
function showConfirmationModal(options = {}) {
    const modal = document.getElementById('confirmModal');
    const title = document.getElementById('confirmModalTitle');
    const message = document.getElementById('confirmMessage');
    const icon = document.getElementById('confirmIcon');
    const confirmBtn = document.getElementById('confirmActionBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    
    // Set modal content
    title.textContent = options.title || 'Confirm Action';
    message.textContent = options.message || 'Are you sure you want to perform this action?';
    confirmBtn.textContent = options.confirmText || 'Confirm';
    
    // Set icon type
    icon.className = 'confirm-icon';
    if (options.icon) {
        icon.classList.add(options.icon);
    }
    
    // Set confirm button class
    confirmBtn.className = 'btn';
    confirmBtn.classList.add(options.confirmClass || 'btn-primary');
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Add new event listeners
    newConfirmBtn.addEventListener('click', async () => {
        hideConfirmationModal();
        if (options.onConfirm) {
            await options.onConfirm();
        }
    });
    
    newCancelBtn.addEventListener('click', () => {
        hideConfirmationModal();
        if (options.onCancel) {
            options.onCancel();
        }
    });
    
    // Close on escape key
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            hideConfirmationModal();
            document.removeEventListener('keydown', escapeHandler);
            if (options.onCancel) {
                options.onCancel();
            }
        }
    };
    document.addEventListener('keydown', escapeHandler);
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Focus the cancel button by default for safer UX
    newCancelBtn.focus();
}

function hideConfirmationModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Data Loading Functions
async function loadVenueData(venueId) {
    try {
        const venueCard = document.querySelector(`[data-venue-id="${venueId}"]`);
        if (!venueCard) return;
        
        // Extract data from venue card
        const name = venueCard.querySelector('h3').textContent;
        const description = venueCard.querySelector('.venue-description').textContent;
        const status = venueCard.dataset.status;
        
        // Find capacity and price from detail items
        const detailItems = venueCard.querySelectorAll('.detail-item span');
        let capacity = '';
        let pricePerHour = '';
        
        detailItems.forEach(item => {
            const text = item.textContent;
            if (text.includes('Capacity:')) {
                capacity = text.replace('Capacity: ', '');
            } else if (text.includes('/hour')) {
                pricePerHour = text.replace('â‚±', '').replace('/hour', '').replace(',', '');
            }
        });
        
        // Get amenities
        const amenityTags = venueCard.querySelectorAll('.amenity-tag:not(.more)');
        const amenities = Array.from(amenityTags).map(tag => tag.textContent).join(', ');
        
        // Populate form
        document.getElementById('venueId').value = venueId;
        document.getElementById('venueName').value = name;
        document.getElementById('venueDescription').value = description;
        document.getElementById('venueCapacity').value = capacity;
        document.getElementById('venuePricePerHour').value = pricePerHour;
        document.getElementById('venueAmenities').value = amenities;
        document.getElementById('venueStatus').value = status;
        
    } catch (error) {
        console.error('Error loading venue data:', error);
        showNotification('Error loading venue data', 'error');
    }
}

async function loadPackageData(packageId) {
    try {
        const packageRow = document.querySelector(`[data-package-id="${packageId}"]`);
        if (!packageRow) return;
        
        const cells = packageRow.querySelectorAll('td');
        
        // Extract data from table cells
        const name = cells[0].querySelector('strong').textContent;
        const description = cells[0].querySelector('.package-desc').textContent;
        const venue = cells[1].textContent;
        const duration = cells[2].textContent.replace(' hours', '');
        const maxGuests = cells[3].textContent.replace(' guests', '');
        const price = cells[4].textContent.replace('â‚±', '').replace(',', '');
        const status = cells[5].querySelector('.status').textContent.toLowerCase();
        
        // Find venue ID
        const venueSelect = document.getElementById('packageVenue');
        const venueOption = Array.from(venueSelect.options).find(option => 
            option.textContent === venue
        );
        const venueId = venueOption ? venueOption.value : '';
        
        // Populate form
        document.getElementById('packageId').value = packageId;
        document.getElementById('packageName').value = name;
        document.getElementById('packageDescription').value = description;
        document.getElementById('packageVenue').value = venueId;
        document.getElementById('packageDuration').value = duration;
        document.getElementById('packageMaxGuests').value = maxGuests;
        document.getElementById('packagePrice').value = price;
        document.getElementById('packageStatus').value = status;
        
        // Note: Inclusions would need to be fetched from backend in a real implementation
        
    } catch (error) {
        console.error('Error loading package data:', error);
        showNotification('Error loading package data', 'error');
    }
}

// Enhanced handlePackageSubmit function with better error handling and debugging
async function handlePackageSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        submitButton.classList.add('loading');
        
        const formData = new FormData(form);
        const packageId = formData.get('package_id');
        const action = packageId ? 'update_package' : 'add_package';
        
        formData.append('action', action);
        
        // Debug: Log the form data being sent
        console.log('Sending package data:', action);
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        // Debug: Log response details
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get response text first to check what we actually received
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            
            // If it's HTML, likely a PHP error - extract useful info
            if (responseText.includes('<br />') || responseText.includes('<b>')) {
                // Extract error message from PHP error output
                const errorMatch = responseText.match(/<b>([^<]+)<\/b>/);
                const errorMsg = errorMatch ? errorMatch[1] : 'Server returned HTML instead of JSON - check PHP errors';
                throw new Error(errorMsg);
            } else {
                throw new Error('Server returned invalid response format');
            }
        }
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal(document.getElementById('packageModal'));
            // Refresh page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Unknown error occurred');
        }
        
    } catch (error) {
        console.error('Error submitting package form:', error);
        showNotification(error.message || 'Failed to save package', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        submitButton.classList.remove('loading');
    }
}

// Also enhance the venue submit function with same debugging
async function handleVenueSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        submitButton.classList.add('loading');
        
        const formData = new FormData(form);
        const venueId = formData.get('venue_id');
        const action = venueId ? 'update_venue' : 'add_venue';
        
        formData.append('action', action);
        
        // Debug: Log the form data being sent
        console.log('Sending venue data:', action);
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        // Get response text first to check what we actually received
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            
            // If it's HTML, likely a PHP error - extract useful info
            if (responseText.includes('<br />') || responseText.includes('<b>')) {
                const errorMatch = responseText.match(/<b>([^<]+)<\/b>/);
                const errorMsg = errorMatch ? errorMatch[1] : 'Server returned HTML instead of JSON - check PHP errors';
                throw new Error(errorMsg);
            } else {
                throw new Error('Server returned invalid response format');
            }
        }
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal(document.getElementById('venueModal'));
            // Refresh page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Unknown error occurred');
        }
        
    } catch (error) {
        console.error('Error submitting venue form:', error);
        showNotification(error.message || 'Failed to save venue', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        submitButton.classList.remove('loading');
    }
}

// Additional debugging function to validate form data before submission
function debugFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            // Handle multiple values (like checkboxes)
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    console.log('Form data object:', data);
    
    // Check for required fields
    const requiredFields = ['name', 'venue_id', 'price', 'duration_hours'];
    const missing = requiredFields.filter(field => !data[field] || data[field].toString().trim() === '');
    
    if (missing.length > 0) {
        console.warn('Missing required fields:', missing);
        return false;
    }
    
    // Validate numeric fields
    if (data.price && (isNaN(data.price) || parseFloat(data.price) < 0)) {
        console.warn('Invalid price:', data.price);
        return false;
    }
    
    if (data.duration_hours && (isNaN(data.duration_hours) || parseInt(data.duration_hours) < 1)) {
        console.warn('Invalid duration:', data.duration_hours);
        return false;
    }
    
    return true;
}

// Enhanced form validation specifically for package form
function validatePackageForm(form) {
    const errors = [];
    
    // Basic validation
    const name = form.querySelector('[name="name"]').value.trim();
    if (!name) errors.push('Package name is required');
    
    const venueId = form.querySelector('[name="venue_id"]').value;
    if (!venueId) errors.push('Venue selection is required');
    
    const price = form.querySelector('[name="price"]').value;
    if (!price || isNaN(price) || parseFloat(price) < 0) {
        errors.push('Valid price is required');
    }
    
    const duration = form.querySelector('[name="duration_hours"]').value;
    if (!duration || isNaN(duration) || parseInt(duration) < 1) {
        errors.push('Valid duration is required');
    }
    
    const inclusions = form.querySelector('[name="inclusions"]').value.trim();
    if (!inclusions) errors.push('Package inclusions are required');
    
    // Validate guest limits
    const minGuests = form.querySelector('[name="min_guests"]').value;
    const maxGuests = form.querySelector('[name="max_guests"]').value;
    
    if (minGuests && maxGuests) {
        if (parseInt(minGuests) > parseInt(maxGuests)) {
            errors.push('Minimum guests cannot be greater than maximum guests');
        }
    }
    
    // Check for file uploads on new packages
    const packageId = form.querySelector('[name="package_id"]').value;
    const imageInput = form.querySelector('[name="package_images[]"]');
    
    if (!packageId && imageInput.files.length === 0) {
        errors.push('At least one image is required for new packages');
    }
    
    return errors;
}

// Delete Functions
async function handleVenueDelete(venueId) {
    // Get venue name for confirmation message
    const venueCard = document.querySelector(`[data-venue-id="${venueId}"]`);
    const venueName = venueCard ? venueCard.querySelector('h3').textContent : 'this venue';
    
    showConfirmationModal({
        title: 'Delete Venue',
        message: `Are you sure you want to delete "${venueName}"? This action cannot be undone and will remove all associated data.`,
        icon: 'danger',
        confirmText: 'Delete Venue',
        confirmClass: 'btn-danger',
        onConfirm: async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'delete_venue');
                formData.append('venue_id', venueId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    // Remove venue card with animation
                    if (venueCard) {
                        venueCard.style.animation = 'fadeOut 0.5s ease-out';
                        setTimeout(() => {
                            venueCard.remove();
                        }, 500);
                    }
                } else {
                    throw new Error(result.message);
                }
                
            } catch (error) {
                console.error('Error deleting venue:', error);
                showNotification(error.message || 'Failed to delete venue', 'error');
            }
        }
    });
}

async function handlePackageDelete(packageId) {
    // Get package name for confirmation message
    const packageRow = document.querySelector(`[data-package-id="${packageId}"]`);
    const packageName = packageRow ? packageRow.querySelector('strong').textContent : 'this package';
    
    showConfirmationModal({
        title: 'Delete Package',
        message: `Are you sure you want to delete "${packageName}"? This action cannot be undone.`,
        icon: 'danger',
        confirmText: 'Delete Package',
        confirmClass: 'btn-danger',
        onConfirm: async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'delete_package');
                formData.append('package_id', packageId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    // Remove package row with animation
                    if (packageRow) {
                        packageRow.style.animation = 'fadeOut 0.5s ease-out';
                        setTimeout(() => {
                            packageRow.remove();
                        }, 500);
                    }
                } else {
                    throw new Error(result.message);
                }
                
            } catch (error) {
                console.error('Error deleting package:', error);
                showNotification(error.message || 'Failed to delete package', 'error');
            }
        }
    });
}

// Filter and Search Functions
function filterVenues() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const capacityFilter = document.getElementById('capacityFilter').value;
    const priceRangeFilter = document.getElementById('priceRangeFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const venueCards = document.querySelectorAll('.venue-card');
    
    let visibleCount = 0;
    
    venueCards.forEach(card => {
        const status = card.dataset.status.toLowerCase();
        const name = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.venue-description').textContent.toLowerCase();
        
        // Get capacity from the card
        const capacityText = card.querySelector('.detail-item span')?.textContent || '';
        const capacityMatch = capacityText.match(/Capacity: (\d+)/);
        const capacity = capacityMatch ? parseInt(capacityMatch[1]) : 0;
        
        // Get price from the card
        const priceText = card.querySelector('.detail-item:nth-child(2) span')?.textContent || '';
        const priceMatch = priceText.match(/[₱,]([\d,]+)/); 
        const price = priceMatch ? parseFloat(priceMatch[1].replace(/,/g, '')) : 0;
        
        // Apply filters
        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchTerm || 
            name.includes(searchTerm) || 
            description.includes(searchTerm);
        
        // Capacity filter
        let capacityMatches = true;
        if (capacityFilter) {
            if (capacityFilter === '1-50') {
                capacityMatches = capacity >= 1 && capacity <= 50;
            } else if (capacityFilter === '51-100') {
                capacityMatches = capacity >= 51 && capacity <= 100;
            } else if (capacityFilter === '101-200') {
                capacityMatches = capacity >= 101 && capacity <= 200;
            } else if (capacityFilter === '201+') {
                capacityMatches = capacity >= 201;
            }
        }
        
        // Price range filter
        let priceMatches = true;
        if (priceRangeFilter) {
            if (priceRangeFilter === '0-1000') {
                priceMatches = price >= 0 && price < 1000;
            } else if (priceRangeFilter === '1000-2000') {
                priceMatches = price >= 1000 && price <= 2000;
            } else if (priceRangeFilter === '2000-5000') {
                priceMatches = price >= 2000 && price <= 5000;
            } else if (priceRangeFilter === '5000+') {
                priceMatches = price >= 5000;
            }
        }
        
        if (statusMatch && searchMatch && capacityMatches && priceMatches) {
            card.style.display = 'block';
            card.style.animation = 'fadeInUp 0.5s ease-out';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show no results message if needed
    showNoResultsMessage(visibleCount === 0);
}

// Apply filters function (called by button)
function applyFilters() {
    filterVenues();
    showNotification('Filters applied successfully', 'success');
}

// Clear filters function (called by button)
function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('capacityFilter').value = '';
    document.getElementById('priceRangeFilter').value = '';
    document.getElementById('searchInput').value = '';
    filterVenues();
    showNotification('Filters cleared', 'info');
}

function showNoResultsMessage(show) {
    let noResultsMsg = document.getElementById('noResultsMessage');
    
    if (show && !noResultsMsg) {
        noResultsMsg = document.createElement('div');
        noResultsMsg.id = 'noResultsMessage';
        noResultsMsg.className = 'no-results-message';
        noResultsMsg.innerHTML = `
            <div class="no-results-content">
                <i class="fas fa-search"></i>
                <h3>No venues found</h3>
                <p>Try adjusting your search criteria or filters.</p>
            </div>
        `;
        document.getElementById('venuesGrid').appendChild(noResultsMsg);
    } else if (!show && noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Notification Functions
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.notification');
    existing.forEach(n => n.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas ${getNotificationIcon(type)}"></i>
            </div>
            <div class="notification-message">${message}</div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);
    
    // Close button event
    notification.querySelector('.notification-close').addEventListener('click', () => {
        removeNotification(notification);
    });
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function removeNotification(notification) {
    notification.classList.add('hide');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

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

// Image Upload Functions (for future implementation)
function handleImageUpload(input, venueId) {
    const files = input.files;
    if (!files.length) return;
    
    const formData = new FormData();
    formData.append('action', 'upload_venue_image');
    formData.append('venue_id', venueId);
    
    for (let file of files) {
        if (file.type.startsWith('image/')) {
            formData.append('images[]', file);
        }
    }
    
    // Implementation would involve uploading to server
    console.log('Image upload functionality would be implemented here');
}

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Alt + V: Add new venue
    if (e.altKey && e.key === 'v') {
        e.preventDefault();
        document.getElementById('addVenueBtn').click();
    }
    
    // Alt + P: Add new package
    if (e.altKey && e.key === 'p') {
        e.preventDefault();
        document.getElementById('addPackageBtn').click();
    }
    
    // Ctrl + F: Focus search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
});

// Form Validation
function validateVenueForm(formData) {
    const errors = [];
    
    if (!formData.get('name').trim()) {
        errors.push('Venue name is required');
    }
    
    if (!formData.get('capacity') || formData.get('capacity') < 1) {
        errors.push('Valid capacity is required');
    }
    
    if (!formData.get('price_per_hour') || formData.get('price_per_hour') < 0) {
        errors.push('Valid price per hour is required');
    }
    
    return errors;
}

function validatePackageForm(formData) {
    const errors = [];
    
    if (!formData.get('name').trim()) {
        errors.push('Package name is required');
    }
    
    if (!formData.get('venue_id')) {
        errors.push('Venue selection is required');
    }
    
    if (!formData.get('price') || formData.get('price') < 0) {
        errors.push('Valid price is required');
    }
    
    if (!formData.get('duration_hours') || formData.get('duration_hours') < 1) {
        errors.push('Valid duration is required');
    }
    
    return errors;
}

// Initialize tooltips (if using a tooltip library)
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        // Tooltip implementation would go here
        console.log('Tooltip initialization for:', element);
    });
}

// Print functionality
function printVenuesList() {
    const printWindow = window.open('', '_blank');
    const venuesGrid = document.getElementById('venuesGrid').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Venues List - Mavic's Resort</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .venue-card { border: 1px solid #ddd; margin-bottom: 20px; padding: 15px; }
                .venue-actions { display: none; }
                @media print {
                    .venue-actions { display: none !important; }
                }
            </style>
        </head>
        <body>
            <h1>Venues List - Mavic's Resort</h1>
            <div>${venuesGrid}</div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Export functionality
function exportVenuesData() {
    const venues = [];
    const venueCards = document.querySelectorAll('.venue-card');
    
    venueCards.forEach(card => {
        const name = card.querySelector('h3').textContent;
        const description = card.querySelector('.venue-description').textContent;
        const status = card.dataset.status;
        
        venues.push({
            name,
            description,
            status
        });
    });
    
    // Convert to CSV
    const csvContent = [
        ['Name', 'Description', 'Status'],
        ...venues.map(v => [v.name, v.description, v.status])
    ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'venues-list.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Initialize page animations
function initializeAnimations() {
    // Stagger animation for venue cards
    const venueCards = document.querySelectorAll('.venue-card');
    venueCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });
    
    // Intersection Observer for lazy loading animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe sections
    const sections = document.querySelectorAll('.venues-section, .packages-section');
    sections.forEach(section => {
        observer.observe(section);
    });
}

// Error handling
window.addEventListener('error', (e) => {
    console.error('JavaScript error:', e);
    // In production, you might want to send errors to a logging service
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e);
    showNotification('An unexpected error occurred. Please try again.', 'error');
});

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeAnimations();
    initializeTooltips();
});

// Form Change Tracking
function trackFormChanges(form) {
    // Store original form data
    const formData = new FormData(form);
    const originalData = {};
    for (let [key, value] of formData.entries()) {
        originalData[key] = value;
    }
    form._originalData = originalData;
    form._hasChanges = false;
    
    // Add change listeners to form elements
    const elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(element => {
        element.addEventListener('input', () => {
            form._hasChanges = true;
        });
        element.addEventListener('change', () => {
            form._hasChanges = true;
        });
    });
}

function hasUnsavedChanges(form) {
    return form._hasChanges === true;
}

function clearFormTracking(form) {
    form._originalData = null;
    form._hasChanges = false;
}

// Add notification styles to the document
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification.hide {
    opacity: 0;
    transform: translateX(100%);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-heavy);
    backdrop-filter: blur(10px);
}

.notification-success .notification-content {
    background: linear-gradient(135deg, rgba(143, 188, 143, 0.95), rgba(107, 142, 107, 0.95));
    color: white;
}

.notification-error .notification-content {
    background: linear-gradient(135deg, rgba(205, 92, 92, 0.95), rgba(165, 42, 42, 0.95));
    color: white;
}

.notification-warning .notification-content {
    background: linear-gradient(135deg, rgba(222, 184, 135, 0.95), rgba(205, 133, 63, 0.95));
    color: var(--dark-gray);
}

.notification-info .notification-content {
    background: linear-gradient(135deg, rgba(95, 158, 160, 0.95), rgba(70, 130, 180, 0.95));
    color: white;
}

.notification-icon {
    font-size: 1.2rem;
}

.notification-message {
    flex: 1;
    font-weight: 500;
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: var(--spacing-xs);
    border-radius: 50%;
    transition: background-color 0.3s ease;
    opacity: 0.7;
}

.notification-close:hover {
    background: rgba(0, 0, 0, 0.1);
    opacity: 1;
}

.no-results-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--spacing-xxl);
    color: var(--medium-gray);
}

.no-results-content i {
    font-size: 3rem;
    margin-bottom: var(--spacing-lg);
    opacity: 0.5;
}

.no-results-content h3 {
    margin-bottom: var(--spacing-sm);
    color: var(--dark-gray);
}

@keyframes fadeOut {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(0.9); }
}

.animate-in {
    animation: fadeInUp 0.6s ease-out;
}
`;

document.head.appendChild(notificationStyles);