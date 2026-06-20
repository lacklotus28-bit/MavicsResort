// Enhanced Admin Dashboard with Real Analytics
document.addEventListener('DOMContentLoaded', function() {
    let currentPeriod = 'month';
    let dashboardData = null;
    let revenueChart = null;
    let bookingsChart = null;
    let eventTypesChart = null;
    
    // Initialize dashboard
    init();
    
    function init() {
        setupEventListeners();
        loadDashboardData();
        
        // Refresh data every 5 minutes
        setInterval(loadDashboardData, 300000);
    }
    
    function setupEventListeners() {
        // Period selector buttons
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentPeriod = this.dataset.period;
                loadDashboardData();
            });
        });
        
        // Chart period selector
        const chartPeriod = document.getElementById('chartPeriod');
        if (chartPeriod) {
            chartPeriod.addEventListener('change', function() {
                loadDashboardData();
            });
        }
    }
    
    async function loadDashboardData() {
        try {
            const chartPeriod = document.getElementById('chartPeriod')?.value || 'month';
            const response = await fetch(`api/analytics/get-dashboard-data.php?period=${chartPeriod}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch dashboard data');
            }
            
            dashboardData = await response.json();
            
            if (dashboardData.success) {
                updateMetrics();
                updateCharts();
                updatePopularVenues();
                updateRecentBookings();
                updateUpcomingEvents();
                updateTopCustomers();
            } else {
                showError('Failed to load dashboard data');
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            showError('Error loading dashboard data. Please refresh the page.');
        }
    }
    
    function updateMetrics() {
        const metrics = dashboardData.metrics;
        const statusData = metrics.bookings_by_status || {};
        
        // Total Revenue
        animateNumber('totalRevenue', metrics.total_revenue, true);
        
        // Revenue Growth
        const growth = metrics.revenue_growth || 0;
        const growthEl = document.getElementById('revenueGrowth');
        if (growthEl) {
            growthEl.className = 'stat-change ' + (growth >= 0 ? 'positive' : 'negative');
            growthEl.innerHTML = `
                <i class="fas fa-arrow-${growth >= 0 ? 'up' : 'down'}"></i> 
                ${Math.abs(growth)}% vs last month
            `;
        }
        
        // Total Bookings
        animateNumber('totalBookings', metrics.total_bookings);
        
        // Pending Bookings
        const pending = statusData.pending || 0;
        animateNumber('pendingBookings', pending);
        const pendingStatus = document.getElementById('pendingStatus');
        if (pendingStatus) {
            pendingStatus.innerHTML = pending > 0 
                ? `<i class="fas fa-exclamation-circle"></i> ${pending} need${pending !== 1 ? '' : 's'} attention`
                : '<i class="fas fa-check-circle"></i> All clear';
        }
        
        // Active Customers
        animateNumber('activeCustomers', metrics.active_customers);
        const newCustomers = document.getElementById('newCustomers');
        if (newCustomers) {
            const newCount = metrics.new_customers_month || 0;
            newCustomers.innerHTML = `<i class="fas fa-user-plus"></i> +${newCount} this month`;
        }
        
        // Average Booking Value
        animateNumber('avgBookingValue', metrics.avg_booking_value, true);
        
        // Conversion Rate
        const conversionRate = metrics.conversion_rate || 0;
        const conversionEl = document.getElementById('conversionRate');
        if (conversionEl) {
            conversionEl.textContent = conversionRate.toFixed(1) + '%';
            conversionEl.classList.remove('loading-shimmer');
        }
        
        const conversionStatus = document.getElementById('conversionStatus');
        if (conversionStatus) {
            conversionStatus.className = 'stat-change ' + (conversionRate >= 70 ? 'positive' : conversionRate >= 50 ? 'neutral' : 'negative');
            conversionStatus.innerHTML = conversionRate >= 70 
                ? '<i class="fas fa-thumbs-up"></i> Excellent'
                : conversionRate >= 50 
                ? '<i class="fas fa-info-circle"></i> Good'
                : '<i class="fas fa-exclamation-triangle"></i> Needs improvement';
        }
    }
    
    function animateNumber(elementId, targetValue, isCurrency = false) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        element.classList.remove('loading-shimmer');
        
        const duration = 1000;
        const startValue = 0;
        const startTime = Date.now();
        
        function update() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = startValue + (targetValue - startValue) * easeOutQuad(progress);
            
            if (isCurrency) {
                element.textContent = '₱' + Math.floor(current).toLocaleString();
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        update();
    }
    
    function easeOutQuad(t) {
        return t * (2 - t);
    }
    
    function updateCharts() {
        updateRevenueChart();
        updateBookingsChart();
        updateEventTypesChart();
    }
    
    function updateRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        
        const chartData = dashboardData.charts?.revenue || { labels: [], values: [] };
        
        if (revenueChart) {
            revenueChart.destroy();
        }
        
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: chartData.values,
                    backgroundColor: 'rgba(139, 90, 60, 0.1)',
                    borderColor: '#8B5A3C',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#8B5A3C',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(47, 27, 20, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + (value / 1000) + 'k';
                            },
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function updateBookingsChart() {
        const ctx = document.getElementById('bookingsChart');
        if (!ctx) return;
        
        const chartData = dashboardData.charts?.bookings || { labels: [], values: [] };
        
        if (bookingsChart) {
            bookingsChart.destroy();
        }
        
        bookingsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Bookings',
                    data: chartData.values,
                    backgroundColor: 'rgba(139, 90, 60, 0.8)',
                    borderColor: '#8B5A3C',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(47, 27, 20, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function updateEventTypesChart() {
        const ctx = document.getElementById('eventTypesChart');
        if (!ctx) return;
        
        const eventTypes = dashboardData.event_types || [];
        
        if (eventTypesChart) {
            eventTypesChart.destroy();
        }
        
        const colors = [
            '#8B5A3C',
            '#DEB887',
            '#CD853F',
            '#D2B48C',
            '#8FBC8F',
            '#5F9EA0'
        ];
        
        eventTypesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: eventTypes.map(e => e.event_type),
                datasets: [{
                    data: eventTypes.map(e => e.count),
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            color: '#333',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(47, 27, 20, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    function updatePopularVenues() {
        const container = document.getElementById('popularVenues');
        if (!container) return;
        
        const venues = dashboardData.popular_venues || [];
        
        if (venues.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> No venue data available</div>';
            return;
        }
        
        container.innerHTML = venues.map((venue, index) => `
            <div class="venue-item">
                <div class="venue-rank">#${index + 1}</div>
                <div class="venue-info">
                    <h4>${venue.name}</h4>
                    <p>${venue.booking_count} booking${venue.booking_count !== 1 ? 's' : ''} • ₱${parseFloat(venue.total_revenue).toLocaleString()}</p>
                </div>
                <div class="venue-trend">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        `).join('');
    }
    
    function updateRecentBookings() {
        const container = document.getElementById('recentBookings');
        if (!container) return;
        
        const bookings = dashboardData.recent_bookings || [];
        
        if (bookings.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i> No recent bookings</div>';
            return;
        }
        
        container.innerHTML = bookings.map(booking => `
            <div class="activity-item">
                <div class="activity-avatar">${getInitials(booking.customer_name)}</div>
                <div class="activity-details">
                    <h4>${booking.customer_name}</h4>
                    <p>${booking.event_type} • ${formatDate(booking.booking_date)}</p>
                    <span class="activity-time">${formatTimeAgo(booking.created_at)}</span>
                </div>
                <div class="activity-status">
                    <span class="status-badge status-${booking.status}">${booking.status}</span>
                    <div class="activity-amount">₱${parseFloat(booking.total_amount).toLocaleString()}</div>
                </div>
            </div>
        `).join('');
    }
    
    function updateUpcomingEvents() {
        const container = document.getElementById('upcomingEvents');
        if (!container) return;
        
        const events = dashboardData.upcoming_events || [];
        
        if (events.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i> No upcoming events in the next 7 days</div>';
            return;
        }
        
        container.innerHTML = events.map(event => {
            const eventDate = new Date(event.booking_date);
            return `
                <div class="event-item">
                    <div class="event-date-badge">
                        <span class="date-day">${eventDate.getDate()}</span>
                        <span class="date-month">${eventDate.toLocaleString('default', { month: 'short' })}</span>
                    </div>
                    <div class="event-details">
                        <h4>${event.customer_name}</h4>
                        <p><i class="fas fa-map-marker-alt"></i> ${event.venue_name}</p>
                        <p><i class="fas fa-users"></i> ${event.guest_count} guests • <i class="fas fa-clock"></i> ${event.start_time.substring(0, 5)} - ${event.end_time.substring(0, 5)}</p>
                    </div>
                    <div class="event-badge">
                        <span class="status-badge status-${event.status}">${event.status}</span>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function updateTopCustomers() {
        const tbody = document.getElementById('topCustomersBody');
        if (!tbody) return;
        
        const customers = dashboardData.top_customers || [];
        
        if (customers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No customer data available</td></tr>';
            return;
        }
        
        tbody.innerHTML = customers.map((customer, index) => {
            const avgBooking = customer.booking_count > 0 ? customer.total_spent / customer.booking_count : 0;
            return `
                <tr>
                    <td>
                        <div class="rank-badge rank-${index + 1}">
                            ${index + 1 <= 3 ? '<i class="fas fa-crown"></i>' : index + 1}
                        </div>
                    </td>
                    <td>
                        <div class="customer-cell">
                            <div class="customer-avatar">${getInitials(customer.name)}</div>
                            <span>${customer.name}</span>
                        </div>
                    </td>
                    <td><strong>${customer.booking_count}</strong></td>
                    <td><strong>₱${parseFloat(customer.total_spent).toLocaleString()}</strong></td>
                    <td>₱${avgBooking.toLocaleString(undefined, { maximumFractionDigits: 0 })}</td>
                    <td>
                        <a href="manage-users.php?id=${customer.id}" class="btn-view-customer">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Helper functions
    function getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval !== 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    }
    
    function showError(message) {
        const notification = document.createElement('div');
        notification.className = 'notification error';
        notification.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
});
