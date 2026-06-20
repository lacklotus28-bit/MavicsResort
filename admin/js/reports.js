// Enhanced Reports JavaScript - Mavic's Resort with Professional Responsiveness
document.addEventListener('DOMContentLoaded', function() {
    // Initialize responsive system
    initializeResponsiveSystem();
    
    // Initialize charts with responsive handling
    initializeCharts();
    
    // Add event listeners
    setupEventListeners();
    
    // Apply animations
    applyAnimations();
    
    // Initialize responsive interactions
    initializeResponsiveInteractions();
});

// Responsive breakpoint system
const BREAKPOINTS = {
    xs: 575,
    sm: 767,
    md: 1023,
    lg: 1199,
    xl: 1399
};

let currentBreakpoint = getCurrentBreakpoint();

function getCurrentBreakpoint() {
    const width = window.innerWidth;
    if (width <= BREAKPOINTS.xs) return 'xs';
    if (width <= BREAKPOINTS.sm) return 'sm';
    if (width <= BREAKPOINTS.md) return 'md';
    if (width <= BREAKPOINTS.lg) return 'lg';
    if (width <= BREAKPOINTS.xl) return 'xl';
    return 'xxl';
}

// Enhanced chart colors with better contrast for mobile
const chartColors = {
    primary: '#8B5A3C',
    primaryLight: '#A67C5A',
    primaryDark: '#6B4226',
    secondary: '#D2B48C',
    secondaryDark: '#CD853F',
    success: '#8FBC8F',
    danger: '#CD5C5C',
    warning: '#DEB887',
    info: '#5F9EA0',
    gradient: {
        primary: ['#8B5A3C', '#6B4226'],
        secondary: ['#D2B48C', '#CD853F'],
        success: ['#8FBC8F', '#6B8E6B'],
        multi: ['#8B5A3C', '#D2B48C', '#8FBC8F', '#DEB887', '#5F9EA0', '#CD5C5C']
    }
};

// Responsive chart configuration
function getResponsiveChartOptions(baseOptions = {}) {
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    const isTablet = currentBreakpoint === 'md';
    
    const responsiveOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: !isMobile || baseOptions.type === 'doughnut' || baseOptions.type === 'pie',
                position: isMobile ? 'bottom' : baseOptions.legendPosition || 'top',
                labels: {
                    color: '#4A3728',
                    font: {
                        family: 'Poppins, sans-serif',
                        size: isMobile ? 10 : isTablet ? 11 : 12,
                        weight: '500'
                    },
                    padding: isMobile ? 10 : 15,
                    usePointStyle: isMobile,
                    boxWidth: isMobile ? 8 : 12,
                    boxHeight: isMobile ? 8 : 12
                }
            },
            tooltip: {
                enabled: true,
                backgroundColor: 'rgba(75, 55, 40, 0.95)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#8B5A3C',
                borderWidth: 1,
                cornerRadius: 8,
                titleFont: {
                    family: 'Poppins, sans-serif',
                    size: isMobile ? 12 : 14,
                    weight: '600'
                },
                bodyFont: {
                    family: 'Poppins, sans-serif',
                    size: isMobile ? 11 : 13,
                    weight: '400'
                },
                padding: isMobile ? 8 : 12
            }
        }
    };
    
    // Add scales for charts that need them
    if (baseOptions.type !== 'doughnut' && baseOptions.type !== 'pie') {
        responsiveOptions.scales = {
            x: {
                display: !isMobile || baseOptions.showAxes !== false,
                ticks: {
                    color: '#4A3728',
                    font: {
                        family: 'Poppins, sans-serif',
                        size: isMobile ? 9 : isTablet ? 10 : 11
                    },
                    maxRotation: isMobile ? 45 : 0,
                    maxTicksLimit: isMobile ? 5 : isTablet ? 8 : 12
                },
                grid: {
                    color: 'rgba(139, 90, 60, 0.1)',
                    display: !isMobile
                }
            },
            y: {
                display: !isMobile || baseOptions.showAxes !== false,
                ticks: {
                    color: '#4A3728',
                    font: {
                        family: 'Poppins, sans-serif',
                        size: isMobile ? 9 : isTablet ? 10 : 11
                    },
                    maxTicksLimit: isMobile ? 5 : 8,
                    callback: function(value) {
                        if (baseOptions.formatCurrency) {
                            return '₱' + value.toLocaleString();
                        }
                        return value;
                    }
                },
                grid: {
                    color: 'rgba(139, 90, 60, 0.1)'
                }
            }
        };
        
        // Add secondary y-axis for dual-axis charts
        if (baseOptions.dualAxis) {
            responsiveOptions.scales.y1 = {
                type: 'linear',
                display: !isMobile,
                position: 'right',
                ticks: {
                    color: '#4A3728',
                    font: {
                        family: 'Poppins, sans-serif',
                        size: isMobile ? 9 : isTablet ? 10 : 11
                    },
                    maxTicksLimit: isMobile ? 5 : 8
                },
                grid: {
                    drawOnChartArea: false,
                    color: 'rgba(210, 180, 140, 0.1)'
                }
            };
        }
    }
    
    return { ...baseOptions, ...responsiveOptions };
}

