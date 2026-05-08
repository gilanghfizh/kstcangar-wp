// ========== Chart Configuration ========== 

// Chart 1: Stok Barang Tersedia (Bar Chart)
const stockCtx = document.getElementById('stockChart');
if (stockCtx) {
    new Chart(stockCtx, {
        type: 'bar',
        data: {
            labels: ['Kentang', 'Strawberry', 'Sayuran', 'Pupuk'],
            datasets: [{
                label: 'Stok (kg)',
                data: [650, 320, 430, 180],
                backgroundColor: [
                    '#28a745',
                    '#28a745',
                    '#28a745',
                    '#28a745'
                ],
                borderRadius: 8,
                borderSkipped: false,
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
                    max: 800,
                    ticks: {
                        stepSize: 200,
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
}

// Chart 2: Tren Booking & Pendapatan (Multi-axis Chart)
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr'],
            datasets: [
                {
                    label: 'Booking',
                    data: [10, 15, 25, 22],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.05)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y',
                    pointBackgroundColor: '#17a2b8',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'Pendapatan (Rp)',
                    data: [10000000, 19000000, 28500000, 25800000],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.05)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1',
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
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
                    title: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        color: '#999'
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    max: 28
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: false
                    },
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
                    },
                    max: 38000000
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
}

// ========== Event Listeners ========== 

// Handle navigation
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        // Remove active class from all items
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        // Add active class to clicked item
        this.classList.add('active');
    });
});

// Handle header navigation buttons
document.querySelector('.btn-home').addEventListener('click', function(e) {
    e.preventDefault();
    // Redirect to home page
    console.log('[v0] Navigating to home');
    // window.location.href = '/';
});

document.querySelector('.btn-admin').addEventListener('click', function(e) {
    e.preventDefault();
    // Redirect to admin panel
    console.log('[v0] Opening admin panel');
    // window.location.href = '/admin';
});

document.querySelector('.btn-logout').addEventListener('click', function(e) {
    e.preventDefault();
    // Handle logout
    if (confirm('Apakah Anda yakin ingin logout?')) {
        console.log('[v0] Logging out');
        // window.location.href = '/logout';
    }
});

// ========== Data Update Functions ========== 

// Function to update KPI cards (can be called from API)
function updateKPICards(data) {
    // Example: updateKPICards({ itemBaru: 1, stokTersedia: 1590, totalBooking: 1, pendapatan: '1.5 Jt' })
    if (data.itemBaru !== undefined) {
        document.querySelector('.kpi-card:nth-child(1) .card-value').textContent = data.itemBaru;
    }
    if (data.stokTersedia !== undefined) {
        document.querySelector('.kpi-card:nth-child(2) .card-value').textContent = data.stokTersedia.toLocaleString('id-ID');
    }
    if (data.totalBooking !== undefined) {
        document.querySelector('.kpi-card:nth-child(3) .card-value').textContent = data.totalBooking;
    }
    if (data.pendapatan !== undefined) {
        document.querySelector('.kpi-card:nth-child(4) .card-value').textContent = data.pendapatan;
    }
}

// Function to update activity list
function updateActivityList(activities) {
    const activityList = document.querySelector('.activity-list');
    if (!activityList || !activities || activities.length === 0) return;

    activityList.innerHTML = '';
    
    activities.forEach(activity => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        
        item.innerHTML = `
            <div class="activity-dot ${activity.type}"></div>
            <div class="activity-content">
                <p class="activity-title">${activity.title}</p>
                <p class="activity-meta">${activity.meta}</p>
                <p class="activity-time">${activity.time}</p>
            </div>
            <p class="activity-value ${activity.type}">${activity.value}</p>
        `;
        
        activityList.appendChild(item);
    });
}

// Function to update booking list
function updateBookingList(bookings) {
    const bookingList = document.querySelector('.activity-list:last-of-type');
    if (!bookingList || !bookings || bookings.length === 0) return;

    bookingList.innerHTML = '';
    
    bookings.forEach(booking => {
        const item = document.createElement('div');
        item.className = 'booking-item';
        
        item.innerHTML = `
            <div class="booking-avatar">${booking.initials}</div>
            <div class="booking-content">
                <p class="booking-name">${booking.name}</p>
                <p class="booking-meta">${booking.location}</p>
                <p class="booking-date">${booking.date}</p>
            </div>
            <span class="booking-status status-${booking.status.toLowerCase()}">${booking.status}</span>
        `;
        
        bookingList.appendChild(item);
    });
}

// ========== API Integration Example ========== 

// Uncomment and modify this to fetch data from your WordPress API
/*
async function fetchDashboardData() {
    try {
        const response = await fetch('/wp-json/api/dashboard');
        const data = await response.json();
        
        // Update KPI cards
        if (data.kpi) {
            updateKPICards(data.kpi);
        }
        
        // Update activity list
        if (data.activities) {
            updateActivityList(data.activities);
        }
        
        // Update booking list
        if (data.bookings) {
            updateBookingList(data.bookings);
        }
        
        console.log('[v0] Dashboard data updated', data);
    } catch (error) {
        console.error('[v0] Error fetching dashboard data:', error);
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', fetchDashboardData);

// Refresh data every 30 seconds
setInterval(fetchDashboardData, 30000);
*/

console.log('[v0] Dashboard script loaded');
