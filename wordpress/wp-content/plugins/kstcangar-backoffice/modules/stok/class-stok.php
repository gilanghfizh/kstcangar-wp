<?php
defined('ABSPATH') || exit;

class KSTCangar_Stok {

    public function __construct() {
        add_action('admin_post_kstcangar_save_item',       [$this, 'handle_save_item']);
        add_action('admin_post_kstcangar_save_movement',   [$this, 'handle_save_movement']);
        add_action('admin_post_kstcangar_save_opname',     [$this, 'handle_save_opname']);
        add_action('admin_post_kstcangar_validate_opname', [$this, 'handle_validate_opname']);
        add_action('admin_post_kstcangar_delete_item',     [$this, 'handle_delete_item']);
    }

    // ══════════════════════════════════════════════════════
    // RENDER — Router halaman utama
    // ══════════════════════════════════════════════════════

    public static function render_page(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');

        $tab = $_GET['tab'] ?? 'data-barang';

        echo '<div class="wrap">';
        echo '<h1>📦 Stok Opname</h1>';

        $tabs = [
            'data-barang' => 'Data Barang',
            'harian'      => 'Stok Harian',
            'opname'      => 'Input Opname',
            'items'       => 'Master Barang',
            'laporan'     => 'Laporan Mingguan',
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? 'nav-tab-active' : '';
            $url = add_query_arg(['page' => 'kstcangar-stok', 'tab' => $key], admin_url('admin.php'));
            echo "<a href='" . esc_url($url) . "' class='nav-tab $active'>$label</a>";
        }
        echo '</nav><div style="margin-top:20px;">';

        match ($tab) {
            'data-barang' => self::render_data_barang(),
            'harian'      => self::render_stok_harian(),
            'items'       => self::render_items(),
            'laporan'     => self::render_laporan(),
            default       => self::render_opname(),
        };

        echo '</div></div>';
    }

    // ══════════════════════════════════════════════════════
    // TAB: DATA BARANG (baru)
    // ══════════════════════════════════════════════════════