function initializeResponsiveSystem() {
    // Handle responsive table scrolling
    initializeTableResponsiveness();
    
    // Handle responsive filter layout
    initializeFilterResponsiveness();
    
    // Setup window resize handler with debouncing
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const newBreakpoint = getCurrentBreakpoint();
            if (newBreakpoint !== currentBreakpoint) {
                currentBreakpoint = newBreakpoint;
                handleBreakpointChange();
            }
            handleChartResize();
        }, 250);
    });
}

function initializeTableResponsiveness() {
    const tables = document.querySelectorAll('.table-container');
    tables.forEach(container => {
        const table = container.querySelector('.table');
        if (table) {
            // Add scroll indicators for mobile
            addScrollIndicators(container);
            
            // Handle table scroll events
            container.addEventListener('scroll', function() {
                updateScrollIndicators(container);
            });
        }
    });
}

function addScrollIndicators(container) {
    if (getCurrentBreakpoint() === 'xs' || getCurrentBreakpoint() === 'sm') {
        container.classList.add('scrollable-table');
        
        // Add scroll hint if not already added
        if (!container.querySelector('.scroll-hint')) {
            const hint = document.createElement('div');
            hint.className = 'scroll-hint';
            hint.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Scroll to view more';
            container.parentElement.insertBefore(hint, container);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (hint.parentElement) {
                    hint.style.opacity = '0';
                    setTimeout(() => hint.remove(), 300);
                }
            }, 3000);
        }
    }
}

function updateScrollIndicators(container) {
    const scrollLeft = container.scrollLeft;
    const scrollWidth = container.scrollWidth;
    const clientWidth = container.clientWidth;
    
    container.classList.toggle('scroll-start', scrollLeft === 0);
    container.classList.toggle('scroll-end', scrollLeft + clientWidth >= scrollWidth - 1);
}

function initializeFilterResponsiveness() {
    const quickFilters = document.querySelector('.quick-filters');
    if (quickFilters && (currentBreakpoint === 'xs' || currentBreakpoint === 'sm')) {
        // Convert to dropdown on very small screens
        convertFiltersToDropdown(quickFilters);
    }
}

function convertFiltersToDropdown(quickFilters) {
    if (quickFilters.querySelector('.filter-dropdown')) return; // Already converted
    
    const buttons = quickFilters.querySelectorAll('.btn');
    if (buttons.length === 0) return;
    
    const dropdown = document.createElement('select');
    dropdown.className = 'form-control filter-dropdown';
    dropdown.style.marginBottom = 'var(--spacing-sm)';
    
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Quick Filter...';
    dropdown.appendChild(defaultOption);
    
    buttons.forEach(btn => {
        const option = document.createElement('option');
        option.value = btn.textContent.toLowerCase().trim();
        option.textContent = btn.textContent;
        dropdown.appendChild(option);
    });
    
    dropdown.addEventListener('change', function() {
        if (this.value) {
            setQuickFilter(this.value);
        }
    });
    
    quickFilters.style.display = 'none';
    quickFilters.parentElement.insertBefore(dropdown, quickFilters);
}

