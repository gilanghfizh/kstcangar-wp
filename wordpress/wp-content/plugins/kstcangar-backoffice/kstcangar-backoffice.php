<?php
/**
 * Plugin Name: KST Cangar Backoffice
 * Description: Sistem manajemen backoffice KST Cangar — Stok Opname, Booking, dan Keuangan.
 * Version: 1.1.0
 * Author: Kelompok 3 - Universitas Brawijaya
 */

defined('ABSPATH') || exit;

define('KSTCANGAR_PATH', plugin_dir_path(__FILE__));
define('KSTCANGAR_URL',  plugin_dir_url(__FILE__));

// Includes
require_once KSTCANGAR_PATH . 'includes/class-database.php';
require_once KSTCANGAR_PATH . 'includes/class-roles.php';
require_once KSTCANGAR_PATH . 'includes/class-helpers.php';

// Modul
require_once KSTCANGAR_PATH . 'modules/stok/class-stok.php';
require_once KSTCANGAR_PATH . 'modules/booking/class-booking.php';

// ── Aktivasi & Deaktivasi ─────────────────────────────────
register_activation_hook(__FILE__, function () {
    KSTCangar_Database::create_tables();
    KSTCangar_Roles::setup();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// ── Init Modul ────────────────────────────────────────────
add_action('init', function () {
    new KSTCangar_Stok();
    new KSTCangar_Booking();
});

// ── Admin Menu ────────────────────────────────────────────
add_action('admin_menu', function () {

    add_menu_page(
        'KST Cangar', 'KST Cangar', 'kstcangar_access',
        'kstcangar',
        function () {
            echo '<div class="wrap"><h1>Selamat datang di Backoffice KST Cangar</h1>
                  <p>Pilih menu di sebelah kiri untuk mulai.</p></div>';
        },
        'dashicons-store', 30
    );

    // Stok Opname
    add_submenu_page(
        'kstcangar', 'Stok Opname', 'Stok Opname',
        'kstcangar_stok', 'kstcangar-stok',
        ['KSTCangar_Stok', 'render_page']
    );

    // Booking
    add_submenu_page(
        'kstcangar', 'Manajemen Booking', 'Booking',
        'kstcangar_booking', 'kstcangar-booking',
        ['KSTCangar_Booking', 'render_page']
    );

    // Keuangan — akan ditambahkan di iterasi berikutnya
    // add_submenu_page('kstcangar', 'Keuangan', 'Keuangan', 'kstcangar_keuangan', 'kstcangar-keuangan', ['KSTCangar_Keuangan', 'render_page']);
});

// ── Assets ────────────────────────────────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'kstcangar') === false) return;
    wp_enqueue_style('kstcangar-admin', KSTCANGAR_URL . 'assets/css/admin.css', [], '1.1.0');
});
