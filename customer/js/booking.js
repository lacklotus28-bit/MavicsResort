// booking.js - Complete booking form functionality

// Global variables
let currentStep = 1;
let selectedVenue = null;
let selectedPackage = null;
let bookingData = window.bookingData || {};
let venuesList = [];
let bookingCalculation = {
    basePrice: 0,
    packagePrice: 0,
    totalAmount: 0,
    downPayment: 0,
    balance: 0,
    hours: 0
};

// Initialize booking page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Booking page initializing...');
    initializeBookingPage();
});

function initializeBookingPage() {
    if (!bookingData.isLoggedIn) {
        console.log('User not logged in, showing login prompt');
        return;
    }

    // Load initial data
    loadSelectedVenue();
    setupEventListeners();
    updateBookingSummary();
    
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const eventDateInput = document.getElementById('eventDate');
    if (eventDateInput) {
        eventDateInput.min = tomorrow.toISOString().split('T')[0];
        
        // Set selected date if provided
        if (bookingData.selectedDate) {
            eventDateInput.value = bookingData.selectedDate.split('T')[0];
        }
    }
}

function setupEventListeners() {
    // Form validation listeners
    const form = document.getElementById('bookingForm');
    if (form) {
        // Event date change
        const eventDate = document.getElementById('eventDate');
        if (eventDate) {
            eventDate.addEventListener('change', function() {
                validateDateAvailability(this.value);
            });
        }

        // Guest count change
        const guestCount = document.getElementById('guestCount');
        if (guestCount) {
            guestCount.addEventListener('input', function() {
                validateGuestCount(this.value);
                calculateBookingTotal();
            });
        }

        // Time inputs
        const startTime = document.getElementById('startTime');
        const endTime = document.getElementById('endTime');
        if (startTime && endTime) {
            startTime.addEventListener('change', calculateBookingTotal);
            endTime.addEventListener('change', calculateBookingTotal);
        }

        // Event type change
        const eventType = document.getElementById('eventType');
        if (eventType) {
            eventType.addEventListener('change', calculateBookingTotal);
        }
    }

    // Payment method listeners
    const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            updatePaymentInstructions(this.value);
        });
    });
    
    // Initialize payment instructions with default (GCash)
    const defaultPaymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    if (defaultPaymentMethod) {
        updatePaymentInstructions(defaultPaymentMethod.value);
    }
}

async function loadSelectedVenue() {
    const venuePreview = document.getElementById('venuePreview');
    if (!venuePreview) return;

    try {
        // Try to get venue from sessionStorage first
        const storedVenue = sessionStorage.getItem('selectedVenue');
        if (storedVenue) {
            selectedVenue = JSON.parse(storedVenue);
            displayVenuePreview(selectedVenue);
        } else if (bookingData.venueId) {
            // Fetch venue from API
            const response = await fetch(`api/venues.php?id=${bookingData.venueId}`);
            const data = await response.json();
            
            if (data.success && data.venue) {
                selectedVenue = data.venue;
                displayVenuePreview(selectedVenue);
                
                // Store in session for future use
                sessionStorage.setItem('selectedVenue', JSON.stringify(selectedVenue));
            } else {
                throw new Error('Venue not found');
            }
        } else {
            // No venue selected, show venue selection
            showVenueSelection();
            return;
        }

        // Load package if specified
        if (bookingData.packageId && selectedVenue.packages) {
            selectedPackage = selectedVenue.packages.find(p => p.id == bookingData.packageId);
            if (selectedPackage) {
                displayPackagePreview(selectedPackage);
            }
        }

        calculateBookingTotal();
        
    } catch (error) {
        console.error('Error loading venue:', error);
        showAlert('Error loading venue information. Please try again.', 'error');
        venuePreview.innerHTML = `
            <div class="venue-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Unable to load venue information</p>
                <button class="btn btn-primary" onclick="showVenueSelection()">
                    Select Venue
                </button>
            </div>
        `;
    }
}

