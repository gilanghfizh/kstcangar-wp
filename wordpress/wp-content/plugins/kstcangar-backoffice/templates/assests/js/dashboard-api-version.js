// ========================================
// KST Cangar Dashboard - API Version
// ========================================
// File ini adalah versi yang ter-update dari dashboard.js yang bisa fetch data dari API
// Copy contents ini ke dashboard.js untuk mengaktifkan integrasi API

// ========== GLOBAL VARIABLES ==========
let stockChartInstance = null;
let trendChartInstance = null;

// ========== FETCH DASHBOARD DATA ==========

/**
 * Main function to fetch all dashboard data from API
 */
async function fetchAllDashboardData() {
    try {
        console.log('[v0] Fetching dashboard data...');
        
        // Get base URL
        const baseUrl = kstcangarData?.restUrl || '/wp-json/kstcangar/v1/';
        
        // Fetch all data in parallel
        const [overview, stockChart, trendChart, activities, bookings] = await Promise.all([
            fetch(baseUrl + 'dashboard').then(r => r.json()),
            fetch(baseUrl + 'dashboard/stock-chart').then(r => r.json()),
            fetch(baseUrl + 'dashboard/trend-chart').then(r => r.json()),
            fetch(baseUrl + 'dashboard/activities').then(r => r.json()),
            fetch(baseUrl + 'dashboard/bookings').then(r => r.json())
        ]);
        
        // Update UI with fetched data
        if (overview.success && overview.data?.kpi) {
            updateKPICards(overview.data.kpi);
        }
        
        if (stockChart.success && stockChart.data) {
            updateStockChart(stockChart.data);
        }
        
        if (trendChart.success && trendChart.data) {
            updateTrendChart(trendChart.data);
        }
        
        if (activities.success && activities.data) {
            updateActivityList(activities.data);
        }
        
        if (bookings.success && bookings.data) {
            updateBookingList(bookings.data);
        }
        
        console.log('[v0] Dashboard data updated successfully');
        
    } catch (error) {
        console.error('[v0] Error fetching dashboard data:', error);
        showErrorNotification('Gagal memuat data dashboard');
    }
}

// ========== UPDATE KPI CARDS ==========

function updateKPICards(kpiData) {
    try {
        const cards = document.querySelectorAll('.kpi-card');
        
        if (cards[0]) {
            cards[0].querySelector('.card-value').textContent = kpiData.itemBaru || '0';
        }
        
        if (cards[1]) {
            cards[1].querySelector('.card-value').textContent = 
                kpiData.stokTersedia?.toLocaleString('id-ID') || '0';
        }
        
        if (cards[2]) {
            cards[2].querySelector('.card-value').textContent = kpiData.totalBooking || '0';
        }
        
        if (cards[3]) {
            cards[3].querySelector('.card-value').textContent = kpiData.pendapatan || 'Rp 0';
        }
        
        console.log('[v0] KPI cards updated');
    } catch (error) {
        console.error('[v0] Error updating KPI cards:', error);
    }
}

// ========== UPDATE CHARTS ==========

/**
 * Update Stock Chart (Bar Chart)
 */