function handleBreakpointChange() {
    console.log('Breakpoint changed to:', currentBreakpoint);
    
    // Reinitialize responsive components
    initializeTableResponsiveness();
    initializeFilterResponsiveness();
    
    // Update chart responsiveness
    if (typeof Chart !== 'undefined') {
        Object.values(Chart.instances).forEach(chart => {
            updateChartResponsiveness(chart);
        });
    }
    
    // Update stat cards layout
    updateStatCardsLayout();
    
    // Update export actions layout
    updateExportActionsLayout();
}

function updateChartResponsiveness(chart) {
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    
    // Update legend
    if (chart.options.plugins && chart.options.plugins.legend) {
        chart.options.plugins.legend.display = !isMobile || chart.config.type === 'doughnut' || chart.config.type === 'pie';
        chart.options.plugins.legend.position = isMobile ? 'bottom' : 'top';
        chart.options.plugins.legend.labels.font.size = isMobile ? 10 : 12;
        chart.options.plugins.legend.labels.padding = isMobile ? 10 : 15;
    }
    
    // Update scales
    if (chart.options.scales) {
        ['x', 'y', 'y1'].forEach(axis => {
            if (chart.options.scales[axis]) {
                chart.options.scales[axis].display = !isMobile || chart.config.showAxes !== false;
                if (chart.options.scales[axis].ticks) {
                    chart.options.scales[axis].ticks.font.size = isMobile ? 9 : 11;
                    chart.options.scales[axis].ticks.maxTicksLimit = isMobile ? 5 : 8;
                    if (axis === 'x') {
                        chart.options.scales[axis].ticks.maxRotation = isMobile ? 45 : 0;
                    }
                }
                if (chart.options.scales[axis].grid && axis === 'x') {
                    chart.options.scales[axis].grid.display = !isMobile;
                }
            }
        });
    }
    
    chart.update('resize');
}

function updateStatCardsLayout() {
    const statCards = document.querySelectorAll('.stat-card');
    const isMobile = currentBreakpoint === 'xs';
    
    statCards.forEach(card => {
        if (isMobile) {
            card.style.flexDirection = 'column';
            card.style.textAlign = 'center';
        } else {
            card.style.flexDirection = 'row';
            card.style.textAlign = 'left';
        }
    });
}

function updateExportActionsLayout() {
    const exportActions = document.querySelector('.export-actions');
    if (!exportActions) return;
    
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    
    if (isMobile) {
        exportActions.style.flexDirection = 'column';
        exportActions.querySelectorAll('.btn').forEach(btn => {
            btn.style.width = '100%';
            btn.style.minWidth = 'auto';
        });
    } else {
        exportActions.style.flexDirection = 'row';
        exportActions.querySelectorAll('.btn').forEach(btn => {
            btn.style.width = 'auto';
            btn.style.minWidth = '180px';
        });
    }
}

function initializeCharts() {
    if (typeof reportData === 'undefined') {
        console.warn('Report data not available');
        showChartError('No data available');
        return;
    }
    
    try {
        // Revenue Trends Chart (Line Chart)
        initializeRevenueChart();
        
        // Event Types Chart (Doughnut Chart)
        initializeEventTypesChart();
        
        // Venue Performance Chart (Bar Chart)
        initializeVenueChart();
        
        // Payment Methods Chart (Pie Chart)
        initializePaymentMethodsChart();
        
    } catch (error) {
        console.error('Chart initialization error:', error);
        showChartError('Error loading charts');
    }
}