function displayVenuePreview(venue) {
    const venuePreview = document.getElementById('venuePreview');
    if (!venuePreview || !venue) return;

    const imageUrl = venue.featured_image || venue.images?.[0] || 'images/venues/default.jpg';
    
    venuePreview.innerHTML = `
        <div class="venue-preview-card">
            <div class="venue-preview-image">
                <img src="${imageUrl}" alt="${venue.name}" onerror="this.src='images/venues/default.jpg'">
            </div>
            <div class="venue-preview-info">
                <h4>${venue.name}</h4>
                <div class="venue-preview-details">
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span>Up to ${venue.capacity} guests</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span>₱${formatNumber(venue.price_per_hour)}/hour</span>
                    </div>
                </div>
                ${venue.amenities && venue.amenities.length > 0 ? `
                    <div class="venue-amenities-preview">
                        ${venue.amenities.slice(0, 3).map(amenity => `
                            <span class="amenity-tag">${amenity}</span>
                        `).join('')}
                        ${venue.amenities.length > 3 ? `<span class="amenity-tag">+${venue.amenities.length - 3} more</span>` : ''}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

function displayPackagePreview(package) {
    const packageGroup = document.getElementById('packageGroup');
    const packagePreview = document.getElementById('packagePreview');
    
    if (!packageGroup || !packagePreview || !package) return;

    packageGroup.style.display = 'block';
    packagePreview.innerHTML = `
        <div class="package-preview-card">
            <div class="package-preview-info">
                <h4>${package.name}</h4>
                <div class="package-price">₱${formatNumber(package.price)}</div>
                <div class="package-duration">${package.duration_hours || 8} hours included</div>
                ${package.inclusions && package.inclusions.length > 0 ? `
                    <div class="package-inclusions">
                        <strong>Includes:</strong>
                        <ul>
                            ${package.inclusions.slice(0, 3).map(item => `<li>${item}</li>`).join('')}
                            ${package.inclusions.length > 3 ? `<li>+${package.inclusions.length - 3} more items</li>` : ''}
                        </ul>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

async function showVenueSelection() {
    console.log('showVenueSelection called');
    try {
        showAlert('Loading available venues...', 'info', 2000);
        const response = await fetch('api/venues.php');
        const data = await response.json();
        
        console.log('Venues API response:', data);
        
        if (data.success && data.venues) {
            venuesList = data.venues;
            console.log('Loaded venues:', venuesList.length);
            openChangeVenueModal();
        } else {
            throw new Error(data.message || 'Failed to load venues');
        }
    } catch (error) {
        console.error('Error loading venues:', error);
        showAlert('Error loading venues. Please refresh the page.', 'error');
    }
}

function openChangeVenueModal() {
    console.log('openChangeVenueModal called');
    const modal = document.getElementById('changeVenueModal');
    const venuesListContainer = document.getElementById('venuesList');
    
    console.log('Modal element:', modal);
    console.log('Venues list container:', venuesListContainer);
    
    if (!modal) {
        console.error('Modal element not found!');
        showAlert('Modal error. Please refresh the page.', 'error');
        return;
    }
    
    if (!venuesListContainer) {
        console.error('Venues list container not found!');
        showAlert('Modal error. Please refresh the page.', 'error');
        return;
    }

    if (!venuesList || venuesList.length === 0) {
        venuesListContainer.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-dark);">No venues available</p>';
    } else {
        // Generate venues list
        venuesListContainer.innerHTML = venuesList.map(venue => `
            <div class="venue-option ${selectedVenue && selectedVenue.id === venue.id ? 'selected' : ''}" 
                 onclick="selectVenue(${venue.id})">
                <div class="venue-option-image">
                    <img src="${venue.featured_image || 'images/venues/default.jpg'}" 
                         alt="${venue.name}" 
                         onerror="this.src='images/venues/default.jpg'">
                </div>
                <div class="venue-option-info">
                    <h4>${venue.name}</h4>
                    <div class="venue-option-details">
                        <span><i class="fas fa-users"></i> Up to ${venue.capacity} guests</span>
                        <span><i class="fas fa-money-bill-wave"></i> ₱${formatNumber(venue.price_per_hour)}/hour</span>
                    </div>
                    <div class="venue-option-status ${venue.status}">
                        ${venue.status === 'available' ? 'Available' : 'Limited Availability'}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Show modal
    console.log('Showing modal...');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    console.log('Modal displayed, style:', modal.style.display);
}

async function selectVenue(venueId) {
    try {
        const response = await fetch(`api/venues.php?id=${venueId}`);
        const data = await response.json();
        
        if (data.success && data.venue) {
            selectedVenue = data.venue;
            selectedPackage = null; // Reset package selection
            
            // Update UI
            displayVenuePreview(selectedVenue);
            document.getElementById('packageGroup').style.display = 'none';
            
            // Store in session
            sessionStorage.setItem('selectedVenue', JSON.stringify(selectedVenue));
            sessionStorage.removeItem('selectedPackage');
            
            // Recalculate booking
            calculateBookingTotal();
            
            // Close modal
            closeModal('changeVenueModal');
            
            showAlert('Venue updated successfully', 'success');
            
        } else {
            throw new Error('Failed to load venue details');
        }
    } catch (error) {
        console.error('Error selecting venue:', error);
        showAlert('Error selecting venue. Please try again.', 'error');
    }
}

function changeVenue() {
    console.log('changeVenue function called');
    showVenueSelection();
}

function changePackage() {
    if (selectedVenue && selectedVenue.packages && selectedVenue.packages.length > 0) {
        // Show package selection (you can create a similar modal for packages)
        showAlert('Package selection modal would open here', 'info');
    } else {
        showAlert('No packages available for this venue', 'warning');
    }
}

async function validateDateAvailability(date) {
    if (!selectedVenue || !date) return;

    try {
        const response = await fetch(`api/availability.php?venue_id=${selectedVenue.id}&start_date=${date}&end_date=${date}`);
        const data = await response.json();
        
        if (data.success && data.calendar && data.calendar[date]) {
            const availability = data.calendar[date];
            const eventDateInput = document.getElementById('eventDate');
            
            if (availability.status !== 'available') {
                let message = 'Selected date is not available. ';
                if (availability.status === 'booked') {
                    message += 'The venue is already booked for this date.';
                } else if (availability.status === 'blocked') {
                    message += 'The venue is blocked for maintenance.';
                }
                
                showAlert(message, 'error');
                eventDateInput.value = '';
                return false;
            } else {
                showAlert('Date is available!', 'success', 2000);
                return true;
            }
        }
    } catch (error) {
        console.error('Error checking availability:', error);
        showAlert('Unable to verify date availability', 'warning');
        return true; // Allow booking to proceed
    }
}

function validateGuestCount(count) {
    if (!selectedVenue || !count) return true;
    
    const guestCount = parseInt(count);
    const venueCapacity = parseInt(selectedVenue.capacity);
    
    if (guestCount > venueCapacity) {
        showAlert(`Guest count exceeds venue capacity of ${venueCapacity}`, 'error');
        return false;
    }
    
    if (selectedPackage) {
        const minGuests = selectedPackage.min_guests || 0;
        const maxGuests = selectedPackage.max_guests || venueCapacity;
        
        if (guestCount < minGuests) {
            showAlert(`Package requires minimum ${minGuests} guests`, 'error');
            return false;
        }
        
        if (guestCount > maxGuests) {
            showAlert(`Package supports maximum ${maxGuests} guests`, 'error');
            return false;
        }
    }
    
    return true;
}

function calculateBookingTotal() {
    if (!selectedVenue) return;

    const startTime = document.getElementById('startTime')?.value;
    const endTime = document.getElementById('endTime')?.value;
    const guestCount = parseInt(document.getElementById('guestCount')?.value) || 0;
    
    if (!startTime || !endTime) {
        updateBookingSummary();
        return;
    }

    // Calculate hours
    const start = new Date(`2000-01-01 ${startTime}`);
    const end = new Date(`2000-01-01 ${endTime}`);
    
    // Handle overnight events
    if (end < start) {
        end.setDate(end.getDate() + 1);
    }
    
    const hours = (end - start) / (1000 * 60 * 60);
    
    // Calculate pricing
    if (selectedPackage) {
        // Package pricing
        bookingCalculation = {
            basePrice: 0,
            packagePrice: parseFloat(selectedPackage.price),
            totalAmount: parseFloat(selectedPackage.price),
            hours: selectedPackage.duration_hours || hours,
            packageHours: selectedPackage.duration_hours || 8
        };
        
        // Add extra hours if needed
        if (hours > bookingCalculation.packageHours) {
            const extraHours = hours - bookingCalculation.packageHours;
            const extraCost = extraHours * parseFloat(selectedVenue.price_per_hour);
            bookingCalculation.totalAmount += extraCost;
            bookingCalculation.extraHours = extraHours;
            bookingCalculation.extraCost = extraCost;
        }
    } else {
        // Hourly pricing
        const basePrice = hours * parseFloat(selectedVenue.price_per_hour);
        bookingCalculation = {
            basePrice: basePrice,
            packagePrice: 0,
            totalAmount: basePrice,
            hours: hours
        };
    }
    
    // Calculate down payment (25% minimum)
    bookingCalculation.downPayment = Math.max(
        bookingCalculation.totalAmount * 0.25,
        1000 // Minimum ₱1,000
    );
    bookingCalculation.balance = bookingCalculation.totalAmount - bookingCalculation.downPayment;
    
    updateBookingSummary();
    updatePaymentOptions();
}

function updateBookingSummary() {
    const summaryContainer = document.getElementById('bookingSummary');
    if (!summaryContainer) return;

    if (!selectedVenue) {
        summaryContainer.innerHTML = `
            <div class="summary-empty">
                <i class="fas fa-info-circle"></i>
                <p>Select a venue to see booking summary</p>
            </div>
        `;
        return;
    }

    const eventDate = document.getElementById('eventDate')?.value;
    const eventType = document.getElementById('eventType')?.value;
    const guestCount = document.getElementById('guestCount')?.value;
    const startTime = document.getElementById('startTime')?.value;
    const endTime = document.getElementById('endTime')?.value;

    summaryContainer.innerHTML = `
        <div class="summary-section">
            <h4>Venue</h4>
            <div class="summary-item">
                <span>${selectedVenue.name}</span>
                <span>₱${formatNumber(selectedVenue.price_per_hour)}/hr</span>
            </div>
        </div>
        
        ${selectedPackage ? `
            <div class="summary-section">
                <h4>Package</h4>
                <div class="summary-item">
                    <span>${selectedPackage.name}</span>
                    <span>₱${formatNumber(selectedPackage.price)}</span>
                </div>
            </div>
        ` : ''}
        
        <div class="summary-section">
            <h4>Event Details</h4>
            ${eventType ? `<div class="summary-detail">Type: ${eventType}</div>` : ''}
            ${eventDate ? `<div class="summary-detail">Date: ${formatDate(eventDate)}</div>` : ''}
            ${startTime && endTime ? `<div class="summary-detail">Time: ${startTime} - ${endTime}</div>` : ''}
            ${guestCount ? `<div class="summary-detail">Guests: ${guestCount}</div>` : ''}
            ${bookingCalculation.hours ? `<div class="summary-detail">Duration: ${bookingCalculation.hours}h</div>` : ''}
        </div>
        
        ${bookingCalculation.totalAmount > 0 ? `
            <div class="summary-section pricing-section">
                <h4>Pricing</h4>
                ${selectedPackage ? `
                    <div class="summary-item">
                        <span>Package (${bookingCalculation.packageHours}h)</span>
                        <span>₱${formatNumber(bookingCalculation.packagePrice)}</span>
                    </div>
                    ${bookingCalculation.extraHours ? `
                        <div class="summary-item">
                            <span>Extra hours (${bookingCalculation.extraHours}h)</span>
                            <span>₱${formatNumber(bookingCalculation.extraCost)}</span>
                        </div>
                    ` : ''}
                ` : `
                    <div class="summary-item">
                        <span>Venue rental (${bookingCalculation.hours}h)</span>
                        <span>₱${formatNumber(bookingCalculation.basePrice)}</span>
                    </div>
                `}
                
                <div class="summary-total">
                    <div class="summary-item total">
                        <span>Total Amount</span>
                        <span>₱${formatNumber(bookingCalculation.totalAmount)}</span>
                    </div>
                </div>
                
                <div class="payment-breakdown">
                    <div class="summary-item">
                        <span>Down Payment</span>
                        <span>₱${formatNumber(bookingCalculation.downPayment)}</span>
                    </div>
                    <div class="summary-item">
                        <span>Balance</span>
                        <span>₱${formatNumber(bookingCalculation.balance)}</span>
                    </div>
                </div>
            </div>
        ` : ''}
    `;
}

function updatePaymentOptions() {
    const downPaymentElement = document.getElementById('downPaymentAmount');
    const fullPaymentElement = document.getElementById('fullPaymentAmount');
    
    if (downPaymentElement && bookingCalculation.downPayment) {
        downPaymentElement.textContent = `₱${formatNumber(bookingCalculation.downPayment)}`;
    }
    
    if (fullPaymentElement && bookingCalculation.totalAmount) {
        fullPaymentElement.textContent = `₱${formatNumber(bookingCalculation.totalAmount)}`;
    }
}

function updatePaymentInstructions(paymentMethod) {
    const instructionsContainer = document.getElementById('paymentInstructions');
    if (!instructionsContainer) return;

    // Get payment settings from window object (set by PHP)
    const settings = window.paymentSettings || {};
    
    // Helper function to get setting with fallback
    const getSetting = (key, fallback) => settings[key] || fallback;

    const instructions = {
        cash: `
            <div class="payment-instruction-card">
                <h4><i class="fas fa-money-bill-wave"></i> Cash Payment Instructions</h4>
                <ul>
                    <li>Payment can be made upon arrival or during venue visit</li>
                    <li>Exact change is appreciated but not required</li>
                    <li>Receipt will be provided upon payment</li>
                    <li>For large amounts, please notify us in advance</li>
                </ul>
                <div class="instruction-note">
                    <i class="fas fa-info-circle"></i>
                    ${getSetting('payment_instructions', 'Your booking will be confirmed immediately, but please settle payment as agreed')}
                </div>
            </div>
        `,
        bank_transfer: `
            <div class="payment-instruction-card">
                <h4><i class="fas fa-university"></i> Bank Transfer Instructions</h4>
                <div class="bank-details">
                    <div class="bank-info">
                        <strong>Bank:</strong> ${getSetting('bank_name', 'BDO Unibank')}<br>
                        <strong>Account Name:</strong> ${getSetting('bank_account_name', "Mavic's Resort")}<br>
                        <strong>Account Number:</strong> ${getSetting('bank_account_number', '1234-5678-9012')}<br>
                        <strong>Branch:</strong> ${getSetting('bank_branch', 'Branch Name')}
                    </div>
                </div>
                <ul>
                    <li>Transfer the exact amount shown above</li>
                    <li>Use booking reference as transfer description</li>
                    <li>Send payment confirmation to our email</li>
                    <li>Allow 1-2 business days for confirmation</li>
                </ul>
                <div class="instruction-note">
                    <i class="fas fa-info-circle"></i>
                    ${getSetting('payment_instructions', 'Please use your booking ID as reference when making a bank transfer')}
                </div>
            </div>
        `,
        gcash: `
            <div class="payment-instruction-card">
                <h4><i class="fas fa-mobile-alt"></i> GCash Payment Instructions</h4>
                <div class="gcash-details">
                    ${getSetting('gcash_qr_code') ? `
                        <div class="gcash-qr-section" style="text-align: center; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                            <div style="background: white; display: inline-block; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <img src="../admin/${getSetting('gcash_qr_code')}" 
                                     alt="GCash QR Code" 
                                     class="gcash-qr-image" 
                                     style="max-width: 250px; width: 100%; height: auto; display: block; border-radius: 8px;" 
                                     onerror="this.parentElement.parentElement.innerHTML='<div style=\'padding: 40px; text-align: center; color: #999;\'><i class=\'fas fa-exclamation-triangle fa-2x\' style=\'margin-bottom: 10px;\'></i><p>QR Code not available</p><p style=\'font-size: 12px;\'>Please use the account details below</p></div>';">
                            </div>
                            <p style="font-size: 14px; color: #666; margin-top: 15px; font-weight: 500;">Scan this QR code with your GCash app</p>
                        </div>
                    ` : '<div style="text-align: center; padding: 20px; color: #999;"><p>QR Code not yet configured</p></div>'}
                    <div class="gcash-info" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; margin-top: 15px;">
                        <div style="margin-bottom: 10px;"><strong>GCash Number:</strong> <span style="color: #007bff; font-family: monospace; font-size: 16px;">${getSetting('gcash_number', '0961 306 7957')}</span></div>
                        <div><strong>Account Name:</strong> ${getSetting('gcash_account_name', "Mavic's Resort")}</div>
                    </div>
                </div>
                <ol style="margin: 20px 0; padding-left: 20px; line-height: 1.8;">
                    <li><strong>Option 1:</strong> Scan the QR code above with your GCash app</li>
                    <li><strong>Option 2:</strong> Send money manually to the GCash number above</li>
                    <li>Enter the payment amount shown in your booking summary</li>
                    <li>Include your booking reference in the message</li>
                    <li>Complete the transaction</li>
                    <li>Take a screenshot of the confirmation receipt</li>
                    <li>Send the screenshot to our contact number</li>
                </ol>
                <div class="instruction-note" style="background: #e3f2fd; padding: 12px 15px; border-radius: 6px; border-left: 4px solid #2196f3; margin-top: 15px;">
                    <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 8px;"></i>
                    <span style="color: #1976d2;">${getSetting('payment_instructions', 'After payment, please send a screenshot of your GCash receipt with your booking ID')}</span>
                </div>
            </div>
        `
    };

    instructionsContainer.innerHTML = instructions[paymentMethod] || '';
}

// Step navigation functions
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < 4) {
            currentStep++;
            showStep(currentStep);
            updateStepIndicator();
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateStepIndicator();
    }
}

function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        default:
            return true;
    }
}

function validateStep1() {
    if (!selectedVenue) {
        showAlert('Please select a venue', 'error');
        return false;
    }

    const requiredFields = ['eventType', 'eventDate', 'guestCount', 'startTime', 'endTime'];
    const missingFields = [];

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element || !element.value.trim()) {
            missingFields.push(field);
        }
    });

    if (missingFields.length > 0) {
        showAlert('Please fill in all required fields', 'error');
        return false;
    }

    // Validate guest count
    const guestCount = parseInt(document.getElementById('guestCount').value);
    if (!validateGuestCount(guestCount)) {
        return false;
    }

    // Validate time
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    if (startTime >= endTime && !confirm('Your event appears to go overnight. Is this correct?')) {
        return false;
    }

    return true;
}

function validateStep2() {
    const requiredFields = ['firstName', 'lastName', 'email', 'phone'];
    const missingFields = [];

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element || !element.value.trim()) {
            missingFields.push(field);
        }
    });

    if (missingFields.length > 0) {
        showAlert('Please fill in all required contact information', 'error');
        return false;
    }

    // Validate email
    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showAlert('Please enter a valid email address', 'error');
        return false;
    }

    // Validate phone
    const phone = document.getElementById('phone').value;
    if (phone.length < 10) {
        showAlert('Please enter a valid phone number', 'error');
        return false;
    }

    return true;
}

function validateStep3() {
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
    const paymentAmount = document.querySelector('input[name="paymentAmount"]:checked');

    if (!paymentMethod) {
        showAlert('Please select a payment method', 'error');
        return false;
    }

    if (!paymentAmount) {
        showAlert('Please select payment amount', 'error');
        return false;
    }

    return true;
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(stepEl => {
        stepEl.classList.remove('active');
    });

    // Show current step
    const currentStepEl = document.getElementById(`step${step}`);
    if (currentStepEl) {
        currentStepEl.classList.add('active');
    }
}

function updateStepIndicator() {
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        const stepNumber = index + 1;
        if (stepNumber <= currentStep) {
            stepEl.classList.add('active');
        } else {
            stepEl.classList.remove('active');
        }
        
        if (stepNumber < currentStep) {
            stepEl.classList.add('completed');
        } else {
            stepEl.classList.remove('completed');
        }
    });
}

// Main booking processing function
async function processBooking() {
    if (!validateCurrentStep()) {
        return;
    }

    const bookingFormData = collectBookingData();
    
    // Show loading
    const processBtn = document.querySelector('button[onclick="processBooking()"]');
    const originalText = processBtn.innerHTML;
    processBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    processBtn.disabled = true;

    try {
        const response = await fetch('api/process-booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(bookingFormData)
        });

        const result = await response.json();

        if (result.success) {
            // Update booking reference
            document.getElementById('bookingReference').textContent = result.booking_reference;
            
            // Move to confirmation step
            currentStep = 4;
            showStep(4);
            updateStepIndicator();
            
            // Clear form data
            sessionStorage.removeItem('selectedVenue');
            sessionStorage.removeItem('selectedPackage');
            
            showAlert('Booking confirmed successfully!', 'success');
            
            // Store booking ID for receipt/details
            sessionStorage.setItem('lastBookingId', result.booking_id);
            
        } else {
            throw new Error(result.message || 'Booking failed');
        }
        
    } catch (error) {
        console.error('Booking error:', error);
        showAlert('Booking failed: ' + error.message, 'error');
    } finally {
        // Restore button
        processBtn.innerHTML = originalText;
        processBtn.disabled = false;
    }
}

function collectBookingData() {
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
    const paymentAmount = document.querySelector('input[name="paymentAmount"]:checked')?.value;
    
    return {
        // Venue and package
        venue_id: selectedVenue.id,
        package_id: selectedPackage?.id || null,
        
        // Event details
        event_type: document.getElementById('eventType').value,
        event_date: document.getElementById('eventDate').value,
        start_time: document.getElementById('startTime').value,
        end_time: document.getElementById('endTime').value,
        guest_count: parseInt(document.getElementById('guestCount').value),
        special_requests: document.getElementById('specialRequests').value,
        
        // Customer info
        customer: {
            first_name: document.getElementById('firstName').value,
            last_name: document.getElementById('lastName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            emergency_contact_name: document.getElementById('emergencyName').value,
            emergency_contact_phone: document.getElementById('emergencyPhone').value
        },
        
        // Pricing
        total_amount: bookingCalculation.totalAmount,
        down_payment: paymentAmount === 'down' ? bookingCalculation.downPayment : bookingCalculation.totalAmount,
        balance: paymentAmount === 'down' ? bookingCalculation.balance : 0,
        
        // Payment
        payment_method: paymentMethod,
        payment_amount_type: paymentAmount
    };
}

function viewBookingDetails() {
    const bookingId = sessionStorage.getItem('lastBookingId');
    if (bookingId) {
        window.location.href = `booking-details.php?id=${bookingId}`;
    }
}

function downloadReceipt() {
    const bookingId = sessionStorage.getItem('lastBookingId');
    if (bookingId) {
        window.open(`api/download-receipt.php?id=${bookingId}`, '_blank');
    }
}

// Utility functions
function formatNumber(number) {
    return new Intl.NumberFormat('en-PH').format(number);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function showAlert(message, type = 'info', duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.booking-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `booking-alert alert-${type}`;
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
        animation: slideInAlert 0.3s ease-out;
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
                alertDiv.style.animation = 'slideOutAlert 0.3s ease-in';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, duration);
    }
}

// Add CSS for alert animations
if (!document.querySelector('#booking-alert-styles')) {
    const style = document.createElement('style');
    style.id = 'booking-alert-styles';
    style.textContent = `
        @keyframes slideInAlert {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutAlert {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .booking-alert .alert-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-alert .alert-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            opacity: 0.7;
        }
        .booking-alert .alert-close:hover {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
}