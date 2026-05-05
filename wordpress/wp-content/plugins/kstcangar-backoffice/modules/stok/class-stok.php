<?php
defined('ABSPATH') || exit;

class KSTCangar_Stok {

    public function __construct() {
        // Handle form submissions
        add_action('admin_post_kstcangar_save_item',    [$this, 'handle_save_item']);
        add_action('admin_post_kstcangar_save_movement',[$this, 'handle_save_movement']);
        add_action('admin_post_kstcangar_save_opname',  [$this, 'handle_save_opname']);
        add_action('admin_post_kstcangar_validate_opname', [$this, 'handle_validate_opname']);
        add_action('admin_post_kstcangar_delete_item',  [$this, 'handle_delete_item']);
    }

    // ══════════════════════════════════════════════════════
    // RENDER — Router halaman utama
    // ══════════════════════════════════════════════════════

    public static function render_page(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        $tab = $_GET['tab'] ?? 'opname';

        echo '<div class="wrap">';
        echo '<h1>📦 Stok Opname</h1>';

        // Tab navigasi
        $tabs = [
            'opname'    => 'Input Stok Harian',
            'items'     => 'Master Barang',
            'laporan'   => 'Laporan Mingguan',
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? 'nav-tab-active' : '';
            $url = add_query_arg(['page' => 'kstcangar-stok', 'tab' => $key], admin_url('admin.php'));
            echo "<a href='" . esc_url($url) . "' class='nav-tab $active'>$label</a>";
        }
        echo '</nav><div style="margin-top:20px;">';

        match ($tab) {
            'items'   => self::render_items(),
            'laporan' => self::render_laporan(),
            default   => self::render_opname(),
        };

        echo '</div></div>';
    }

    // ══════════════════════════════════════════════════════
    // TAB: INPUT STOK HARIAN (Opname)
    // ══════════════════════════════════════════════════════