function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx || !reportData.bookingTrends) {
        handleChartError('revenueChart', 'No revenue data available');
        return;
    }
    
    const labels = reportData.bookingTrends.map(item => {
        const date = new Date(item.month + '-01');
        return currentBreakpoint === 'xs' ? 
            date.toLocaleDateString('en-US', { month: 'short' }) :
            date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    
    const revenueData = reportData.bookingTrends.map(item => parseFloat(item.revenue) || 0);
    const bookingData = reportData.bookingTrends.map(item => parseInt(item.count) || 0);
    
    const options = getResponsiveChartOptions({
        type: 'line',
        dualAxis: true,
        formatCurrency: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        if (label.includes('Revenue')) {
                            return `${label}: ₱${value.toLocaleString()}`;
                        }
                        return `${label}: ${value}`;
                    }
                }
            }
        }
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue (₱)',
                    data: revenueData,
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(139, 90, 60, 0.1)',
                    borderWidth: currentBreakpoint === 'xs' ? 2 : 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: chartColors.primary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: currentBreakpoint === 'xs' ? 4 : 6,
                    pointHoverRadius: currentBreakpoint === 'xs' ? 6 : 8
                },
                {
                    label: 'Bookings',
                    data: bookingData,
                    borderColor: chartColors.secondary,
                    backgroundColor: 'rgba(210, 180, 140, 0.1)',
                    borderWidth: currentBreakpoint === 'xs' ? 2 : 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: chartColors.secondary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: currentBreakpoint === 'xs' ? 4 : 6,
                    pointHoverRadius: currentBreakpoint === 'xs' ? 6 : 8,
                    yAxisID: 'y1'
                }
            ]
        },
        options: options
    });
}

function initializeEventTypesChart() {
    const ctx = document.getElementById('eventTypesChart');
    if (!ctx || !reportData.eventPopularity) {
        handleChartError('eventTypesChart', 'No event data available');
        return;
    }
    
    const labels = reportData.eventPopularity.map(item => item.event_type);
    const data = reportData.eventPopularity.map(item => parseInt(item.count) || 0);
    
    const options = getResponsiveChartOptions({
        type: 'doughnut',
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        }
    });
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: chartColors.gradient.multi,
                borderColor: '#ffffff',
                borderWidth: currentBreakpoint === 'xs' ? 2 : 3,
                hoverBorderWidth: currentBreakpoint === 'xs' ? 3 : 4
            }]
        },
        options: options
    });
}

function initializeVenueChart() {
    const ctx = document.getElementById('venueChart');
    if (!ctx || !reportData.venuePopularity) {
        handleChartError('venueChart', 'No venue data available');
        return;
    }
    
    const labels = reportData.venuePopularity.map(item => {
        // Truncate long venue names on mobile
        return currentBreakpoint === 'xs' && item.name.length > 10 ? 
            item.name.substring(0, 10) + '...' : item.name;
    });
    const bookingsData = reportData.venuePopularity.map(item => parseInt(item.bookings) || 0);
    const revenueData = reportData.venuePopularity.map(item => parseFloat(item.revenue) || 0);
    
    const options = getResponsiveChartOptions({
        type: 'bar',
        dualAxis: true,
        formatCurrency: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        if (label.includes('Revenue')) {
                            return `${label}: ₱${value.toLocaleString()}`;
                        }
                        return `${label}: ${value}`;
                    }
                }
            }
        }
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Bookings',
                    data: bookingsData,
                    backgroundColor: chartColors.primary,
                    borderColor: chartColors.primaryDark,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Revenue (₱)',
                    data: revenueData,
                    backgroundColor: chartColors.secondary,
                    borderColor: chartColors.secondaryDark,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                    yAxisID: 'y1'
                }
            ]
        },
        options: options
    });
}

function initializePaymentMethodsChart() {
    const ctx = document.getElementById('paymentMethodsChart');
    if (!ctx || !reportData.paymentMethods) {
        handleChartError('paymentMethodsChart', 'No payment data available');
        return;
    }
    
    const labels = reportData.paymentMethods.map(item => item.payment_method.toUpperCase());
    const data = reportData.paymentMethods.map(item => parseFloat(item.total_amount) || 0);
    
    const options = getResponsiveChartOptions({
        type: 'pie',
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                        return `${context.label}: ₱${context.parsed.toLocaleString()} (${percentage}%)`;
                    }
                }
            }
        }
    });
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: chartColors.gradient.multi,
                borderColor: '#ffffff',
                borderWidth: currentBreakpoint === 'xs' ? 2 : 3,
                hoverBorderWidth: currentBreakpoint === 'xs' ? 3 : 4
            }]
        },
        options: options
    });
}

