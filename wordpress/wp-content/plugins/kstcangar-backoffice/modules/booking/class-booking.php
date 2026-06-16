<?php
defined('ABSPATH') || exit;

class KSTCangar_Booking {

    // Unit glamping: 1-7 Deluxe, 8-10 Long
    const UNIT_DELUXE = ['Unit-1','Unit-2','Unit-3','Unit-4','Unit-5','Unit-6','Unit-7'];
    const UNIT_LONG   = ['Unit-8','Unit-9','Unit-10'];

    public function __construct() {
        add_action('admin_post_kstcangar_save_booking',     [$this, 'handle_save_booking']);
        add_action('admin_post_kstcangar_validate_booking', [$this, 'handle_validate_booking']);
        add_action('admin_post_kstcangar_delete_booking',   [$this, 'handle_delete_booking']);

        add_action('admin_post_nopriv_kstcangar_public_booking', [$this, 'handle_public_booking']);
        add_action('admin_post_kstcangar_public_booking',        [$this, 'handle_public_booking']);

        add_action('wp_ajax_kstcangar_cek_kapasitas', [$this, 'ajax_cek_kapasitas']);
        add_shortcode('kstcangar_booking_form', [$this, 'render_public_form']);
    }

    // ══════════════════════════════════════════════════════
    // RENDER ADMIN
    // ══════════════════════════════════════════════════════

