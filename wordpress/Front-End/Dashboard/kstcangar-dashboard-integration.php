<?php
/**
 * KST Cangar Dashboard Integration
 * 
 * File ini berisi integrasi dashboard frontend dengan WordPress backend.
 * Copy code ini ke file main plugin Anda (wp-content/plugins/kstcangar-backoffice/)
 * 
 * @package KST_Cangar
 * @version 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// 1. REGISTER CUSTOM ADMIN PAGE
// ============================================

add_action('admin_menu', 'kstcangar_dashboard_add_admin_menu');
function kstcangar_dashboard_add_admin_menu() {
    add_menu_page(
        'KST Cangar Dashboard',              // Page title
        'Dashboard',                         // Menu title
        'manage_options',                    // Required capability
        'kstcangar-dashboard',               // Menu slug
        'kstcangar_dashboard_page_content',  // Callback function
        'dashicons-chart-bar',               // Menu icon
        2                                    // Position
    );
}

// ============================================
// 2. DISPLAY DASHBOARD PAGE
// ============================================

function kstcangar_dashboard_page_content() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    // Get dashboard file path
    $dashboard_file = dirname(dirname(__FILE__)) . '/Front-End/dashboard.html';
    
    // Check if file exists
    if (!file_exists($dashboard_file)) {
        echo '<div class="notice notice-error"><p>';
        echo 'Error: Dashboard file tidak ditemukan di ' . esc_html($dashboard_file);
        echo '</p></div>';
        return;
    }
    
    // Include dashboard HTML
    include $dashboard_file;
}

// ============================================
// 3. ENQUEUE ASSETS (CSS & JS)
// ============================================

add_action('admin_enqueue_scripts', 'kstcangar_dashboard_enqueue_assets');
function kstcangar_dashboard_enqueue_assets($hook_suffix) {
    // Only enqueue on our dashboard page
    if ('toplevel_page_kstcangar-dashboard' !== $hook_suffix) {
        return;
    }
    
    $plugin_url = dirname(dirname(__FILE__)) . '/Front-End';
    $plugin_uri = plugins_url('Front-End', dirname(__FILE__));
    
    // Enqueue Dashboard CSS
    wp_enqueue_style(
        'kstcangar-dashboard-css',
        $plugin_uri . '/css/dashboard.css',
        [],
        '1.0.0'
    );
    
    // Enqueue Chart.js library
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
        [],
        '3.9.1',
        false
    );
    
    // Enqueue Dashboard JS
    wp_enqueue_script(
        'kstcangar-dashboard-js',
        $plugin_uri . '/js/dashboard.js',
        ['chartjs'],
        '1.0.0',
        true
    );
    
    // Localize script - Pass data to JavaScript
    wp_localize_script('kstcangar-dashboard-js', 'kstcangarData', [
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'restUrl'     => rest_url('kstcangar/v1/'),
        'nonce'       => wp_create_nonce('kstcangar_dashboard_nonce'),
        'userCapabilities' => [
            'canManageOptions' => current_user_can('manage_options'),
        ]
    ]);
}

// ============================================
// 4. REGISTER REST API ENDPOINTS
// ============================================

add_action('rest_api_init', 'kstcangar_dashboard_register_rest_routes');
function kstcangar_dashboard_register_rest_routes() {
    
    // Dashboard Overview Data
    register_rest_route('kstcangar/v1', '/dashboard', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_dashboard_get_overview_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    // Stock Chart Data
    register_rest_route('kstcangar/v1', '/dashboard/stock-chart', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_dashboard_get_stock_chart_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    // Trend Chart Data
    register_rest_route('kstcangar/v1', '/dashboard/trend-chart', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_dashboard_get_trend_chart_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    // Activities Data
    register_rest_route('kstcangar/v1', '/dashboard/activities', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_dashboard_get_activities',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    // Bookings Data
    register_rest_route('kstcangar/v1', '/dashboard/bookings', [
        'methods'  => 'GET',
        'callback' => 'kstcangar_dashboard_get_bookings',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
}

// ============================================
// 5. API CALLBACK FUNCTIONS
// ============================================

/**
 * Get Dashboard Overview Data
 * Endpoint: /wp-json/kstcangar/v1/dashboard
 */