function handleChartError(chartId, message) {
    console.error(`Error with chart ${chartId}:`, message);
    
    const container = document.querySelector(`#${chartId}`)?.closest('.chart-container');
    if (container) {
        container.innerHTML = `
            <div class="chart-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
                <button class="btn btn-sm btn-outline" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Retry
                </button>
            </div>
        `;
    }
}

function showChartError(message) {
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        if (!container.querySelector('canvas')) return;
        container.innerHTML = `
            <div class="chart-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    });
}

function setupEventListeners() {
    // Quick filter buttons (with responsive handling)
    document.querySelectorAll('.quick-filters .btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const filterType = this.textContent.toLowerCase().trim();
            setQuickFilter(filterType);
        });
    });
    
    // Enhanced form submission with loading state
    const filterForm = document.querySelector('.filters-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            showLoading();
            
            // Add timeout to hide loading if form submission takes too long
            setTimeout(() => {
                hideLoading();
            }, 10000);
        });
    }
    
    // Touch-friendly interactions for mobile
    if (currentBreakpoint === 'xs' || currentBreakpoint === 'sm') {
        addTouchInteractions();
    }
}

function addTouchInteractions() {
    // Add touch feedback to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        btn.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
        });
    });
    
    // Add swipe detection for table scrolling hint
    let startX = null;
    document.querySelectorAll('.table-container').forEach(container => {
        container.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        container.addEventListener('touchmove', function(e) {
            if (startX !== null) {
                const currentX = e.touches[0].clientX;
                const diff = startX - currentX;
                
                // Remove scroll hint on swipe
                const hint = container.parentElement.querySelector('.scroll-hint');
                if (hint && Math.abs(diff) > 10) {
                    hint.style.opacity = '0';
                    setTimeout(() => hint.remove(), 300);
                }
            }
        });
        
        container.addEventListener('touchend', function() {
            startX = null;
        });
    });
}

function setQuickFilter(period) {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (!startDateInput || !endDateInput) {
        console.warn('Date inputs not found');
        return;
    }
    
    const today = new Date();
    let startDate, endDate;
    
    switch(period) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'this week':
        case 'week':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - today.getDay());
            endDate = today;
            break;
        case 'this month':
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = today;
            break;
        case 'this quarter':
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            startDate = new Date(today.getFullYear(), quarter * 3, 1);
            endDate = today;
            break;
        case 'this year':
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = today;
            break;
        default:
            console.warn('Unknown filter period:', period);
            return;
    }
    
    startDateInput.value = startDate.toISOString().split('T')[0];
    endDateInput.value = endDate.toISOString().split('T')[0];
    
    // Visual feedback for mobile
    if (currentBreakpoint === 'xs' || currentBreakpoint === 'sm') {
        const button = event?.target;
        if (button) {
            button.classList.add('active');
            setTimeout(() => button.classList.remove('active'), 200);
        }
    }
    
    // Submit form with loading state
    showLoading();
    document.querySelector('.filters-form').submit();
}

function applyAnimations() {
    // Apply staggered fade-in animations (respecting reduced motion preference)
    const cards = document.querySelectorAll('.stat-card, .chart-card, .report-table-card');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (!prefersReducedMotion) {
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });
    }
}

function initializeResponsiveInteractions() {
    // Initialize tooltips and interactive elements with responsive behavior
    initializeInteractivity();
    
    // Add responsive table interactions
    initializeTableInteractions();
    
    // Setup responsive stat card interactions
    initializeStatCardInteractions();
}

function initializeTableInteractions() {
    const tableRows = document.querySelectorAll('.table tbody tr');
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    
    tableRows.forEach(row => {
        if (isMobile) {
            // Touch-friendly interactions for mobile
            row.addEventListener('touchstart', function() {
                this.style.backgroundColor = 'var(--light-gray)';
            });
            
            row.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 200);
            });
        } else {
            // Mouse interactions for desktop
            row.addEventListener('click', function() {
                tableRows.forEach(r => r.classList.remove('active'));
                this.classList.add('active');
            });
        }
    });
}

function initializeStatCardInteractions() {
    const statCards = document.querySelectorAll('.stat-card');
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    
    statCards.forEach(card => {
        if (!isMobile) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px)';
            });
        }
    });
}

// Enhanced export functions with responsive handling
function exportChart(chartId, filename) {
    showLoading('Preparing chart export...');
    
    setTimeout(() => {
        try {
            const canvas = document.getElementById(chartId);
            if (canvas) {
                // Increase resolution for better quality on mobile
                const scale = currentBreakpoint === 'xs' ? 2 : 3;
                const tempCanvas = document.createElement('canvas');
                const tempCtx = tempCanvas.getContext('2d');
                
                tempCanvas.width = canvas.width * scale;
                tempCanvas.height = canvas.height * scale;
                tempCtx.scale(scale, scale);
                tempCtx.drawImage(canvas, 0, 0);
                
                const link = document.createElement('a');
                link.download = `${filename}-${new Date().toISOString().split('T')[0]}.png`;
                link.href = tempCanvas.toDataURL('image/png', 1.0);
                link.click();
            }
        } catch (error) {
            console.error('Export error:', error);
            alert('Export failed. Please try again.');
        }
        hideLoading();
    }, 500);
}

function exportTable(tableId, filename) {
    showLoading('Preparing table export...');
    
    setTimeout(() => {
        try {
            const table = document.getElementById(tableId);
            if (table) {
                let csv = '';
                const rows = table.querySelectorAll('tr');
                
                rows.forEach(row => {
                    const cols = row.querySelectorAll('th, td');
                    const csvRow = [];
                    cols.forEach(col => {
                        let text = col.textContent.trim();
                        // Clean up text and handle special characters
                        text = text.replace(/\s+/g, ' ').replace(/"/g, '""');
                        csvRow.push(`"${text}"`);
                    });
                    csv += csvRow.join(',') + '\n';
                });
                
                downloadCSV(csv, `${filename}-${new Date().toISOString().split('T')[0]}.csv`);
            }
        } catch (error) {
            console.error('Table export error:', error);
            alert('Export failed. Please try again.');
        }
        hideLoading();
    }, 500);
}

function exportFullReport(format) {
    showLoading(`Generating ${format.toUpperCase()} report...`);
    
    if (format === 'pdf') {
        setTimeout(() => {
            try {
                generatePDFReport();
            } catch (error) {
                console.error('PDF generation error:', error);
                alert('PDF generation failed. Please try again.');
            }
            hideLoading();
        }, 1000);
    } else if (format === 'csv') {
        setTimeout(() => {
            try {
                generateCSVReport();
            } catch (error) {
                console.error('CSV generation error:', error);
                alert('CSV generation failed. Please try again.');
            }
            hideLoading();
        }, 1000);
    }
}

function generatePDFReport() {
    if (typeof window.jspdf === 'undefined') {
        console.error('jsPDF library not loaded');
        alert('PDF library not available. Please refresh the page and try again.');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF();
    const isMobile = currentBreakpoint === 'xs' || currentBreakpoint === 'sm';
    
    // Responsive font sizes
    const titleSize = isMobile ? 16 : 20;
    const headingSize = isMobile ? 12 : 16;
    const bodySize = isMobile ? 10 : 12;
    
    // Header
    pdf.setFontSize(titleSize);
    pdf.setFont(undefined, 'bold');
    pdf.text('Mavics Resort - Analytics Report', 20, 30);
    
    // Date range
    const startDate = document.getElementById('start_date')?.value || 'N/A';
    const endDate = document.getElementById('end_date')?.value || 'N/A';
    pdf.setFontSize(bodySize);
    pdf.setFont(undefined, 'normal');
    pdf.text(`Report Period: ${startDate} to ${endDate}`, 20, 45);
    
    // Summary statistics
    let yPos = 65;
    pdf.setFontSize(headingSize);
    pdf.setFont(undefined, 'bold');
    pdf.text('Summary Statistics', 20, yPos);
    
    yPos += 20;
    pdf.setFontSize(bodySize);
    pdf.setFont(undefined, 'normal');
    
    const stats = document.querySelectorAll('.stat-card');
    stats.forEach(stat => {
        const title = stat.querySelector('.stat-details h3')?.textContent || 'N/A';
        const value = stat.querySelector('.stat-number')?.textContent || 'N/A';
        const period = stat.querySelector('.stat-period')?.textContent || 'N/A';
        
        pdf.text(`${title}: ${value} (${period})`, 30, yPos);
        yPos += 15;
        
        if (yPos > 250) {
            pdf.addPage();
            yPos = 30;
        }
    });
    
    // Add table data with responsive handling
    addTableToPDF(pdf, 'venueUtilization', 'Venue Utilization Data', yPos, bodySize, headingSize);
    addTableToPDF(pdf, 'paymentStatus', 'Payment Status Data', yPos + 100, bodySize, headingSize);
    
    // Footer
    const pageCount = pdf.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        pdf.setPage(i);
        pdf.setFontSize(8);
        pdf.text(`Generated on ${new Date().toLocaleDateString()} - Page ${i} of ${pageCount}`, 20, 285);
        pdf.text('Mavics Resort Management System', 150, 285);
    }
    
    pdf.save(`mavics-resort-report-${new Date().toISOString().split('T')[0]}.pdf`);
}

function addTableToPDF(pdf, tableId, title, startY, bodySize, headingSize) {
    const table = document.getElementById(tableId);
    if (!table) return startY;
    
    let yPos = startY + 30;
    
    // Check if we need a new page
    if (yPos > 250) {
        pdf.addPage();
        yPos = 30;
    }
    
    pdf.setFontSize(headingSize);
    pdf.setFont(undefined, 'bold');
    pdf.text(title, 20, yPos);
    
    yPos += 20;
    pdf.setFontSize(bodySize);
    pdf.setFont(undefined, 'normal');
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (yPos > 270) {
            pdf.addPage();
            yPos = 30;
        }
        
        const cols = row.querySelectorAll('td');
        if (cols.length >= 2) {
            const rowData = Array.from(cols).map(col => col.textContent.trim()).join(' | ');
            pdf.text(rowData, 30, yPos);
            yPos += 12;
        }
    });
    
    return yPos;
}

function generateCSVReport() {
    let csv = 'Mavics Resort - Complete Report Data\n\n';
    
    // Summary Statistics
    csv += 'Summary Statistics\n';
    csv += 'Metric,Value,Period\n';
    
    const stats = document.querySelectorAll('.stat-card');
    stats.forEach(stat => {
        const title = stat.querySelector('.stat-details h3')?.textContent || 'N/A';
        const value = stat.querySelector('.stat-number')?.textContent || 'N/A';
        const period = stat.querySelector('.stat-period')?.textContent || 'N/A';
        csv += `"${title}","${value}","${period}"\n`;
    });
    
    csv += '\n';
    
    // Add table data
    ['venueUtilization', 'paymentStatus'].forEach(tableId => {
        const table = document.getElementById(tableId);
        if (table) {
            const tableTitle = table.closest('.report-table-card')?.querySelector('h3')?.textContent || tableId;
            csv += `${tableTitle}\n`;
            
            const headers = table.querySelectorAll('thead th');
            if (headers.length > 0) {
                const headerRow = Array.from(headers).map(th => `"${th.textContent.trim()}"`).join(',');
                csv += headerRow + '\n';
            }
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td');
                const csvRow = Array.from(cols).map(col => {
                    let text = col.textContent.trim();
                    text = text.replace(/"/g, '""');
                    return `"${text}"`;
                });
                csv += csvRow.join(',') + '\n';
            });
            
            csv += '\n';
        }
    });
    
    downloadCSV(csv, `mavics-resort-complete-report-${new Date().toISOString().split('T')[0]}.csv`);
}

function downloadCSV(csv, filename) {
    try {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } else {
            // Fallback for older browsers
            alert('Download not supported in this browser. Please try a modern browser.');
        }
    } catch (error) {
        console.error('Download error:', error);
        alert('Download failed. Please try again.');
    }
}

function printReport() {
    // Enhanced print function with responsive handling
    showLoading('Preparing for print...');
    
    setTimeout(() => {
        // Hide elements for printing
        const elementsToHide = [
            '.sidebar',
            '.main-header', 
            '.export-section',
            '.chart-actions',
            '.filters-section'
        ];
        
        const hiddenElements = [];
        elementsToHide.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                hiddenElements.push({
                    element: element,
                    originalDisplay: element.style.display
                });
                element.style.display = 'none';
            });
        });
        
        // Adjust main content
        const mainContent = document.querySelector('.main-content');
        const originalMargin = mainContent.style.marginLeft;
        const originalWidth = mainContent.style.width;
        mainContent.style.marginLeft = '0';
        mainContent.style.width = '100%';
        
        // Add print-specific styles
        const printStyle = document.createElement('style');
        printStyle.innerHTML = `
            @media print {
                body { font-size: 12px; }
                .chart-card, .report-table-card { 
                    height: auto !important; 
                    min-height: 300px !important;
                }
                .chart-container { 
                    height: 250px !important; 
                    min-height: 250px !important;
                }
            }
        `;
        document.head.appendChild(printStyle);
        
        // Print
        window.print();
        
        // Restore original styles
        setTimeout(() => {
            hiddenElements.forEach(item => {
                item.element.style.display = item.originalDisplay;
            });
            
            mainContent.style.marginLeft = originalMargin;
            mainContent.style.width = originalWidth;
            
            document.head.removeChild(printStyle);
            hideLoading();
        }, 1000);
    }, 500);
}

function showLoading(message = 'Loading...') {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        const messageElement = overlay.querySelector('p');
        if (messageElement) {
            messageElement.textContent = message;
        }
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Enhanced chart resize handling
function handleChartResize() {
    if (typeof Chart === 'undefined') return;
    
    Object.values(Chart.instances).forEach(chart => {
        if (chart && typeof chart.resize === 'function') {
            chart.resize();
        }
    });
}

// Keyboard shortcuts with responsive considerations
document.addEventListener('keydown', function(e) {
    // Only enable keyboard shortcuts on non-mobile devices
    if (currentBreakpoint === 'xs' || currentBreakpoint === 'sm') return;
    
    // Ctrl/Cmd + P for print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printReport();
    }
    
    // Ctrl/Cmd + E for export
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportFullReport('pdf');
    }
});

// Enhanced error handling
function initializeInteractivity() {
    // Add hover effects only for non-touch devices
    if (!('ontouchstart' in window)) {
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-2px)';
            });
        });
    }
}

// Data validation with better error messaging
function validateReportData(data) {
    if (!data) {
        console.error('Report data is null or undefined');
        return false;
    }
    
    const requiredFields = ['bookingTrends', 'eventPopularity', 'venuePopularity', 'paymentMethods'];
    const missingFields = [];
    
    for (const field of requiredFields) {
        if (!data[field] || !Array.isArray(data[field])) {
            missingFields.push(field);
        }
    }
    
    if (missingFields.length > 0) {
        console.warn(`Missing or invalid data for fields: ${missingFields.join(', ')}`);
        return false;
    }
    
    return true;
}

// Initialize accessibility features
function initializeAccessibility() {
    // Add ARIA labels and roles
    const charts = document.querySelectorAll('canvas');
    charts.forEach((canvas, index) => {
        canvas.setAttribute('role', 'img');
        canvas.setAttribute('aria-label', `Chart ${index + 1}`);
    });
    
    // Add keyboard navigation for tables
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        table.setAttribute('role', 'table');
        const rows = table.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.setAttribute('role', 'row');
            if (index === 0) {
                const headers = row.querySelectorAll('th');
                headers.forEach(header => {
                    header.setAttribute('role', 'columnheader');
                });
            } else {
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    cell.setAttribute('role', 'cell');
                });
            }
        });
    });
}

// Call accessibility initialization
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initializeAccessibility();
    }, 1000);
});