    private static function render_data_barang(): void {
        global $wpdb;

        $data_barang = $wpdb->get_results("
            SELECT
                i.item_id,
                i.name,
                i.unit,
                COALESCE(SUM(CASE WHEN m.type = 'IN'     THEN m.quantity ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN m.type = 'OUT'    THEN m.quantity ELSE 0 END), 0) AS total_out,
                COALESCE(SUM(CASE WHEN m.type = 'RETURN' THEN m.quantity ELSE 0 END), 0) AS total_return,
                latest.physical_stock  AS last_physical_stock,
                latest.difference      AS last_difference,
                latest.status          AS last_opname_status,
                latest.week            AS last_opname_week
            FROM {$wpdb->prefix}kst_items i
            LEFT JOIN {$wpdb->prefix}kst_stock_movements m ON i.item_id = m.item_id
            LEFT JOIN (
                SELECT o1.*
                FROM {$wpdb->prefix}kst_stock_opname o1
                INNER JOIN (
                    SELECT item_id, MAX(created_at) AS max_date
                    FROM {$wpdb->prefix}kst_stock_opname
                    GROUP BY item_id
                ) o2 ON o1.item_id = o2.item_id AND o1.created_at = o2.max_date
            ) latest ON i.item_id = latest.item_id
            WHERE i.is_active = 1
            GROUP BY i.item_id, i.name, i.unit,
                     latest.physical_stock, latest.difference,
                     latest.status, latest.week
            ORDER BY i.name ASC
        ");

        require KSTCANGAR_PATH . 'modules/stok/views/data-barang.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: STOK HARIAN (baru)
    // ══════════════════════════════════════════════════════

    private static function render_stok_harian(): void {
        global $wpdb;

        $date        = sanitize_text_field($_GET['filter_date'] ?? date('Y-m-d'));
        $filter_item = (int)($_GET['filter_item'] ?? 0);

        $where  = ['m.date = %s'];
        $params = [$date];

        if ($filter_item) {
            $where[]  = 'm.item_id = %d';
            $params[] = $filter_item;
        }

        $where_sql = implode(' AND ', $where);

        $movements = $wpdb->get_results($wpdb->prepare("
            SELECT m.movement_id, m.date, m.type, m.quantity, m.description,
                   m.created_at, i.name AS item_name, i.unit,
                   u.display_name AS created_by_name
            FROM {$wpdb->prefix}kst_stock_movements m
            JOIN {$wpdb->prefix}kst_items i ON m.item_id = i.item_id
            LEFT JOIN {$wpdb->users} u ON m.created_by = u.ID
            WHERE $where_sql
            ORDER BY m.created_at DESC
        ", ...$params));

        // Summary IN/OUT/RETURN hari ini
        $summary = $wpdb->get_row($wpdb->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'IN'     THEN quantity ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN type = 'OUT'    THEN quantity ELSE 0 END), 0) AS total_out,
                COALESCE(SUM(CASE WHEN type = 'RETURN' THEN quantity ELSE 0 END), 0) AS total_return
            FROM {$wpdb->prefix}kst_stock_movements
            WHERE date = %s
            " . ($filter_item ? $wpdb->prepare('AND item_id = %d', $filter_item) : ''),
            $date
        ));

        $items = self::get_active_items();

        require KSTCANGAR_PATH . 'modules/stok/views/stok-harian.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: INPUT OPNAME
    // ══════════════════════════════════════════════════════

    private static function render_opname(): void {
        global $wpdb;

        $action = $_GET['action'] ?? 'list';

        if ($action === 'form') {
            require KSTCANGAR_PATH . 'modules/stok/views/form-opname.php';
            return;
        }

        $date  = $_GET['filter_date'] ?? date('Y-m-d');
        $week  = KSTCangar_Helpers::get_week($date);

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
    // HANDLERS
    // ══════════════════════════════════════════════════════

    public function handle_save_item(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');
        if (!KSTCangar_Helpers::verify_nonce('save_item')) wp_die('Invalid nonce.');

        global $wpdb;
        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'unit' => sanitize_text_field($_POST['unit'] ?? 'pcs'),
        ];

        if (empty($data['name'])) {
            KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'error' => 'name_required']);
        }

        $item_id = (int)($_POST['item_id'] ?? 0);
        $item_id > 0
            ? $wpdb->update($wpdb->prefix . 'kst_items', $data, ['item_id' => $item_id])
            : $wpdb->insert($wpdb->prefix . 'kst_items', $data);

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'success' => '1']);
    }

    public function handle_delete_item(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');
        if (!KSTCangar_Helpers::verify_nonce('delete_item')) wp_die('Invalid nonce.');

        global $wpdb;
        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id > 0) {
            $wpdb->update($wpdb->prefix . 'kst_items', ['is_active' => 0], ['item_id' => $item_id]);
        }

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'items', 'success' => '1']);
    }

    public function handle_save_movement(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');
        if (!KSTCangar_Helpers::verify_nonce('save_movement')) wp_die('Invalid nonce.');

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
            KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'harian', 'error' => 'invalid_data']);
        }

        $wpdb->insert($wpdb->prefix . 'kst_stock_movements', $data);
        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'harian', 'success' => '1']);
    }

    public function handle_save_opname(): void {
        KSTCangar_Roles::require_cap('kstcangar_stok');
        if (!KSTCangar_Helpers::verify_nonce('save_opname')) wp_die('Invalid nonce.');

        global $wpdb;
        $table = $wpdb->prefix . 'kst_stock_opname';

        $item_id        = (int)($_POST['item_id'] ?? 0);
        $week           = sanitize_text_field($_POST['week'] ?? KSTCangar_Helpers::get_week());
        $initial_stock  = (int)($_POST['initial_stock'] ?? 0);
        $physical_stock = (int)($_POST['physical_stock'] ?? 0);
        $week_date      = date('Y-m-d', strtotime($week . ' Monday'));

        $stock_in = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0) FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'IN' AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, $week_date));

        $stock_out = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0) FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'OUT' AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, $week_date));

        $stock_return = (int)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(quantity), 0) FROM {$wpdb->prefix}kst_stock_movements
            WHERE item_id = %d AND type = 'RETURN' AND YEARWEEK(date, 1) = YEARWEEK(%s, 1)
        ", $item_id, $week_date));

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

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT opname_id FROM $table WHERE item_id = %d AND week = %s",
            $item_id, $week
        ));

        $existing
            ? $wpdb->update($table, $data, ['opname_id' => $existing])
            : $wpdb->insert($table, $data);

        KSTCangar_Helpers::redirect('kstcangar-stok', ['tab' => 'opname', 'success' => '1']);
    }

    public function handle_validate_opname(): void {
        KSTCangar_Roles::require_cap('kstcangar_validate');
        if (!KSTCangar_Helpers::verify_nonce('validate_opname')) wp_die('Invalid nonce.');

        global $wpdb;
        $opname_id = (int)($_POST['opname_id'] ?? 0);
        $action    = sanitize_text_field($_POST['validate_action'] ?? '');

        if (!in_array($action, ['validated', 'rejected'])) wp_die('Invalid action.');

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
    // HELPERS
    // ══════════════════════════════════════════════════════

    public static function get_active_items(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT item_id, name, unit FROM {$wpdb->prefix}kst_items WHERE is_active = 1 ORDER BY name ASC"
        ) ?: [];
    }
}
