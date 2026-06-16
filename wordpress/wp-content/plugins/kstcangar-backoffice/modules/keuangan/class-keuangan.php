<?php
defined('ABSPATH') || exit;

class KSTCangar_Keuangan {

    // Kategori fixed sesuai kebutuhan client
    const KATEGORI_PEMASUKAN  = ['Booking', 'Café', 'Glamping', 'Camping', 'Lainnya'];
    const KATEGORI_PENGELUARAN = ['Operasional', 'Bahan Baku', 'Perawatan', 'Gaji', 'Lainnya'];

    public function __construct() {
        add_action('admin_post_kstcangar_save_finance',     [$this, 'handle_save_finance']);
        add_action('admin_post_kstcangar_validate_finance', [$this, 'handle_validate_finance']);
        add_action('admin_post_kstcangar_delete_finance',   [$this, 'handle_delete_finance']);
    }

    // ══════════════════════════════════════════════════════
    // RENDER — Router halaman utama
    // ══════════════════════════════════════════════════════

    public static function render_page(): void {
        KSTCangar_Roles::require_cap('kstcangar_keuangan');

        $tab = $_GET['tab'] ?? 'list';

        echo '<div class="wrap">';
        echo '<h1>💰 Manajemen Keuangan</h1>';

        $tabs = [
            'list'   => 'Input Transaksi',
            'rekap'  => 'Rekap Harian',
            'mingguan' => 'Rekap Mingguan',
            'bulanan'  => 'Rekap Bulanan',
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? 'nav-tab-active' : '';
            $url = add_query_arg(['page' => 'kstcangar-keuangan', 'tab' => $key], admin_url('admin.php'));
            echo "<a href='" . esc_url($url) . "' class='nav-tab $active'>$label</a>";
        }
        echo '</nav><div style="margin-top:20px;">';

        match ($tab) {
            'rekap'    => self::render_rekap_harian(),
            'mingguan' => self::render_rekap_mingguan(),
            'bulanan'  => self::render_rekap_bulanan(),
            default    => self::render_list(),
        };

        echo '</div></div>';
    }

    // ══════════════════════════════════════════════════════
    // TAB: INPUT TRANSAKSI
    // ══════════════════════════════════════════════════════

