# Dashboard KST Cangar - Integration Guide

Panduan lengkap untuk mengintegrasikan dashboard frontend dengan backend WordPress plugin Anda.

## 📁 Struktur File

```
wordpress/
├── Front-End/
│   ├── dashboard.html          # File HTML utama dashboard
│   ├── css/
│   │   └── dashboard.css       # Styling dashboard
│   ├── js/
│   │   └── dashboard.js        # Logic & Charts
│   ├── images/                 # Folder untuk assets
│   └── INTEGRATION_GUIDE.md    # File ini
└── wp-content/
    └── plugins/
        └── kstcangar-backoffice/  # Backend plugin Anda
            └── api/               # REST API endpoints
```

---

## 🚀 Cara Menggunakan Dashboard

### 1. **Sebagai Custom Admin Page di WordPress**

#### Option A: Menggunakan enqueue_script
Di file plugin Anda (`kstcangar-backoffice/main.php`):

```php
<?php
// Register custom admin page
add_action('admin_menu', 'kstcangar_add_admin_menu');
function kstcangar_add_admin_menu() {
    add_menu_page(
        'Dashboard',                    // Page title
        'Dashboard',                    // Menu title
        'manage_options',               // Capability
        'kstcangar-dashboard',          // Menu slug
        'kstcangar_dashboard_page',     // Function to display page
        'dashicons-chart-bar',          // Icon
        25                              // Position
    );
}

// Display dashboard page
function kstcangar_dashboard_page() {
    // Set dashboard file path
    $dashboard_file = plugin_dir_path(__FILE__) . '../../../Front-End/dashboard.html';
    
    if (file_exists($dashboard_file)) {
        include $dashboard_file;
    } else {
        echo '<div class="error"><p>Dashboard file tidak ditemukan.</p></div>';
    }
}

// Enqueue CSS & JS
add_action('admin_enqueue_scripts', 'kstcangar_enqueue_assets');
function kstcangar_enqueue_assets($hook_suffix) {
    if ($hook_suffix !== 'toplevel_page_kstcangar-dashboard') {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'kstcangar-dashboard-css',
        plugin_dir_url(__FILE__) . '../../../Front-End/css/dashboard.css',
        [],
        '1.0'
    );
    
    // Enqueue Chart.js
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js',
        [],
        '3.9.1'
    );
    
    // Enqueue dashboard JS
    wp_enqueue_script(
        'kstcangar-dashboard-js',
        plugin_dir_url(__FILE__) . '../../../Front-End/js/dashboard.js',
        ['chart-js'],
        '1.0',
        true
    );
    
    // Pass WordPress data to JS
    wp_localize_script('kstcangar-dashboard-js', 'wpData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kstcangar_dashboard_nonce'),
    ]);
}
?>
```

#### Option B: Sebagai Standalone Page di Frontend
Jika ingin sebagai halaman publik, buat file `dashboard-page.php` di theme:

```php
<?php
/*
Template Name: KST Cangar Dashboard
*/
get_header();
include get_stylesheet_directory() . '/path-to-dashboard.html';
get_footer();
?>
```

---

## 🔌 API Integration dengan Backend

### 1. **REST API Endpoints yang Diperlukan**

Dashboard membutuhkan data dari endpoint berikut:

#### `/wp-json/kstcangar/v1/dashboard`
**Method:** GET
**Response:**
```json
{
  "kpi": {
    "itemBaru": 1,
    "stokTersedia": 1590,
    "totalBooking": 1,
    "pendapatan": "Rp 1.5 Jt"
  },
  "stokChart": {
    "labels": ["Kentang", "Strawberry", "Sayuran", "Pupuk"],
    "data": [650, 320, 430, 180]
  },
  "trendChart": {
    "labels": ["Jan", "Feb", "Mar", "Apr"],
    "booking": [10, 15, 25, 22],
    "pendapatan": [10000000, 19000000, 28500000, 25800000]
  },
  "activities": [
    {
      "id": 1,
      "title": "Kentang Granola",
      "meta": "Stok keluar",
      "time": "1 hari lalu",
      "value": "+200 Kg",
      "type": "green"
    }
  ],
  "bookings": [
    {
      "id": 1,
      "name": "Ahmad Rizki",
      "initials": "AZ",
      "location": "Glamping Deluxe",
      "date": "1-3 Mei 2026",
      "status": "lunas"
    }
  ]
}
```

### 2. **Implementasi REST API di Plugin**

Di file plugin Anda:

```php
<?php
// Register REST API route
add_action('rest_api_init', 'kstcangar_register_rest_routes');
function kstcangar_register_rest_routes() {
    register_rest_route('kstcangar/v1', '/dashboard', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_get_dashboard_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
}

// Dashboard data callback
function kstcangar_get_dashboard_data() {
    global $wpdb;
    
    // Query data dari database
    $item_baru = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}kstcangar_stok 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    
    $stok_tersedia = $wpdb->get_var("
        SELECT SUM(quantity) FROM {$wpdb->prefix}kstcangar_stok
    ");
    
    $booking_data = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}kstcangar_booking 
        ORDER BY created_at DESC LIMIT 10
    ");
    
    // Return data
    return rest_ensure_response([
        'kpi' => [
            'itemBaru' => intval($item_baru),
            'stokTersedia' => intval($stok_tersedia),
            'totalBooking' => count($booking_data),
            'pendapatan' => 'Rp 1.5 Jt' // Hitung dari database
        ],
        'stokChart' => [
            'labels' => ['Kentang', 'Strawberry', 'Sayuran', 'Pupuk'],
            'data' => [650, 320, 430, 180]
        ],
        'activities' => kstcangar_get_activities(),
        'bookings' => kstcangar_format_bookings($booking_data)
    ]);
}

// Helper function
function kstcangar_get_activities() {
    global $wpdb;
    $activities = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}kstcangar_activities 
        ORDER BY created_at DESC LIMIT 5
    ");
    
    return array_map(function($activity) {
        return [
            'title' => $activity->title,
            'meta' => $activity->meta,
            'time' => kstcangar_time_ago($activity->created_at),
            'value' => $activity->value,
            'type' => $activity->type
        ];
    }, $activities);
}

function kstcangar_format_bookings($bookings) {
    return array_map(function($booking) {
        return [
            'name' => $booking->customer_name,
            'initials' => substr($booking->customer_name, 0, 2),
            'location' => $booking->location,
            'date' => $booking->date_range,
            'status' => strtolower($booking->status)
        ];
    }, $bookings);
}

function kstcangar_time_ago($time) {
    $time = strtotime($time);
    $diff = time() - $time;
    
    if ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    } else {
        return floor($diff / 86400) . ' hari lalu';
    }
}
?>
```

### 3. **Uncomment di js/dashboard.js**

Untuk mengaktifkan fetch data, uncomment bagian API Integration di `dashboard.js`:

```javascript
// Di file js/dashboard.js, cari section "API Integration Example"
// Uncomment kode berikut:

async function fetchDashboardData() {
    try {
        const response = await fetch(wpData.ajaxUrl + '?action=kstcangar_get_dashboard&_wpnonce=' + wpData.nonce);
        const data = await response.json();
        
        // Update KPI cards
        if (data.kpi) {
            updateKPICards(data.kpi);
        }
        
        // Update charts
        if (data.stokChart) {
            updateStockChart(data.stokChart);
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
```

---

## 🎨 Customization

### 1. **Mengubah Warna Theme**

Edit file `css/dashboard.css`, bagian `:root`:

```css
:root {
    --primary-teal: #009933;        /* Warna hijau KST */
    --primary-green: #28a745;
    --primary-orange: #fd7e14;
    --primary-blue: #007bff;
    /* ... warna lainnya */
}
```

### 2. **Mengubah Data Dummy**

Di `js/dashboard.js`, ubah data di bagian Chart Configuration:

```javascript
// Bar Chart - Stok Barang
data: {
    labels: ['Kentang', 'Strawberry', 'Sayuran', 'Pupuk'],  // Ubah nama
    datasets: [{
        data: [650, 320, 430, 180],  // Ubah nilai
    }]
}
```

### 3. **Menambah/Mengurangi KPI Cards**

Edit `dashboard.html`, cari section `<!-- KPI Cards -->` dan tambahkan/hapus `<div class="kpi-card">`.

---

## 📊 Chart.js Configuration

Dashboard menggunakan **Chart.js 3.9.1** dengan dua tipe chart:

### 1. **Bar Chart** (Stok Barang Tersedia)
- Type: Bar (Histogram)
- Library: Chart.js
- Config: `stockCtx` di js/dashboard.js

### 2. **Line Chart** (Tren Booking & Pendapatan)
- Type: Line dengan dual Y-axis
- Library: Chart.js
- Config: `trendCtx` di js/dashboard.js

---

## 🔐 Security Notes

1. **Nonce Verification** - Selalu verify request dengan WordPress nonce
2. **Capability Check** - Dashboard hanya bisa diakses oleh admin
3. **AJAX Actions** - Semua AJAX request harus diverifikasi
4. **Input Sanitization** - Sanitize semua input dari database

---

## 🐛 Troubleshooting

### Chart tidak muncul
- Pastikan Chart.js library ter-load dengan benar
- Check console browser untuk error messages
- Verify path file CSS & JS sudah benar

### Data tidak update
- Check REST API endpoint respond dengan benar
- Verify nonce token valid
- Check browser console untuk fetch error

### Styling tidak benar
- Verify CSS file ter-load
- Check CSS path di HTML
- Clear browser cache (Ctrl+F5)

---

## 📝 Next Steps

1. ✅ Setup endpoint REST API di plugin
2. ✅ Implementasi database queries
3. ✅ Test fetch data via AJAX
4. ✅ Uncomment API code di dashboard.js
5. ✅ Deploy ke server

---

## 📞 Support

Untuk bantuan atau pertanyaan, silahkan buat issue di repository atau hubungi tim development.