    public static function render_page(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');
        $tab = $_GET['tab'] ?? 'list';

        echo '<div class="wrap"><h1>📅 Manajemen Booking</h1>';
        $tabs = ['list' => 'Daftar Booking', 'form' => 'Tambah Booking', 'jadwal' => 'Jadwal'];

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

    private static function render_list(): void {
        global $wpdb;

        $filter_status  = sanitize_text_field($_GET['filter_status']  ?? '');
        $filter_service = sanitize_text_field($_GET['filter_service'] ?? '');
        $filter_date    = sanitize_text_field($_GET['filter_date']    ?? '');

        $where = ['1=1']; $params = [];
        if ($filter_status)  { $where[] = 'b.status = %s';           $params[] = $filter_status; }
        if ($filter_service) { $where[] = 'b.service_type = %s';     $params[] = $filter_service; }
        if ($filter_date)    { $where[] = 'b.checkin_date = %s';     $params[] = $filter_date; }

        $query = "
            SELECT b.*, u.display_name AS created_by_name
            FROM {$wpdb->prefix}kst_bookings b
            LEFT JOIN {$wpdb->users} u ON b.created_by = u.ID
            WHERE " . implode(' AND ', $where) . "
            ORDER BY b.checkin_date DESC, b.created_at DESC
        ";

        $bookings = $params
            ? $wpdb->get_results($wpdb->prepare($query, ...$params))
            : $wpdb->get_results($query);

        require KSTCANGAR_PATH . 'modules/booking/views/list.php';
    }

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

    private static function render_jadwal(): void {
        global $wpdb;
        $month = sanitize_text_field($_GET['filter_month'] ?? date('Y-m'));
        $jadwal = $wpdb->get_results($wpdb->prepare("
            SELECT checkin_date AS date, service_type,
                   COUNT(*) AS total_booking,
                   SUM(CASE WHEN status = 'confirmed' THEN quantity ELSE 0 END) AS confirmed_qty,
                   SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS pending_count
            FROM {$wpdb->prefix}kst_bookings
            WHERE DATE_FORMAT(checkin_date,'%%Y-%%m') = %s AND status != 'cancelled'
            GROUP BY checkin_date, service_type ORDER BY checkin_date ASC
        ", $month));
        require KSTCANGAR_PATH . 'modules/booking/views/jadwal.php';
    }

    public function render_public_form(): string {
        $success = isset($_GET['booking']) && $_GET['booking'] === 'success';
        $error   = isset($_GET['booking']) && $_GET['booking'] === 'error';
        ob_start();
        require KSTCANGAR_PATH . 'modules/booking/views/form-public.php';
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════
    // AJAX — Cek kapasitas
    // ══════════════════════════════════════════════════════

    public function ajax_cek_kapasitas(): void {
        check_ajax_referer('kstcangar_cek_kapasitas');
        KSTCangar_Roles::require_cap('kstcangar_booking');

        $service    = sanitize_text_field($_GET['service'] ?? '');
        $date       = sanitize_text_field($_GET['date']    ?? '');
        $exclude_id = (int)($_GET['exclude_id'] ?? 0);

        $kapasitas = ['glamping' => 10, 'cafe' => 50, 'camping' => 20];
        $max       = $kapasitas[$service] ?? 10;
        $booked    = self::get_booked_qty($service, $date, $exclude_id);

        wp_send_json_success([
            'max'   => $max,
            'booked'=> $booked,
            'sisa'  => $max - $booked,
            'penuh' => $booked >= $max,
        ]);
    }

    // ══════════════════════════════════════════════════════
    // HANDLERS
    // ══════════════════════════════════════════════════════

    public function handle_save_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');
        if (!KSTCangar_Helpers::verify_nonce('save_booking')) wp_die('Invalid nonce.');

        global $wpdb;

        $booking_id = (int)($_POST['booking_id'] ?? 0);

        // ── Upload bukti pembayaran ───────────────────────
        $bukti_url = sanitize_text_field($_POST['bukti_existing'] ?? '');
        if (!empty($_FILES['bukti_pembayaran']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('bukti_pembayaran', 0);
            if (!is_wp_error($attachment_id)) {
                $bukti_url = wp_get_attachment_url($attachment_id);
            }
        }

        $data = [
            'service_type'    => sanitize_text_field($_POST['service_type']  ?? ''),
            'customer_name'   => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_phone'  => sanitize_text_field($_POST['customer_phone']?? ''),
            'alamat'          => sanitize_textarea_field($_POST['alamat']     ?? ''),
            'checkin_date'    => sanitize_text_field($_POST['checkin_date']   ?? ''),
            'checkout_date'   => sanitize_text_field($_POST['checkout_date']  ?? ''),
            'unit_glamping'   => sanitize_text_field($_POST['unit_glamping']  ?? ''),
            'quantity'        => max(1, (int)($_POST['quantity'] ?? 1)),
            'harga'           => (float)($_POST['harga'] ?? 0),
            'bukti_pembayaran'=> $bukti_url,
            'no_receipt'      => sanitize_text_field($_POST['no_receipt']     ?? ''),
            'no_invoice'      => sanitize_text_field($_POST['no_invoice']     ?? ''),
            'notes'           => sanitize_textarea_field($_POST['notes']      ?? ''),
            'status'          => sanitize_text_field($_POST['status']         ?? 'pending'),
        ];

        // Kosongkan unit_glamping jika bukan glamping
        if ($data['service_type'] !== 'glamping') {
            $data['unit_glamping'] = null;
        }

        // Validasi field wajib
        if (empty($data['customer_name']) || empty($data['checkin_date']) || empty($data['service_type'])) {
            KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'form', 'error' => 'required_fields']);
        }

        // Validasi kapasitas
        $booked    = self::get_booked_qty($data['service_type'], $data['checkin_date'], $booking_id);
        $kapasitas = ['glamping' => 10, 'cafe' => 50, 'camping' => 20];
        $max       = $kapasitas[$data['service_type']] ?? 10;

        if (($booked + $data['quantity']) > $max) {
            KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'form', 'error' => 'kapasitas_penuh']);
        }

        if ($booking_id > 0) {
            // Ambil harga lama untuk update finance jika berubah
            $old = $wpdb->get_row($wpdb->prepare(
                "SELECT harga, service_type, checkin_date FROM {$wpdb->prefix}kst_bookings WHERE booking_id = %d",
                $booking_id
            ));
            $wpdb->update($wpdb->prefix . 'kst_bookings', $data, ['booking_id' => $booking_id]);

            // Update record finance jika harga berubah
            if ($old && (float)$old->harga !== $data['harga'] && $data['harga'] > 0) {
                self::upsert_finance($booking_id, $data);
            }
        } else {
            $data['created_by'] = get_current_user_id();
            $wpdb->insert($wpdb->prefix . 'kst_bookings', $data);
            $new_booking_id = $wpdb->insert_id;

            // Buat record INCOME di tabel finances otomatis
            if ($data['harga'] > 0) {
                self::upsert_finance($new_booking_id, $data);
            }
        }

        KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'list', 'success' => '1']);
    }

    /**
     * Buat atau update record INCOME di tabel finances.
     * Dipanggil otomatis saat booking disimpan.
     */
    private static function upsert_finance(int $booking_id, array $booking_data): void {
        global $wpdb;

        $table     = $wpdb->prefix . 'kst_finances';
        $kategori  = match($booking_data['service_type']) {
            'glamping' => 'Glamping',
            'cafe'     => 'Café',
            'camping'  => 'Camping',
            default    => 'Booking',
        };

        $unit_info = $booking_data['unit_glamping']
            ? ' – ' . $booking_data['unit_glamping']
            : '';

        $finance_data = [
            'type'        => 'INCOME',
            'amount'      => $booking_data['harga'],
            'category'    => $kategori,
            'description' => 'Booking #' . $booking_id . $unit_info . ' – ' . $booking_data['customer_name'],
            'date'        => $booking_data['checkin_date'],
            'status'      => 'draft', // Admin KST tetap perlu validasi
            'created_by'  => get_current_user_id(),
        ];

        // Cek apakah sudah ada finance untuk booking ini
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT finance_id FROM $table WHERE description LIKE %s LIMIT 1",
            'Booking #' . $booking_id . '%'
        ));

        if ($existing) {
            $wpdb->update($table, $finance_data, ['finance_id' => $existing]);
        } else {
            $wpdb->insert($table, $finance_data);
        }
    }

    public function handle_validate_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_validate');
        if (!KSTCangar_Helpers::verify_nonce('validate_booking')) wp_die('Invalid nonce.');

        global $wpdb;
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        if (!in_array($new_status, ['confirmed', 'cancelled'])) wp_die('Invalid status.');

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

    public function handle_delete_booking(): void {
        KSTCangar_Roles::require_cap('kstcangar_booking');
        if (!KSTCangar_Helpers::verify_nonce('delete_booking')) wp_die('Invalid nonce.');

        global $wpdb;
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if ($booking_id > 0) {
            $wpdb->delete($wpdb->prefix . 'kst_bookings', ['booking_id' => $booking_id]);
        }
        KSTCangar_Helpers::redirect('kstcangar-booking', ['tab' => 'list', 'success' => '1']);
    }

    public function handle_public_booking(): void {
        if (!isset($_POST['kstcangar_nonce']) ||
            !wp_verify_nonce($_POST['kstcangar_nonce'], 'kstcangar_public_booking')) {
            wp_die('Invalid request.');
        }

        global $wpdb;
        $data = [
            'service_type'  => sanitize_text_field($_POST['service_type']  ?? ''),
            'customer_name' => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_phone'=> sanitize_text_field($_POST['customer_phone']?? ''),
            'alamat'        => sanitize_textarea_field($_POST['alamat']     ?? ''),
            'checkin_date'  => sanitize_text_field($_POST['checkin_date']   ?? ''),
            'checkout_date' => sanitize_text_field($_POST['checkout_date']  ?? ''),
            'unit_glamping' => sanitize_text_field($_POST['unit_glamping']  ?? ''),
            'quantity'      => max(1, (int)($_POST['quantity'] ?? 1)),
            'notes'         => sanitize_textarea_field($_POST['notes']      ?? ''),
            'status'        => 'pending',
            'created_by'    => get_current_user_id() ?: 0,
        ];

        if (empty($data['customer_name']) || empty($data['checkin_date']) || empty($data['service_type'])) {
            wp_redirect(add_query_arg('booking', 'error', wp_get_referer())); exit;
        }

        if (!self::is_available($data['service_type'], $data['checkin_date'], $data['quantity'])) {
            wp_redirect(add_query_arg('booking', 'full', wp_get_referer())); exit;
        }

        $wpdb->insert($wpdb->prefix . 'kst_bookings', $data);
        wp_redirect(add_query_arg('booking', 'success', wp_get_referer())); exit;
    }

    // ══════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════

    public static function get_booked_qty(string $service, string $date, int $exclude_id = 0): int {
        global $wpdb;
        $exclude = $exclude_id > 0 ? $wpdb->prepare('AND booking_id != %d', $exclude_id) : '';
        return (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM {$wpdb->prefix}kst_bookings
            WHERE service_type = %s AND checkin_date = %s
            AND status IN ('pending','confirmed') $exclude
        ", $service, $date));
    }

    public static function is_available(string $service, string $date, int $quantity = 1): bool {
        $kapasitas = ['glamping' => 10, 'cafe' => 50, 'camping' => 20];
        $max = $kapasitas[$service] ?? 10;
        return (self::get_booked_qty($service, $date) + $quantity) <= $max;
    }

    public static function service_label(string $service): string {
        return match($service) {
            'glamping' => '🏕️ Glamping',
            'cafe'     => '☕ Café',
            'camping'  => '⛺ Camping',
            default    => $service,
        };
    }

    public static function get_stats(): array {
        global $wpdb;
        return [
            'total_today'     => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE checkin_date = %s AND status != 'cancelled'", date('Y-m-d'))),
            'pending'         => (int)$wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE status = 'pending'"),
            'confirmed_month' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE DATE_FORMAT(checkin_date,'%%Y-%%m') = %s AND status = 'confirmed'", date('Y-m'))),
        ];
    }
}