    private static function render_list(): void {
        global $wpdb;

        $filter_type = sanitize_text_field($_GET['filter_type'] ?? '');
        $filter_date = sanitize_text_field($_GET['filter_date'] ?? '');
        $action      = $_GET['action'] ?? 'list';

        if ($action === 'form') {
            $finance = null;
            if (!empty($_GET['finance_id'])) {
                $finance = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}kst_finances WHERE finance_id = %d",
                    (int)$_GET['finance_id']
                ));
            }
            require KSTCANGAR_PATH . 'modules/keuangan/views/form-finance.php';
            return;
        }

        $where  = ['1=1'];
        $params = [];

        if ($filter_type) {
            $where[]  = 'type = %s';
            $params[] = $filter_type;
        }
        if ($filter_date) {
            $where[]  = 'date = %s';
            $params[] = $filter_date;
        }

        $where_sql = implode(' AND ', $where);
        $query = "
            SELECT f.*, u.display_name AS created_by_name,
                   v.display_name AS validated_by_name
            FROM {$wpdb->prefix}kst_finances f
            LEFT JOIN {$wpdb->users} u ON f.created_by = u.ID
            LEFT JOIN {$wpdb->users} v ON f.validated_by = v.ID
            WHERE $where_sql
            ORDER BY f.date DESC, f.created_at DESC
        ";

        $transaksi = $params
            ? $wpdb->get_results($wpdb->prepare($query, ...$params))
            : $wpdb->get_results($query);

        // Ringkasan hari ini
        $summary = self::get_daily_summary(date('Y-m-d'));

        require KSTCANGAR_PATH . 'modules/keuangan/views/list.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: REKAP HARIAN
    // ══════════════════════════════════════════════════════

    private static function render_rekap_harian(): void {
        global $wpdb;

        $date    = sanitize_text_field($_GET['filter_date'] ?? date('Y-m-d'));
        $summary = self::get_daily_summary($date);

        $transaksi = $wpdb->get_results($wpdb->prepare("
            SELECT f.*, u.display_name AS created_by_name
            FROM {$wpdb->prefix}kst_finances f
            LEFT JOIN {$wpdb->users} u ON f.created_by = u.ID
            WHERE f.date = %s
            ORDER BY f.created_at DESC
        ", $date));

        // Per kategori
        $per_kategori = $wpdb->get_results($wpdb->prepare("
            SELECT type, category,
                   SUM(amount) AS total,
                   COUNT(*) AS jumlah
            FROM {$wpdb->prefix}kst_finances
            WHERE date = %s AND status = 'validated'
            GROUP BY type, category
            ORDER BY type, total DESC
        ", $date));

        require KSTCANGAR_PATH . 'modules/keuangan/views/rekap-harian.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: REKAP MINGGUAN
    // ══════════════════════════════════════════════════════

    private static function render_rekap_mingguan(): void {
        global $wpdb;

        $week = sanitize_text_field($_GET['filter_week'] ?? KSTCangar_Helpers::get_week());

        $rekap = $wpdb->get_results($wpdb->prepare("
            SELECT
                date,
                SUM(CASE WHEN type = 'INCOME'  THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'EXPENSE' THEN amount ELSE 0 END) AS total_expense,
                COUNT(*) AS jumlah_transaksi
            FROM {$wpdb->prefix}kst_finances
            WHERE YEARWEEK(date, 1) = YEARWEEK(%s, 1)
            AND status = 'validated'
            GROUP BY date
            ORDER BY date ASC
        ", date('Y-m-d', strtotime($week . ' Monday'))));

        $total_income  = array_sum(array_column((array)$rekap, 'total_income'));
        $total_expense = array_sum(array_column((array)$rekap, 'total_expense'));
        $net           = $total_income - $total_expense;

        // Per kategori minggu ini
        $per_kategori = $wpdb->get_results($wpdb->prepare("
            SELECT type, category, SUM(amount) AS total
            FROM {$wpdb->prefix}kst_finances
            WHERE YEARWEEK(date, 1) = YEARWEEK(%s, 1)
            AND status = 'validated'
            GROUP BY type, category
            ORDER BY type, total DESC
        ", date('Y-m-d', strtotime($week . ' Monday'))));

        require KSTCANGAR_PATH . 'modules/keuangan/views/rekap-mingguan.php';
    }

    // ══════════════════════════════════════════════════════
    // TAB: REKAP BULANAN
    // ══════════════════════════════════════════════════════

    private static function render_rekap_bulanan(): void {
        global $wpdb;

        $month = sanitize_text_field($_GET['filter_month'] ?? date('Y-m'));

        $rekap = $wpdb->get_results($wpdb->prepare("
            SELECT
                date,
                SUM(CASE WHEN type = 'INCOME'  THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'EXPENSE' THEN amount ELSE 0 END) AS total_expense,
                COUNT(*) AS jumlah_transaksi
            FROM {$wpdb->prefix}kst_finances
            WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
            AND status = 'validated'
            GROUP BY date
            ORDER BY date ASC
        ", $month));

        $total_income  = array_sum(array_column((array)$rekap, 'total_income'));
        $total_expense = array_sum(array_column((array)$rekap, 'total_expense'));
        $net           = $total_income - $total_expense;

        // Per kategori bulan ini
        $per_kategori = $wpdb->get_results($wpdb->prepare("
            SELECT type, category, SUM(amount) AS total
            FROM {$wpdb->prefix}kst_finances
            WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
            AND status = 'validated'
            GROUP BY type, category
            ORDER BY type, total DESC
        ", $month));

        require KSTCANGAR_PATH . 'modules/keuangan/views/rekap-bulanan.php';
    }

    // ══════════════════════════════════════════════════════
    // HANDLERS
    // ══════════════════════════════════════════════════════

    public function handle_save_finance(): void {
        KSTCangar_Roles::require_cap('kstcangar_keuangan');

        if (!KSTCangar_Helpers::verify_nonce('save_finance')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $finance_id = (int)($_POST['finance_id'] ?? 0);
        $type       = sanitize_text_field($_POST['type'] ?? '');

        if (!in_array($type, ['INCOME', 'EXPENSE'])) {
            KSTCangar_Helpers::redirect('kstcangar-keuangan', ['error' => 'invalid_type']);
        }

        // Validasi kategori sesuai type
        $valid_kategori = $type === 'INCOME'
            ? self::KATEGORI_PEMASUKAN
            : self::KATEGORI_PENGELUARAN;

        $category = sanitize_text_field($_POST['category'] ?? '');
        if (!in_array($category, $valid_kategori)) {
            KSTCangar_Helpers::redirect('kstcangar-keuangan', ['error' => 'invalid_category']);
        }

        $amount = (float)str_replace(['Rp', '.', ',', ' '], ['', '', '.', ''], $_POST['amount'] ?? '0');

        if ($amount <= 0) {
            KSTCangar_Helpers::redirect('kstcangar-keuangan', ['action' => 'form', 'error' => 'invalid_amount']);
        }

        $data = [
            'type'        => $type,
            'amount'      => $amount,
            'category'    => $category,
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'date'        => sanitize_text_field($_POST['date'] ?? date('Y-m-d')),
            'status'      => 'draft',
        ];

        if ($finance_id > 0) {
            $wpdb->update($wpdb->prefix . 'kst_finances', $data, ['finance_id' => $finance_id]);
        } else {
            $data['created_by'] = get_current_user_id();
            $wpdb->insert($wpdb->prefix . 'kst_finances', $data);
        }

        KSTCangar_Helpers::redirect('kstcangar-keuangan', ['success' => '1']);
    }

    public function handle_validate_finance(): void {
        KSTCangar_Roles::require_cap('kstcangar_validate');

        if (!KSTCangar_Helpers::verify_nonce('validate_finance')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;

        $finance_id = (int)($_POST['finance_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        if (!in_array($new_status, ['validated', 'rejected'])) {
            wp_die('Invalid status.');
        }

        $wpdb->update(
            $wpdb->prefix . 'kst_finances',
            [
                'status'       => $new_status,
                'validated_by' => get_current_user_id(),
                'validated_at' => current_time('mysql'),
            ],
            ['finance_id' => $finance_id]
        );

        KSTCangar_Helpers::redirect('kstcangar-keuangan', ['success' => '1']);
    }

    public function handle_delete_finance(): void {
        KSTCangar_Roles::require_cap('kstcangar_keuangan');

        if (!KSTCangar_Helpers::verify_nonce('delete_finance')) {
            wp_die('Invalid nonce.');
        }

        global $wpdb;
        $finance_id = (int)($_POST['finance_id'] ?? 0);

        if ($finance_id > 0) {
            $wpdb->delete($wpdb->prefix . 'kst_finances', ['finance_id' => $finance_id]);
        }

        KSTCangar_Helpers::redirect('kstcangar-keuangan', ['success' => '1']);
    }

    // ══════════════════════════════════════════════════════
    // HELPERS STATIS
    // ══════════════════════════════════════════════════════

    /** Ringkasan pemasukan & pengeluaran untuk satu tanggal */
    public static function get_daily_summary(string $date): array {
        global $wpdb;

        $income = (float)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM {$wpdb->prefix}kst_finances
            WHERE date = %s AND type = 'INCOME' AND status = 'validated'
        ", $date));

        $expense = (float)$wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM {$wpdb->prefix}kst_finances
            WHERE date = %s AND type = 'EXPENSE' AND status = 'validated'
        ", $date));

        return [
            'income'  => $income,
            'expense' => $expense,
            'net'     => $income - $expense,
        ];
    }

    /** Data untuk dashboard */
    public static function get_stats(): array {
        global $wpdb;

        $today         = self::get_daily_summary(date('Y-m-d'));
        $pending_count = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kst_finances WHERE status = 'draft'"
        );

        return [
            'income_today'  => $today['income'],
            'expense_today' => $today['expense'],
            'net_today'     => $today['net'],
            'pending_count' => $pending_count,
        ];
    }
}