    private static function render_opname(): void {
        global $wpdb;

        $action = $_GET['action'] ?? 'list';

        if ($action === 'form') {
            require KSTCANGAR_PATH . 'modules/stok/views/form-opname.php';
            return;
        }

        // Tampilkan list input harian
        $date   = $_GET['filter_date'] ?? date('Y-m-d');
        $week   = KSTCangar_Helpers::get_week($date);

        $opnames = $wpdb->get_results($wpdb->prepare("
            SELECT o.*, i.name AS item_name, i.unit,
                   u.display_name AS created_by_name
            FROM {$wpdb->prefix}kst_stock_opname o
            JOIN {$wpdb->prefix}kst_items i ON o.item_id = i.item_id
            LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
            WHERE o.week = %s
            ORDER BY i.name ASC
        ", $week));

        require KSTCANGAR_PATH . 'modules/stok/views/list.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: MASTER BARANG
    // ══════════════════════════════════════════════════════

    private static function render_items(): void {
        global $wpdb;

        $action = $_GET['action'] ?? 'list';

        if ($action === 'form') {
            $item = null;
            if (!empty($_GET['item_id'])) {
                $item = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}kst_items WHERE item_id = %d",
                    (int)$_GET['item_id']
                ));
            }
            require KSTCANGAR_PATH . 'modules/stok/views/form-item.php';
            return;
        }

        $items = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}kst_items ORDER BY name ASC"
        );

        require KSTCANGAR_PATH . 'modules/stok/views/list-items.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: LAPORAN MINGGUAN
    // ══════════════════════════════════════════════════════

    private static function render_laporan(): void {
        global $wpdb;

        $week = $_GET['filter_week'] ?? KSTCangar_Helpers::get_week();

        $laporan = $wpdb->get_results($wpdb->prepare("
            SELECT o.*, i.name AS item_name, i.unit,
                   u.display_name AS validated_by_name
            FROM {$wpdb->prefix}kst_stock_opname o
            JOIN {$wpdb->prefix}kst_items i ON o.item_id = i.item_id
            LEFT JOIN {$wpdb->users} u ON o.validated_by = u.ID
            WHERE o.week = %s
            ORDER BY i.name ASC
        ", $week));

        require KSTCANGAR_PATH . 'modules/stok/views/laporan.php';
    }

    // ══════════════════════════════════════════════════════
    // HANDLERS — Form submissions
    // ══════════════════════════════════════════════════════

    /** Simpan master barang (tambah/edit) */
    public function handle_save_item(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        if (!KSTCangar_Helpers::verify_nonce('save_item')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'kst_items';

        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'unit' => sanitize_text_field($_POST['unit'] ?? 'pcs'),
        ];

        if (empty($data['name'])) {
            KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'error' => 'name_required']);
        }

        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id > 0) {
            $wpdb->update($table, $data, ['item_id' => $item_id]);
        } else {
            $wpdb->insert($table, $data);
        }

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'success' => '1']);
    }

    /** Hapus barang */
    public function handle_delete_item(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        if (!KSTCangar_Helpers::verify_nonce('delete_item')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;
        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id > 0) {
            // Soft delete — nonaktifkan saja
            $wpdb->update(
                $wpdb->prefix . 'kst_items',
                ['is_active' => 0],
                ['item_id' => $item_id]
            );
        }

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'success' => '1']);
    }

    /** Simpan pergerakan stok harian (IN/OUT/RETURN) */
    public function handle_save_movement(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        if (!KSTCangar_Helpers::verify_nonce('save_movement')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $data = [
            'item_id'     => (int)($_POST['item_id'] ?? 0),
            'date'        => sanitize_text_field($_POST['date'] ?? date('Y-m-d')),
            'type'        => sanitize_text_field($_POST['type'] ?? 'IN'),
            'quantity'    => (int)($_POST['quantity'] ?? 0),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'created_by'  => get_current_user_id(),
        ];

        if (!$data['item_id'] || !in_array($data['type'], ['IN','OUT','RETURN'])) {
            KSTCangar_Helpers::redirect('kstcangar-stok', ['error' => 'invalid_data']);
        }

        $wpdb->insert($wpdb->prefix . 'kst_stock_movements', $data);

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'opname', 'success' => '1']);
    }

    /** Simpan / update data stok opname mingguan */
    public function handle_save_opname(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        if (!KSTCangar_Helpers::verify_nonce('save_opname')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'kst_stock_opname';

        $item_id       = (int)($_POST['item_id'] ?? 0);
        $week          = sanitize_text_field($_POST['week'] ?? KSTCangar_Helpers::get_week());
        $initial_stock = (int)($_POST['initial_stock'] ?? 0);
        $physical_stock = (int)($_POST['physical_stock'] ?? 0);

        // Hitung stok dari movements otomatis
        $stock_in = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'IN'
            AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, date('Y-m-d', strtotime($week . ' Monday'))));

        $stock_out = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'OUT'
            AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, date('Y-m-d', strtotime($week . ' Monday'))));

        $stock_return = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'RETURN'
            AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, date('Y-m-d', strtotime($week . ' Monday'))));

        // Stok sistem = awal + masuk + retur - keluar
        $system_stock = $initial_stock + $stock_in + $stock_return - $stock_out;
        $difference   = $system_stock - $physical_stock;

        $data = [
            'item_id'        => $item_id,
            'week'           => $week,
            'initial_stock'  => $initial_stock,
            'stock_in'       => $stock_in,
            'stock_out'      => $stock_out,
            'stock_return'   => $stock_return,
            'system_stock'   => $system_stock,
            'physical_stock' => $physical_stock,
            'difference'     => $difference,
            'note'           => sanitize_textarea_field($_POST['note'] ?? ''),
            'created_by'     => get_current_user_id(),
        ];

        // Upsert — update jika sudah ada, insert jika belum
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT opname_id FROM $table WHERE item_id = %d AND week = %s",
            $item_id, $week
        ));

        if ($existing) {
            $wpdb->update($table, $data, ['opname_id' => $existing]);
        } else {
            $wpdb->insert($table, $data);
        }

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'opname', 'success' => '1']);
    }

    /** Validasi / tolak opname — hanya Admin KST */
    public function handle_validate_opname(): void {
        KSTCangar_Roles::require_cap('kstcangar_validate');

        if (!KSTCangar_Helpers::verify_nonce('validate_opname')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $opname_id = (int)($_POST['opname_id'] ?? 0);
        $action    = sanitize_text_field($_POST['validate_action'] ?? '');

        if (!in_array($action, ['validated', 'rejected'])) {
            wp_die('Invalid action.');
        }

        $wpdb->update(
            $wpdb->prefix . 'kst_stock_opname',
            [
                'status'       => $action,
                'validated_by' => get_current_user_id(),
                'validated_at' => current_time('mysql'),
            ],
            ['opname_id' => $opname_id]
        );

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'laporan', 'success' => '1']);
    }

    // ══════════════════════════════════════════════════════
    // HELPERS STATIS
    // ══════════════════════════════════════════════════════

    /** Ambil semua barang aktif untuk dropdown */
    public static function get_active_items(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT item_id, name, unit FROM {$wpdb->prefix}kst_items WHERE is_active = 1 ORDER BY name ASC"
        ) ?: [];
    }
}
