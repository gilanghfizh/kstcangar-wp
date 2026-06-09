<?php
defined('ABSPATH') || exit;

class KSTCangar_Helpers {

    /**
     * Format angka ke Rupiah.
     * Contoh: 150000 → "Rp 150.000"
     */
    public static function rupiah(float $amount): string {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format tanggal ke bahasa Indonesia.
     * Contoh: 2026-04-28 → "28 April 2026"
     */
    public static function format_date(string $date): string {
        $bulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
            5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
            9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $d = date_create($date);
        if (!$d) return $date;
        return date_format($d, 'j') . ' ' . $bulan[(int)date_format($d, 'n')] . ' ' . date_format($d, 'Y');
    }

    /**
     * Dapatkan minggu dalam format YYYY-WXX dari tanggal.
     * Contoh: 2026-04-28 → "2026-W18"
     */
    public static function get_week(string $date = ''): string {
        $d = $date ? date_create($date) : date_create();
        return date_format($d, 'Y-\WW');
    }

    /**
     * Tampilkan badge status dengan warna.
     */
    public static function status_badge(string $status): string {
        $map = [
            'draft'     => ['label' => 'Draft',     'color' => '#f0ad4e'],
            'validated' => ['label' => 'Tervalidasi','color' => '#5cb85c'],
            'rejected'  => ['label' => 'Ditolak',   'color' => '#d9534f'],
            'pending'   => ['label' => 'Pending',   'color' => '#f0ad4e'],
            'confirmed' => ['label' => 'Confirmed', 'color' => '#5cb85c'],
            'cancelled' => ['label' => 'Dibatalkan','color' => '#d9534f'],
        ];
        $s = $map[$status] ?? ['label' => $status, 'color' => '#999'];
        return sprintf(
            '<span style="background:%s;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;">%s</span>',
            esc_attr($s['color']),
            esc_html($s['label'])
        );
    }

    /**
     * Nonce field untuk form — wrapper agar konsisten.
     */
    public static function nonce_field(string $action): void {
        wp_nonce_field('kstcangar_' . $action, 'kstcangar_nonce');
    }

    /**
     * Verifikasi nonce dari form submission.
     */
    public static function verify_nonce(string $action): bool {
        return isset($_POST['kstcangar_nonce'])
            && wp_verify_nonce($_POST['kstcangar_nonce'], 'kstcangar_' . $action);
    }

    /**
     * Redirect ke halaman admin KST dengan query params.
     */
    public static function redirect(string $page, array $args = []): void {
        $args['page'] = $page;
        wp_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
