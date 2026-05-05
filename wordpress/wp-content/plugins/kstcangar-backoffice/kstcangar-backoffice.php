<?php
/**
 * Plugin Name: KST Cangar Backoffice
 * Description: Sistem manajemen backoffice KST Cangar — Stok Opname, Booking, dan Keuangan.
 * Version: 1.0.0
 * Author: Kelompok 3 - Universitas Brawijaya
 */

defined('ABSPATH') || exit;

// Konstanta path plugin
define('KSTCANGAR_PATH', plugin_dir_path(__FILE__));
define('KSTCANGAR_URL',  plugin_dir_url(__FILE__));

// Load semua includes
require_once KSTCANGAR_PATH . 'includes/class-database.php';
require_once KSTCANGAR_PATH . 'includes/class-roles.php';
require_once KSTCANGAR_PATH . 'includes/class-helpers.php';

// Load modul stok
require_once KSTCANGAR_PATH . 'modules/stok/class-stok.php';

// ── Aktivasi & Deaktivasi Plugin ──────────────────────────
register_activation_hook(__FILE__, function () {
    KSTCangar_Database::create_tables();
    KSTCangar_Roles::setup();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// ── Inisialisasi Modul ────────────────────────────────────
add_action('init', function () {
    new KSTCangar_Stok();
});

// ── Admin Menu Utama ──────────────────────────────────────
add_action('admin_menu', function () {
    // Menu utama KST Cangar
    add_menu_page(
        'KST Cangar',
        'KST Cangar',
        'kstcangar_access',
        'kstcangar',
        function () {
            echo '<div class="wrap"><h1>Selamat datang di Backoffice KST Cangar</h1>
                  <p>Pilih menu di sebelah kiri untuk mulai.</p></div>';
        },
        'dashicons-store',
        30
    );

    // Submenu Stok Opname
    add_submenu_page(
        'kstcangar',
        'Stok Opname',
        'Stok Opname',
        'kstcangar_stok',
        'kstcangar-stok',
        ['KSTCangar_Stok', 'render_page']
    );
});

// ── Load CSS & JS Admin ───────────────────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    // Hanya load di halaman plugin ini
    if (strpos($hook, 'kstcangar') === false) return;

    wp_enqueue_style(
        'kstcangar-admin',
        KSTCANGAR_URL . 'assets/css/admin.css',
        [],
        '1.0.0'
    );
});