function kstcangar_dashboard_get_overview_data($request) {
    global $wpdb;
    
    // Table names (adjust according to your DB structure)
    $stok_table = $wpdb->prefix . 'kstcangar_stok';
    $booking_table = $wpdb->prefix . 'kstcangar_booking';
    
    // Get KPI data
    $item_baru = $wpdb->get_var("
        SELECT COUNT(*) FROM $stok_table 
        WHERE DATE(created_at) = CURDATE()
    ");
    
    $stok_tersedia = $wpdb->get_var("
        SELECT COALESCE(SUM(quantity), 0) FROM $stok_table 
        WHERE status = 'tersedia'
    ");
    
    $total_booking = $wpdb->get_var("
        SELECT COUNT(*) FROM $booking_table 
        WHERE status = 'active'
    ");
    
    $pendapatan = $wpdb->get_var("
        SELECT COALESCE(SUM(total_price), 0) FROM $booking_table 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    
    return rest_ensure_response([
        'success' => true,
        'data' => [
            'kpi' => [
                'itemBaru' => intval($item_baru),
                'stokTersedia' => intval($stok_tersedia),
                'totalBooking' => intval($total_booking),
                'pendapatan' => kstcangar_format_currency($pendapatan)
            ],
            'timestamp' => current_time('mysql')
        ]
    ]);
}

/**
 * Get Stock Chart Data
 * Endpoint: /wp-json/kstcangar/v1/dashboard/stock-chart
 */
function kstcangar_dashboard_get_stock_chart_data($request) {
    global $wpdb;
    
    $stok_table = $wpdb->prefix . 'kstcangar_stok';
    
    // Get stock data grouped by item name
    $stock_data = $wpdb->get_results("
        SELECT 
            item_name,
            SUM(quantity) as total_quantity
        FROM $stok_table
        WHERE status = 'tersedia'
        GROUP BY item_name
        LIMIT 10
    ");
    
    // Format data for Chart.js
    $labels = [];
    $data = [];
    
    foreach ($stock_data as $item) {
        $labels[] = $item->item_name;
        $data[] = intval($item->total_quantity);
    }
    
    return rest_ensure_response([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Stok (Unit)',
                    'data' => $data,
                    'backgroundColor' => '#28a745',
                ]
            ]
        ]
    ]);
}

/**
 * Get Trend Chart Data
 * Endpoint: /wp-json/kstcangar/v1/dashboard/trend-chart
 */
function kstcangar_dashboard_get_trend_chart_data($request) {
    global $wpdb;
    
    $booking_table = $wpdb->prefix . 'kstcangar_booking';
    
    // Get data for last 4 months
    $trend_data = $wpdb->get_results("
        SELECT 
            DATE_FORMAT(created_at, '%b') as month,
            COUNT(*) as booking_count,
            COALESCE(SUM(total_price), 0) as total_revenue
        FROM $booking_table
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY created_at ASC
    ");
    
    // Format data
    $labels = [];
    $booking_data = [];
    $revenue_data = [];
    
    foreach ($trend_data as $item) {
        $labels[] = $item->month;
        $booking_data[] = intval($item->booking_count);
        $revenue_data[] = intval($item->total_revenue);
    }
    
    return rest_ensure_response([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Booking',
                    'data' => $booking_data,
                    'borderColor' => '#17a2b8',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $revenue_data,
                    'borderColor' => '#007bff',
                    'yAxisID' => 'y1',
                ]
            ]
        ]
    ]);
}

/**
 * Get Recent Activities
 * Endpoint: /wp-json/kstcangar/v1/dashboard/activities
 */
