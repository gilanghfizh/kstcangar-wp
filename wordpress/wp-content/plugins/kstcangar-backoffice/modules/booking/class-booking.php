<?php
defined('ABSPATH') || exit;

class KSTCangar_Booking {

    public function __construct() {
        // Handler form submission dari wp-admin (operator)
        add_action('admin_post_kstcangar_save_booking',     [$this, 'handle_save_booking']);
        add_action('admin_post_kstcangar_validate_booking', [$this, 'handle_validate_booking']);
        add_action('admin_post_kstcangar_delete_booking',   [$this, 'handle_delete_booking']);

        // Handler form submission dari publik (halaman frontend)
        add_action('admin_post_nopriv_kstcangar_public_booking', [$this, 'handle_public_booking']);
        add_action('admin_post_kstcangar_public_booking',        [$this, 'handle_public_booking']);

        // Shortcode untuk form booking publik di halaman WordPress
        // Cara pakai: tambahkan [kstcangar_booking_form] di halaman/post
        add_shortcode('kstcangar_booking_form', [$this, 'render_public_form']);
    }

    // ══════════════════════════════════════════════════════
    // RENDER ADMIN — Router halaman utama
    // ══════════════════════════════════════════════════════

    public static function render_page(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');

        $tab = $_GET['tab'] ?? 'list';

        echo '<div class="wrap">';
        echo '<h1>📅 Manajemen Booking</h1>';

        $tabs = [
            'list'    => 'Daftar Booking',
            'form'    => 'Tambah Booking',
            'jadwal'  => 'Jadwal & Ketersediaan',
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? 'nav-tab-active' : '';
            $url = add_query_arg(['page' => 'kstcangar-booking', 'tab' => $key], admin_url('admin.php'));
            echo "<a href='" . esc_url($url) . "' class='nav-tab $active'>$label</a>";
        }
        echo '</nav><div style="margin-top:20px;">';

        match ($tab) {
            'form'   => self::render_form(),
            'jadwal' => self::render_jadwal(),
            default  => self::render_list(),
        };

        echo '</div></div>';
    }

    // ══════════════════════════════════════════════════════
    // TAB: DAFTAR BOOKING
    // ══════════════════════════════════════════════════════

    private static function render_list(): void {
        global $wpdb;

        $filter_status  = sanitize_text_field($_GET['filter_status'] ?? '');
        $filter_service = sanitize_text_field($_GET['filter_service'] ?? '');
        $filter_date    = sanitize_text_field($_GET['filter_date'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($filter_status) {
            $where[]  = 'b.status = %s';
            $params[] = $filter_status;
        }
        if ($filter_service) {
            $where[]  = 'b.service_type = %s';
            $params[] = $filter_service;
        }
        if ($filter_date) {
            $where[]  = 'b.date = %s';
            $params[] = $filter_date;
        }

        $where_sql = implode(' AND ', $where);
        $query = "
            SELECT b.*, u.display_name AS created_by_name
            FROM {$wpdb->prefix}kst_bookings b
            LEFT JOIN {$wpdb->users} u ON b.created_by = u.ID
            WHERE $where_sql
            ORDER BY b.date DESC, b.created_at DESC
        ";

        $bookings = $params
            ? $wpdb->get_results($wpdb->prepare($query, ...$params))
            : $wpdb->get_results($query);

        require KSTCANGAR_PATH . 'modules/booking/views/list.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: FORM TAMBAH / EDIT BOOKING (OPERATOR)
    // ══════════════════════════════════════════════════════

    private static function render_form(): void {
        global $wpdb;

        $booking = null;
        if (!empty($_GET['booking_id'])) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}kst_bookings WHERE booking_id = %d",
                (int)$_GET['booking_id']
            ));
        }

        require KSTCANGAR_PATH . 'modules/booking/views/form-booking.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: JADWAL & KETERSEDIAAN
    // ══════════════════════════════════════════════════════

    private static function render_jadwal(): void {
        global $wpdb;

        $month = sanitize_text_field($_GET['filter_month'] ?? date('Y-m'));

        $jadwal = $wpdb->get_results($wpdb->prepare("
            SELECT
                date,
                service_type,
                COUNT(*) AS total_booking,
                SUM(CASE WHEN status = 'confirmed' THEN quantity ELSE 0 END) AS confirmed_qty,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
            FROM {$wpdb->prefix}kst_bookings
            WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
            AND status != 'cancelled'
            GROUP BY date, service_type
            ORDER BY date ASC
        ", $month));

        require KSTCANGAR_PATH . 'modules/booking/views/jadwal.php';
    }

    // ══════════════════════════════════════════════════════
    // SHORTCODE — Form booking publik
    // ══════════════════════════════════════════════════════

    public function render_public_form(): string {
        // Cek apakah baru saja submit
        $success = isset($_GET['booking']) && $_GET['booking'] === 'success';
        $error   = isset($_GET['booking']) && $_GET['booking'] === 'error';

        ob_start();
        require KSTCANGAR_PATH . 'modules/booking/views/form-public.php';
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════
    // HANDLERS
    // ══════════════════════════════════════════════════════

    /** Simpan booking dari operator (wp-admin) */
    public function handle_save_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');

        if (!KSTCangar_Helpers::verify_nonce('save_booking')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $booking_id = (int)($_POST['booking_id'] ?? 0);

        $data = [
            'service_type'   => sanitize_text_field($_POST['service_type'] ?? ''),
            'customer_name'  => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'date'           => sanitize_text_field($_POST['date'] ?? ''),
            'quantity'       => max(1, (int)($_POST['quantity'] ?? 1)),
            'notes'          => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status'         => sanitize_text_field($_POST['status'] ?? 'pending'),
        ];

        // Validasi field wajib
        if (empty($data['customer_name']) || empty($data['date']) || empty($data['service_type'])) {
            KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'form', 'error' => 'required_fields']);
        }

        // Validasi service_type
        if (!in_array($data['service_type'], ['glamping', 'cafe', 'camping'])) {
            KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'form', 'error' => 'invalid_service']);
        }

