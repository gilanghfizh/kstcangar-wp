<?php
/**
 * Plugin Name: KST Cangar Backoffice
 * Description: Sistem manajemen backoffice KST Cangar — Stok Opname, Booking, Keuangan, dan REST API dengan JWT.
 * Version: 1.4.0
 * Author: Kelompok 3 - Universitas Brawijaya
 */

defined('ABSPATH') || exit;

define('KSTCANGAR_PATH', plugin_dir_path(__FILE__));
define('KSTCANGAR_URL',  plugin_dir_url(__FILE__));

require_once KSTCANGAR_PATH . 'includes/class-database.php';
require_once KSTCANGAR_PATH . 'includes/class-roles.php';
require_once KSTCANGAR_PATH . 'includes/class-helpers.php';
require_once KSTCANGAR_PATH . 'includes/class-jwt.php';
require_once KSTCANGAR_PATH . 'includes/class-api.php';

require_once KSTCANGAR_PATH . 'modules/stok/class-stok.php';
require_once KSTCANGAR_PATH . 'modules/booking/class-booking.php';
require_once KSTCANGAR_PATH . 'modules/keuangan/class-keuangan.php';

register_activation_hook(__FILE__, function () {
    KSTCangar_Database::create_tables();
    KSTCangar_Roles::setup();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('init', function () {
    new KSTCangar_Stok();
    new KSTCangar_Booking();
    new KSTCangar_Keuangan();
});

KSTCangar_API::register();

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if ($result !== null) return $result;

    $override = $request->get_header('x_http_method_override');
    if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'])) {
        $request->set_method(strtoupper($override));
    }

    return $result;
}, 10, 3);

add_action('init', function () {
    $flushed = get_option('kstcangar_routes_flushed', '0');
    if ($flushed !== '1.4.1') {
        flush_rewrite_rules();
        update_option('kstcangar_routes_flushed', '1.4.1');
    }
}, 99);

add_action('admin_menu', function () {

    add_menu_page(
        'KST Cangar', 'KST Cangar', 'kstcangar_access',
        'kstcangar',
        function () {
            $booking_stats  = KSTCangar_Booking::get_stats();
            $keuangan_stats = KSTCangar_Keuangan::get_stats();
            echo '<div class="wrap">';
            echo '<h1>🌿 Backoffice KST Cangar</h1>';
            echo '<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:20px;">';
            echo '<div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px;">
                    <h3 style="margin-top:0;">📅 Booking</h3>
                    <p>Pending: <strong>' . $booking_stats['pending'] . '</strong></p>
                    <p>Hari ini: <strong>' . $booking_stats['total_today'] . '</strong></p>
                    <p>Confirmed bulan ini: <strong>' . $booking_stats['confirmed_month'] . '</strong></p>
                    <a href="' . admin_url('admin.php?page=kstcangar-booking') . '" class="button">Kelola Booking</a>
                  </div>';
            echo '<div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px;">
                    <h3 style="margin-top:0;">💰 Keuangan</h3>
                    <p>Pemasukan hari ini: <strong style="color:#5cb85c;">' . KSTCangar_Helpers::rupiah($keuangan_stats['income_today']) . '</strong></p>
                    <p>Pengeluaran hari ini: <strong style="color:#d9534f;">' . KSTCangar_Helpers::rupiah($keuangan_stats['expense_today']) . '</strong></p>
                    <p>Menunggu validasi: <strong>' . $keuangan_stats['pending_count'] . '</strong></p>
                    <a href="' . admin_url('admin.php?page=kstcangar-keuangan') . '" class="button">Kelola Keuangan</a>
                  </div>';
            echo '<div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px;">
                    <h3 style="margin-top:0;">📦 Stok Opname</h3>
                    <p>Kelola stok harian café dan laporan mingguan.</p>
                    <a href="' . admin_url('admin.php?page=kstcangar-stok') . '" class="button">Kelola Stok</a>
                  </div>';
            echo '</div></div>';
        },
        'dashicons-store', 30
    );

    add_submenu_page('kstcangar', 'Stok Opname', '📦 Stok',
        'kstcangar_stok', 'kstcangar-stok', ['KSTCangar_Stok', 'render_page']);

    add_submenu_page('kstcangar', 'Manajemen Booking', '📅 Booking',
        'kstcangar_booking', 'kstcangar-booking', ['KSTCangar_Booking', 'render_page']);

    add_submenu_page('kstcangar', 'Keuangan', '💰 Keuangan',
        'kstcangar_keuangan', 'kstcangar-keuangan', ['KSTCangar_Keuangan', 'render_page']);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'kstcangar') === false) return;
    wp_enqueue_style('kstcangar-admin', KSTCANGAR_URL . 'assets/css/admin.css', [], '1.4.0');
});