function kstcangar_dashboard_get_activities($request) {
    global $wpdb;
    
    $activity_table = $wpdb->prefix . 'kstcangar_activity_log';
    
    // Get recent activities
    $activities = $wpdb->get_results("
        SELECT 
            id,
            item_name as title,
            activity_type as meta,
            quantity as value,
            activity_type as type,
            created_at
        FROM $activity_table
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    // Format activities
    $formatted = [];
    foreach ($activities as $activity) {
        $formatted[] = [
            'id' => intval($activity->id),
            'title' => $activity->title,
            'meta' => $activity->meta,
            'time' => kstcangar_get_time_ago($activity->created_at),
            'value' => ($activity->value > 0 ? '+' : '') . $activity->value . ' Unit',
            'type' => kstcangar_get_activity_color($activity->type)
        ];
    }
    
    return rest_ensure_response([
        'success' => true,
        'data' => $formatted
    ]);
}

/**
 * Get Recent Bookings
 * Endpoint: /wp-json/kstcangar/v1/dashboard/bookings
 */
function kstcangar_dashboard_get_bookings($request) {
    global $wpdb;
    
    $booking_table = $wpdb->prefix . 'kstcangar_booking';
    $user_table = $wpdb->prefix . 'users';
    
    // Get recent bookings
    $bookings = $wpdb->get_results("
        SELECT 
            b.id,
            u.display_name as customer_name,
            b.location,
            DATE_FORMAT(b.check_in, '%d') as check_in_day,
            DATE_FORMAT(b.check_out, '%d') as check_out_day,
            DATE_FORMAT(b.check_in, '%b %Y') as month_year,
            b.status,
            b.payment_status
        FROM $booking_table b
        LEFT JOIN $user_table u ON b.user_id = u.ID
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    
    // Format bookings
    $formatted = [];
    foreach ($bookings as $booking) {
        $formatted[] = [
            'id' => intval($booking->id),
            'name' => $booking->customer_name,
            'initials' => strtoupper(substr($booking->customer_name, 0, 2)),
            'location' => $booking->location,
            'date' => $booking->check_in_day . '-' . $booking->check_out_day . ' ' . $booking->month_year,
            'status' => kstcangar_get_booking_status($booking->payment_status)
        ];
    }
    
    return rest_ensure_response([
        'success' => true,
        'data' => $formatted
    ]);
}

// ============================================
// 6. HELPER FUNCTIONS
// ============================================

/**
 * Format currency to Rupiah format
 */
function kstcangar_format_currency($amount) {
    $amount = intval($amount);
    
    if ($amount >= 1000000) {
        return 'Rp ' . round($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return 'Rp ' . round($amount / 1000, 1) . 'K';
    }
    
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Get human-readable time ago
 */
function kstcangar_get_time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' hari lalu';
    }
    
    return date('d M Y', $time);
}

/**
 * Get activity type color
 */
function kstcangar_get_activity_color($type) {
    switch (strtolower($type)) {
        case 'in':
        case 'masuk':
        case 'tambah':
            return 'green';
        case 'out':
        case 'keluar':
        case 'kurang':
            return 'orange';
        case 'adjustment':
        case 'penyesuaian':
            return 'teal';
        case 'reject':
        case 'ditolak':
        case 'error':
            return 'red';
        default:
            return 'blue';
    }
}

/**
 * Get booking payment status badge
 */
function kstcangar_get_booking_status($status) {
    switch (strtolower($status)) {
        case 'paid':
        case 'lunas':
            return 'lunas';
        case 'partial':
        case 'dp':
            return 'dp';
        case 'pending':
        case 'unpaid':
        default:
            return 'pending';
    }
}

// ============================================
// 7. CREATE TABLES (On Plugin Activation)
// ============================================

register_activation_hook(__FILE__, 'kstcangar_create_dashboard_tables');
function kstcangar_create_dashboard_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Activity Log Table
    $activity_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kstcangar_activity_log (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        item_name VARCHAR(255) NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        quantity INT(11) NOT NULL,
        notes LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($activity_table);
}

?>