        if ($booking_id > 0) {
            $wpdb->update($wpdb->prefix . 'kst_bookings', $data, ['booking_id' => $booking_id]);
        } else {
            $data['created_by'] = get_current_user_id();
            $wpdb->insert($wpdb->prefix . 'kst_bookings', $data);
        }

        KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'list', 'success' => '1']);
    }

    /** Validasi / tolak / batalkan booking — Admin KST */
    public function handle_validate_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_validate');

        if (!KSTCangar_Helpers::verify_nonce('validate_booking')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        if (!in_array($new_status, ['confirmed', 'cancelled'])) {
            wp_die('Invalid status.');
        }

        $wpdb->update(
            $wpdb->prefix . 'kst_bookings',
            [
                'status'       => $new_status,
                'validated_by' => get_current_user_id(),
                'validated_at' => current_time('mysql'),
            ],
            ['booking_id' => $booking_id]
        );

        KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'list', 'success' => '1']);
    }

    /** Hapus booking permanen */
    public function handle_delete_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');

        if (!KSTCangar_Helpers::verify_nonce('delete_booking')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;
        $booking_id = (int)($_POST['booking_id'] ?? 0);

        if ($booking_id > 0) {
            $wpdb->delete($wpdb->prefix . 'kst_bookings', ['booking_id' => $booking_id]);
        }

        KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'list', 'success' => '1']);
    }

    /** Handle booking dari publik (shortcode form) */
    public function handle_public_booking(): void {
        // Verifikasi nonce — wajib meski dari publik
        if (!isset($_POST['kstcangar_nonce']) ||
            !wp_verify_nonce($_POST['kstcangar_nonce'], 'kstcangar_public_booking')) {
            wp_die('Invalid request.');
        }

        global $wpdb;

        $data = [
            'service_type'   => sanitize_text_field($_POST['service_type'] ?? ''),
            'customer_name'  => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'date'           => sanitize_text_field($_POST['date'] ?? ''),
            'quantity'       => max(1, (int)($_POST['quantity'] ?? 1)),
            'notes'          => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status'         => 'pending', // booking publik selalu mulai dari pending
            'created_by'     => get_current_user_id() ?: 0, // 0 jika tidak login
        ];

        if (empty($data['customer_name']) || empty($data['date']) || empty($data['service_type'])) {
            $redirect = add_query_arg('booking', 'error', wp_get_referer());
            wp_redirect($redirect);
            exit;
        }

        // Cek ketersediaan slot di tanggal tersebut
        if (!self::is_available($data['service_type'], $data['date'])) {
            $redirect = add_query_arg('booking', 'full', wp_get_referer());
            wp_redirect($redirect);
            exit;
        }

        $wpdb->insert($wpdb->prefix . 'kst_bookings', $data);

        // Redirect ke halaman yang sama dengan pesan sukses
        $redirect = add_query_arg('booking', 'success', wp_get_referer());
        wp_redirect($redirect);
        exit;
    }

    // ══════════════════════════════════════════════════════
    // HELPERS STATIS
    // ══════════════════════════════════════════════════════

    /**
     * Cek ketersediaan slot layanan di tanggal tertentu.
     * Kapasitas default per layanan per hari.
     */
    public static function is_available(string $service, string $date): bool {
        global $wpdb;

        // Kapasitas maksimal per layanan per hari
        $kapasitas = [
            'glamping' => 10,
            'cafe'     => 50,
            'camping'  => 20,
        ];

        $max = $kapasitas[$service] ?? 10;

        $booked = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM {$wpdb->prefix}kst_bookings
            WHERE service_type = %s
            AND date = %s
            AND status IN ('pending', 'confirmed')
        ", $service, $date));

        return $booked < $max;
    }

    /**
     * Ambil label layanan yang readable.
     */
    public static function service_label(string $service): string {
        return match($service) {
            'glamping' => '🏕️ Glamping',
            'cafe'     => '☕ Café Eduwisata',
            'camping'  => '⛺ Camping Ground',
            default    => $service,
        };
    }

    /**
     * Ambil statistik booking untuk dashboard.
     */
    public static function get_stats(): array {
        global $wpdb;

        return [
            'total_today'    => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE date = %s AND status != 'cancelled'",
                date('Y-m-d')
            )),
            'pending'        => (int)$wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE status = 'pending'"
            ),
            'confirmed_month' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE DATE_FORMAT(date,'%%Y-%%m') = %s AND status = 'confirmed'",
                date('Y-m')
            )),
        ];
    }
}
