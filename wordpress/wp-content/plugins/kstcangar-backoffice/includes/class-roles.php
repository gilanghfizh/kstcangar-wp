<?php
defined('ABSPATH') || exit;

class KSTCangar_Roles {

    /**
     * Dipanggil saat plugin diaktifkan.
     * Menambahkan custom roles ke WordPress.
     */
    public static function setup() {
        // Capabilities dasar yang semua role KST butuhkan
        $base_caps = [
            'read'           => true,
            'kstcangar_access' => true, // akses menu utama KST Cangar
        ];

        // ── Super Admin ───────────────────────────────────
        // Gunakan role Administrator bawaan WordPress
        // Tambahkan semua capabilities KST ke administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('kstcangar_access');
            $admin->add_cap('kstcangar_stok');
            $admin->add_cap('kstcangar_booking');
            $admin->add_cap('kstcangar_keuangan');
            $admin->add_cap('kstcangar_validate'); // validasi data operator
            $admin->add_cap('kstcangar_manage_users');
        }

        // ── Admin KST ─────────────────────────────────────
        // Bisa lihat semua data & validasi, tidak bisa manage users
        add_role('admin_kst', 'Admin KST', array_merge($base_caps, [
            'kstcangar_stok'     => true,
            'kstcangar_booking'  => true,
            'kstcangar_keuangan' => true,
            'kstcangar_validate' => true,
        ]));

        // ── Operator Stok ─────────────────────────────────
        add_role('operator_stok', 'Operator Stok', array_merge($base_caps, [
            'kstcangar_stok' => true,
        ]));

        // ── Operator Booking ──────────────────────────────
        add_role('operator_booking', 'Operator Booking', array_merge($base_caps, [
            'kstcangar_booking' => true,
        ]));

        // ── Operator Keuangan ─────────────────────────────
        add_role('operator_keuangan', 'Operator Keuangan', array_merge($base_caps, [
            'kstcangar_keuangan' => true,
        ]));

        // ── Manajemen ─────────────────────────────────────
        // Hanya bisa lihat dashboard & laporan, tidak bisa input
        add_role('manajemen', 'Manajemen', array_merge($base_caps, [
            'kstcangar_stok'     => true,
            'kstcangar_booking'  => true,
            'kstcangar_keuangan' => true,
        ]));
    }

    /**
     * Cek apakah user saat ini punya capability tertentu.
     * Shorthand untuk dipakai di views.
     */
    public static function can(string $cap): bool {
        return current_user_can($cap);
    }

    /**
     * Redirect dengan pesan error jika tidak punya akses.
     */
    public static function require_cap(string $cap): void {
        if (!current_user_can($cap)) {
            wp_die(
                '<h1>Akses Ditolak</h1><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>',
                'Akses Ditolak',
                ['response' => 403, 'back_link' => true]
            );
        }
    }
}