function updateStockChart(chartData) {
    try {
        const ctx = document.getElementById('stockChart');
        if (!ctx) {
            console.warn('[v0] Stock chart canvas not found');
            return;
        }
        
        // Destroy existing chart if it exists
        if (stockChartInstance) {
            stockChartInstance.destroy();
        }
        
        // Create new chart
        stockChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets: chartData.datasets || [{
                    label: 'Stok (Unit)',
                    data: [],
                    backgroundColor: '#28a745',
                    borderRadius: 8,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 100,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
        
        console.log('[v0] Stock chart updated');
    } catch (error) {
        console.error('[v0] Error updating stock chart:', error);
    }
}

/**
 * Update Trend Chart (Line Chart with dual Y-axis)
 */
function updateTrendChart(chartData) {
    try {
        const ctx = document.getElementById('trendChart');
        if (!ctx) {
            console.warn('[v0] Trend chart canvas not found');
            return;
        }
        
        // Destroy existing chart if it exists
        if (trendChartInstance) {
            trendChartInstance.destroy();
        }
        
        // Create new chart with proper datasets
        trendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels || [],
                datasets: (chartData.datasets || []).map(dataset => ({
                    ...dataset,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBorderWidth: 2,
                    pointBorderColor: '#fff',
                    pointHoverRadius: 7,
                    borderWidth: 2.5
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            usePointStyle: true,
                            padding: 15,
                            boxWidth: 6
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#999'
                        },
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#999',
                            callback: function(value) {
                                return (value / 1000000).toFixed(0) + 'M';
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
        
        console.log('[v0] Trend chart updated');
    } catch (error) {
        console.error('[v0] Error updating trend chart:', error);
    }
}

// ========== UPDATE ACTIVITY LIST ==========

function updateActivityList(activities) {
    try {
        // Find the first activity section
        const activityCards = document.querySelectorAll('.activity-card');
        if (!activityCards[0]) {
            console.warn('[v0] Activity card not found');
            return;
        }
        
        const activityList = activityCards[0].querySelector('.activity-list');
        if (!activityList) {
            console.warn('[v0] Activity list container not found');
            return;
        }
        
        // Clear existing items
        activityList.innerHTML = '';
        
        // Add new items
        activities.forEach(activity => {
            const itemHTML = `
                <div class="activity-item">
                    <div class="activity-dot ${activity.type}"></div>
                    <div class="activity-content">
                        <p class="activity-title">${escapeHtml(activity.title)}</p>
                        <p class="activity-meta">${escapeHtml(activity.meta)}</p>
                        <p class="activity-time">${escapeHtml(activity.time)}</p>
                    </div>
                    <p class="activity-value ${activity.type}">${escapeHtml(activity.value)}</p>
                </div>
            `;
            
            activityList.insertAdjacentHTML('beforeend', itemHTML);
        });
        
        console.log('[v0] Activity list updated with', activities.length, 'items');
    } catch (error) {
        console.error('[v0] Error updating activity list:', error);
    }
}

// ========== UPDATE BOOKING LIST ==========

function updateBookingList(bookings) {
    try {
        // Find the second activity section (bookings)
        const activityCards = document.querySelectorAll('.activity-card');
        if (!activityCards[1]) {
            console.warn('[v0] Booking card not found');
            return;
        }
        
        const bookingList = activityCards[1].querySelector('.activity-list');
        if (!bookingList) {
            console.warn('[v0] Booking list container not found');
            return;
        }
        
        // Clear existing items
        bookingList.innerHTML = '';
        
        // Add new items
        bookings.forEach(booking => {
            const itemHTML = `
                <div class="booking-item">
                    <div class="booking-avatar">${escapeHtml(booking.initials)}</div>
                    <div class="booking-content">
                        <p class="booking-name">${escapeHtml(booking.name)}</p>
                        <p class="booking-meta">${escapeHtml(booking.location)}</p>
                        <p class="booking-date">${escapeHtml(booking.date)}</p>
                    </div>
                    <span class="booking-status status-${booking.status}">${escapeHtml(booking.status === 'lunas' ? 'Lunas' : booking.status === 'dp' ? 'DP' : 'Belum Lunas')}</span>
                </div>
            `;
            
            bookingList.insertAdjacentHTML('beforeend', itemHTML);
        });
        
        console.log('[v0] Booking list updated with', bookings.length, 'items');
    } catch (error) {
        console.error('[v0] Error updating booking list:', error);
    }
}

// ========== UTILITY FUNCTIONS ==========

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show error notification
 */
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-error';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// ========== NAVIGATION HANDLERS ==========

document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

// Home button
const homeBtn = document.querySelector('.nav-btn.btn-home');
if (homeBtn) {
    homeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = '/';
    });
}

// Admin button
const adminBtn = document.querySelector('.nav-btn.btn-admin');
if (adminBtn) {
    adminBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = '/wp-admin';
    });
}

// Logout button
const logoutBtn = document.querySelector('.nav-btn.btn-logout');
if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Apakah Anda yakin ingin logout?')) {
            window.location.href = '/wp-login.php?action=logout';
        }
    });
}

// ========== INITIALIZATION ==========

document.addEventListener('DOMContentLoaded', function() {
    console.log('[v0] Dashboard initialized, fetching data...');
    
    // Check if we have WordPress data
    if (typeof kstcangarData !== 'undefined') {
        console.log('[v0] WordPress data available');
    } else {
        console.warn('[v0] WordPress data not available, using dummy data');
    }
    
    // Fetch dashboard data
    fetchAllDashboardData();
    
    // Refresh data every 30 seconds
    setInterval(fetchAllDashboardData, 30000);
});

console.log('[v0] Dashboard API script loaded');
