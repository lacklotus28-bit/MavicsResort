// My Bookings Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('My Bookings Page Loaded');
    
    // Initialize page features
    initializeAnimations();
    initializeSearchHighlight();
});

/**
 * Initialize page animations
 */
function initializeAnimations() {
    // Animate booking items on scroll
    const bookingItems = document.querySelectorAll('.booking-item');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    bookingItems.forEach(item => {
        observer.observe(item);
    });
}

/**
 * Initialize search term highlighting
 */
function initializeSearchHighlight() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    
    if (searchTerm) {
        highlightSearchTerms(searchTerm);
    }
}

/**
 * Highlight search terms in booking items
 */
function highlightSearchTerms(term) {
    const bookingItems = document.querySelectorAll('.booking-item');
    const regex = new RegExp(`(${term})`, 'gi');
    
    bookingItems.forEach(item => {
        const textElements = item.querySelectorAll('h3, .detail-value, .booking-id');
        
        textElements.forEach(element => {
            const text = element.textContent;
            if (regex.test(text)) {
                element.innerHTML = text.replace(regex, '<mark style="background: #fef3c7; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            }
        });
    });
}

/**
 * Change sort order
 */
function changeSortOrder(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortValue);
    window.location.href = '?' + urlParams.toString();
}

/**
 * Clear search
 */
function clearSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('search');
    window.location.href = '?' + urlParams.toString();
}

/**
 * Show loading indicator
 */
function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loading-overlay';
    loadingOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    loadingOverlay.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div class="loading-spinner" style="width: 50px; height: 50px; border: 4px solid #f3f4f6; border-top-color: #8B4513; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
            <p style="margin: 0; color: #1f2937; font-weight: 600;">Loading...</p>
        </div>
    `;
    
    document.body.appendChild(loadingOverlay);
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Add spinner animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Expose functions to global scope
window.changeSortOrder = changeSortOrder;
window.clearSearch = clearSearch;