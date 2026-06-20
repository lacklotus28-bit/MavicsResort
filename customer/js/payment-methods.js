// Payment Methods Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment Methods page loaded');
    
    // Form validation
    const paymentForm = document.getElementById('addPaymentForm');
    if (paymentForm) {
        const paymentTypeSelect = document.getElementById('paymentType');
        const expiryMonth = document.getElementById('expiryMonth');
        const expiryYear = document.getElementById('expiryYear');
        
        paymentTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            
            // Show/hide expiry fields based on payment type
            if (selectedType === 'credit_card' || selectedType === 'debit_card') {
                expiryMonth.required = true;
                expiryYear.required = true;
            } else {
                expiryMonth.required = false;
                expiryYear.required = false;
            }
        });
        
        // Validate last 4 digits input
        const lastFourDigits = document.getElementById('lastFourDigits');
        if (lastFourDigits) {
            lastFourDigits.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
            });
        }
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('addPaymentModal');
        if (modal && modal.classList.contains('active')) {
            closeAddPaymentModal();
        }
    }
});
