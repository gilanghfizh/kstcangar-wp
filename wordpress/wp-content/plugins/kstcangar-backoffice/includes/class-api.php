<?php
defined('ABSPATH') || exit;

/**
 * KSTCangar_API
 * REST API sesuai kontrak data KST Dashboard v0.0.1
 * Base URL: /wp-json/kstcangar/v1/
 */
class KSTCangar_API {

    // Identifier KST ini — dipakai di JWT claims roles
    const KST_IDENTIFIER = 'kst-cangar';

    // Versi kontrak data
    const CONTRACT_VERSION = '0.0.1';

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {

        $namespace = 'kstcangar/v1';

        // ── AUTH ──────────────────────────────────────────
        register_rest_route($namespace, '/auth/login', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'login'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/auth/me', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'me'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/auth/logout', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'logout'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/auth/refresh', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'refresh_token'],
            'permission_callback' => '__return_true',
        ]);

        // ── HEALTH CHECK ──────────────────────────────────
        register_rest_route($namespace, '/health', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'health_check'],
            'permission_callback' => '__return_true',
        ]);

        // ── CONTRACT ──────────────────────────────────────
        register_rest_route($namespace, '/contract', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_contract'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // ── DATA ENDPOINTS ────────────────────────────────
        // Booking
        register_rest_route($namespace, '/data/booking', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_booking'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Keuangan
        register_rest_route($namespace, '/data/keuangan', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_keuangan'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Keuangan rekap
        register_rest_route($namespace, '/data/keuangan/rekap', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_keuangan_rekap'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Stok opname
        register_rest_route($namespace, '/data/stok', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stok'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Stok items
        register_rest_route($namespace, '/data/stok/items', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stok_items'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Summary
        register_rest_route($namespace, '/data/summary', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_summary'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // ── QUERY AGREGAT ─────────────────────────────────
        register_rest_route($namespace, '/query', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'query'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    // ══════════════════════════════════════════════════════
    // RESPONSE HELPERS — sesuai kontrak
    // ══════════════════════════════════════════════════════

    private static function timestamp(): string {
        return current_time('c'); // ISO 8601 dengan timezone
    }

    /** Response sukses standar */
    private static function ok($response_data, int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'timestamp' => self::timestamp(),
            'response'  => $response_data,
        ], $status);
    }

    /** Response error standar */
    private static function err(int $code, string $message): WP_REST_Response {
        return new WP_REST_Response([
            'timestamp' => self::timestamp(),
            'response'  => null,
            'error'     => [
                'code'    => $code,
                'message' => $message,
            ],
        ], $code);
    }

    /**
     * Bungkus data dengan DataContainer sesuai kontrak.
     * Dipakai untuk semua endpoint /data/{path}
     */
    private static function data_container(string $code, $data, ?string $created_at = null, ?string $updated_at = null): array {
        return [
            'code'      => $code,
            'createdAt' => $created_at ?? self::timestamp(),
            'updatedAt' => $updated_at,
            'data'      => $data,
        ];
    }

    // ══════════════════════════════════════════════════════
    // AUTH CHECK
    // ══════════════════════════════════════════════════════

    public static function check_auth(WP_REST_Request $request): bool|WP_Error {
        $token = KSTCangar_JWT::get_token_from_header();

        if (!$token) {
            return new WP_Error('rest_forbidden',
                'Token tidak ditemukan. Sertakan Authorization: Bearer {token}.',
                ['status' => 401]
            );
        }

        $payload = KSTCangar_JWT::verify($token);

        if (!$payload) {
            return new WP_Error('rest_forbidden',
                'Token tidak valid atau sudah kadaluwarsa.',
                ['status' => 401]
            );
        }

        $request->set_param('_jwt_payload', $payload);
        $request->set_param('_jwt_user_id', $payload['sub'] ?? null);
        $request->set_param('_jwt_role',    $payload['roles'][self::KST_IDENTIFIER][0] ?? 'publik');

        return true;
    }

    // ══════════════════════════════════════════════════════
    // AUTH ENDPOINTS
    // ══════════════════════════════════════════════════════

    /**
     * POST /auth/login
     */
    public static function login(WP_REST_Request $request): WP_REST_Response {
        $body     = $request->get_json_params();
        $username = sanitize_text_field($body['username'] ?? $request->get_param('username') ?? '');
        $password = $body['password'] ?? $request->get_param('password') ?? '';

        if (!$username || !$password) {
            return self::err(400, 'Username dan password wajib diisi.');
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return self::err(401, 'Username atau password salah.');
        }

        if (!user_can($user, 'kstcangar_access')) {
            return self::err(403, 'Akun tidak memiliki akses ke sistem ini.');
        }

        // Ambil role KST
        $kst_roles = ['administrator','admin_kst','operator_stok','operator_booking','operator_keuangan','manajemen'];
        $user_role = 'publik';
        foreach ($user->roles as $role) {
            if (in_array($role, $kst_roles)) {
                $user_role = $role;
                break;
            }
        }

        // Generate access token (8 jam)
        $exp = time() + 28800;
        $access_token = KSTCangar_JWT::generate([
            'sub'      => (string)$user->ID,
            'username' => $user->user_login,
            'name'     => $user->display_name,
            'roles'    => [self::KST_IDENTIFIER => [$user_role]],
            'iss'      => get_site_url(),
            'aud'      => get_site_url(),
        ], 28800);

        // Generate refresh token (7 hari) — simpan hash di user meta
        $refresh_token     = bin2hex(random_bytes(32));
        $refresh_token_hash = hash('sha256', $refresh_token);
        update_user_meta($user->ID, 'kstcangar_refresh_token', $refresh_token_hash);
        update_user_meta($user->ID, 'kstcangar_refresh_exp',   time() + 604800);

        // Set refresh token di HTTP-only cookie
        setcookie('refresh_token', $refresh_token, [
            'expires'  => time() + 604800,
            'path'     => '/wp-json/kstcangar/v1/auth/refresh',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $response = self::ok([
            'accessToken' => $access_token,
            'expDate'     => $exp,
            'user'        => [
                'userid'   => (string)$user->ID,
                'username' => $user->user_login,
                'name'     => $user->display_name,
                'roles'    => [self::KST_IDENTIFIER => [$user_role]],
            ],
        ]);

        return $response;
    }

        /**
     * GET /auth/me
     * Cek token aktif & info user yang sedang login
     */
    public static function me(WP_REST_Request $request): WP_REST_Response {
        $payload = $request->get_param('_jwt_payload');

        return self::ok([
            'userid'   => $payload['sub']      ?? null,
            'username' => $payload['username'] ?? null,
            'name'     => $payload['name']     ?? null,
            'roles'    => $payload['roles']    ?? [],
            'iat'      => $payload['iat']      ?? null,
            'exp'      => $payload['exp']      ?? null,
        ]);
    }

    /**
     * POST /auth/logout
     */
    public static function logout(WP_REST_Request $request): WP_REST_Response {
        $refresh_token = $_COOKIE['refresh_token'] ?? '';

        if (!$refresh_token) {
            return self::err(400, 'Tidak ada refresh_token pada cookie request.');
        }

        $payload = $request->get_param('_jwt_payload');
        $user_id = $payload['sub'] ?? 0;

        // Hapus refresh token dari user meta
        delete_user_meta($user_id, 'kstcangar_refresh_token');
        delete_user_meta($user_id, 'kstcangar_refresh_exp');

        // Hapus cookie
        setcookie('refresh_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/wp-json/kstcangar/v1/auth/refresh',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        return self::ok([]);
    }

    /**
     * POST /auth/refresh
     */
    public static function refresh_token(WP_REST_Request $request): WP_REST_Response {
        $refresh_token = $_COOKIE['refresh_token'] ?? '';

        if (!$refresh_token) {
            return self::err(400, 'Tidak ada refresh_token pada cookie request.');
        }

        $refresh_hash = hash('sha256', $refresh_token);

        // Cari user berdasarkan refresh token
        $users = get_users(['meta_key' => 'kstcangar_refresh_token', 'meta_value' => $refresh_hash]);

        if (empty($users)) {
            return self::err(401, 'Refresh token tidak valid atau sudah kadaluwarsa.');
        }

        $user    = $users[0];
        $exp_time = (int)get_user_meta($user->ID, 'kstcangar_refresh_exp', true);

        if (time() > $exp_time) {
            delete_user_meta($user->ID, 'kstcangar_refresh_token');
            delete_user_meta($user->ID, 'kstcangar_refresh_exp');
            return self::err(401, 'Refresh token sudah kadaluwarsa. Silakan login kembali.');
        }

        // Ambil role
        $kst_roles = ['administrator','admin_kst','operator_stok','operator_booking','operator_keuangan','manajemen'];
        $user_role = 'publik';
        foreach ($user->roles as $role) {
            if (in_array($role, $kst_roles)) { $user_role = $role; break; }
        }

        // Generate access token baru
        $exp = time() + 28800;
        $access_token = KSTCangar_JWT::generate([
            'sub'      => (string)$user->ID,
            'username' => $user->user_login,
            'name'     => $user->display_name,
            'roles'    => [self::KST_IDENTIFIER => [$user_role]],
            'iss'      => get_site_url(),
            'aud'      => get_site_url(),
        ], 28800);

        return self::ok([
            'accessToken' => $access_token,
            'expDate'     => $exp,
            'user'        => [
                'userid'   => (string)$user->ID,
                'username' => $user->user_login,
                'name'     => $user->display_name,
                'roles'    => [self::KST_IDENTIFIER => [$user_role]],
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════
    // HEALTH CHECK
    // ══════════════════════════════════════════════════════

    public static function health_check(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        // Cek koneksi DB
        $db_ok = $wpdb->get_var('SELECT 1') === '1';

        if (!$db_ok) {
            return new WP_REST_Response([
                'timestamp' => self::timestamp(),
                'response'  => null,
                'error'     => ['code' => 503, 'message' => 'service unhealthy'],
            ], 503);
        }

        return self::ok(['status' => 'ok']);
    }

    // ══════════════════════════════════════════════════════
    // CONTRACT ENDPOINT
    // ══════════════════════════════════════════════════════

    /**
     * GET /contract
     * Mengembalikan kontrak data KST Cangar sesuai role user.
     */
    public static function get_contract(WP_REST_Request $request): WP_REST_Response {
        $role       = $request->get_param('_jwt_role') ?? 'publik';
        $permission = sanitize_text_field($request->get_param('permission') ?? 'r');

        // Semua data yang tersedia di KST Cangar
        $all_data = [
            // Booking
            [
                'name'        => 'Data Booking',
                'path'        => '/data/booking',
                'code'        => '1f0c9d2a-0001-6d7e-8f90-kstcangar0001',
                'iconUri'     => null,
                'description' => 'Data pemesanan layanan KST Cangar (glamping, café, camping ground)',
                'operations'  => self::get_operations($role, 'booking'),
                'params'      => [
                    ['key' => 'status',       'valueType' => 'string', 'options' => ['pending','confirmed','cancelled']],
                    ['key' => 'service_type', 'valueType' => 'string', 'options' => ['glamping','cafe','camping']],
                    ['key' => 'date',         'valueType' => 'datetime', 'options' => null],
                    ['key' => 'month',        'valueType' => 'string',   'options' => null],
                    ['key' => 'offset',       'valueType' => 'int',      'options' => null],
                    ['key' => 'limit',        'valueType' => 'int',      'options' => null],
                ],
                'dataType' => [
                    'typeName' => 'table',
                    'columns'  => [
                        ['colIdx' => 0, 'name' => 'Nama Customer',  'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 1, 'name' => 'No. HP',         'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 2, 'name' => 'Jenis Layanan',  'dataType' => ['typeName' => 'variant', 'variants' => [
                            ['index' => 0, 'variant' => 'glamping', 'semantic' => '#2e6cd1'],
                            ['index' => 1, 'variant' => 'cafe',     'semantic' => '#f0a500'],
                            ['index' => 2, 'variant' => 'camping',  'semantic' => '#12a85a'],
                        ]]],
                        ['colIdx' => 3, 'name' => 'Tanggal',        'dataType' => ['typeName' => 'datetime']],
                        ['colIdx' => 4, 'name' => 'Jumlah',         'dataType' => ['typeName' => 'number', 'unit' => 'orang']],
                        ['colIdx' => 5, 'name' => 'Status',         'dataType' => ['typeName' => 'variant', 'variants' => [
                            ['index' => 0, 'variant' => 'pending',   'semantic' => 'neutral'],
                            ['index' => 1, 'variant' => 'confirmed', 'semantic' => 'positive'],
                            ['index' => 2, 'variant' => 'cancelled', 'semantic' => 'negative'],
                        ]]],
                    ],
                ],
            ],

            // Keuangan
            [
                'name'        => 'Transaksi Keuangan',
                'path'        => '/data/keuangan',
                'code'        => '1f0c9d2a-0002-6d7e-8f90-kstcangar0002',
                'iconUri'     => null,
                'description' => 'Data pemasukan dan pengeluaran operasional KST Cangar',
                'operations'  => self::get_operations($role, 'keuangan'),
                'params'      => [
                    ['key' => 'type',   'valueType' => 'string', 'options' => ['INCOME','EXPENSE']],
                    ['key' => 'date',   'valueType' => 'datetime', 'options' => null],
                    ['key' => 'month',  'valueType' => 'string',   'options' => null],
                    ['key' => 'status', 'valueType' => 'string', 'options' => ['draft','validated','rejected']],
                    ['key' => 'offset', 'valueType' => 'int',      'options' => null],
                    ['key' => 'limit',  'valueType' => 'int',      'options' => null],
                ],
                'dataType' => [
                    'typeName' => 'table',
                    'columns'  => [
                        ['colIdx' => 0, 'name' => 'Jenis',      'dataType' => ['typeName' => 'variant', 'variants' => [
                            ['index' => 0, 'variant' => 'INCOME',  'semantic' => 'positive'],
                            ['index' => 1, 'variant' => 'EXPENSE', 'semantic' => 'negative'],
                        ]]],
                        ['colIdx' => 1, 'name' => 'Nominal',    'dataType' => ['typeName' => 'number', 'unit' => 'IDR']],
                        ['colIdx' => 2, 'name' => 'Kategori',   'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 3, 'name' => 'Keterangan', 'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 4, 'name' => 'Tanggal',    'dataType' => ['typeName' => 'datetime']],
                        ['colIdx' => 5, 'name' => 'Status',     'dataType' => ['typeName' => 'variant', 'variants' => [
                            ['index' => 0, 'variant' => 'draft',     'semantic' => 'neutral'],
                            ['index' => 1, 'variant' => 'validated', 'semantic' => 'positive'],
                            ['index' => 2, 'variant' => 'rejected',  'semantic' => 'negative'],
                        ]]],
                    ],
                ],
            ],

            // Rekap keuangan bulanan (time series)
            [
                'name'        => 'Rekap Keuangan Bulanan',
                'path'        => '/data/keuangan/rekap',
                'code'        => '1f0c9d2a-0003-6d7e-8f90-kstcangar0003',
                'iconUri'     => null,
                'description' => 'Rekap pemasukan dan pengeluaran per hari dalam satu bulan',
                'operations'  => ['read'],
                'params'      => [
                    ['key' => 'month', 'valueType' => 'string', 'options' => null],
                ],
                'dataType' => ['typeName' => 'timeSeries', 'unit' => 'IDR'],
            ],

            // Stok opname
            [
                'name'        => 'Stok Opname',
                'path'        => '/data/stok',
                'code'        => '1f0c9d2a-0004-6d7e-8f90-kstcangar0004',
                'iconUri'     => null,
                'description' => 'Data stok opname mingguan barang café KST Cangar',
                'operations'  => self::get_operations($role, 'stok'),
                'params'      => [
                    ['key' => 'week',    'valueType' => 'string', 'options' => null],
                    ['key' => 'item_id', 'valueType' => 'int',    'options' => null],
                ],
                'dataType' => [
                    'typeName' => 'table',
                    'columns'  => [
                        ['colIdx' => 0, 'name' => 'Nama Barang',  'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 1, 'name' => 'Satuan',       'dataType' => ['typeName' => 'text']],
                        ['colIdx' => 2, 'name' => 'Stok Awal',    'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 3, 'name' => 'Masuk',        'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 4, 'name' => 'Keluar',       'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 5, 'name' => 'Retur',        'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 6, 'name' => 'Stok Sistem',  'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 7, 'name' => 'Stok Fisik',   'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 8, 'name' => 'Selisih',      'dataType' => ['typeName' => 'number', 'unit' => null]],
                        ['colIdx' => 9, 'name' => 'Status',       'dataType' => ['typeName' => 'variant', 'variants' => [
                            ['index' => 0, 'variant' => 'draft',     'semantic' => 'neutral'],
                            ['index' => 1, 'variant' => 'validated', 'semantic' => 'positive'],
                            ['index' => 2, 'variant' => 'rejected',  'semantic' => 'negative'],
                        ]]],
                    ],
                ],
            ],

            // Summary dashboard
            [
                'name'        => 'Ringkasan Dashboard',
                'path'        => '/data/summary',
                'code'        => '1f0c9d2a-0005-6d7e-8f90-kstcangar0005',
                'iconUri'     => null,
                'description' => 'Ringkasan data operasional KST Cangar untuk dashboard pusat',
                'operations'  => ['read'],
                'params'      => [],
                'dataType'    => ['typeName' => 'text'],
            ],
        ];

        // Filter berdasarkan permission query param
        if ($permission === 'rw') {
            $all_data = array_filter($all_data, fn($d) =>
                in_array('read', $d['operations']) && in_array('write', $d['operations'])
            );
        }

        return self::ok([
            'version'  => self::CONTRACT_VERSION,
            'contract' => array_values($all_data),
        ]);
    }

    /**
     * Tentukan operasi yang diizinkan berdasarkan role dan modul.
     */
    private static function get_operations(string $role, string $module): array {
        $write_roles = ['administrator', 'admin_kst', "operator_$module"];

        return in_array($role, $write_roles)
            ? ['read', 'write']
            : ['read'];
    }

    // ══════════════════════════════════════════════════════
    // DATA ENDPOINTS
    // ══════════════════════════════════════════════════════

    public static function get_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $status       = sanitize_text_field($request->get_param('status') ?? '');
        $service_type = sanitize_text_field($request->get_param('service_type') ?? '');
        $date         = sanitize_text_field($request->get_param('date') ?? '');
        $month        = sanitize_text_field($request->get_param('month') ?? '');
        $offset       = max(0, (int)($request->get_param('offset') ?? 0));
        $limit        = min(50, max(1, (int)($request->get_param('limit') ?? 15)));

        $where = ['1=1']; $params = [];
        if ($status)       { $where[] = 'status = %s';                        $params[] = $status; }
        if ($service_type) { $where[] = 'service_type = %s';                  $params[] = $service_type; }
        if ($date)         { $where[] = 'date = %s';                           $params[] = $date; }
        if ($month)        { $where[] = "DATE_FORMAT(date,'%%Y-%%m') = %s";   $params[] = $month; }

        $where_sql = implode(' AND ', $where);

        $total = (int)($params
            ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE $where_sql", ...$params))
            : $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE $where_sql"));

        $query = "SELECT * FROM {$wpdb->prefix}kst_bookings WHERE $where_sql ORDER BY date DESC LIMIT %d OFFSET %d";
        $params_paginated = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, ...$params_paginated));

        // Format sebagai table sesuai kontrak
        $items = array_map(fn($row) => [
            'rowId'     => (string)$row->booking_id,
            'createdAt' => $row->created_at,
            'updatedAt' => null,
            'colValues' => [
                ['colIdx' => 0, 'value' => $row->customer_name],
                ['colIdx' => 1, 'value' => $row->customer_phone],
                ['colIdx' => 2, 'value' => $row->service_type],
                ['colIdx' => 3, 'value' => $row->date],
                ['colIdx' => 4, 'value' => (int)$row->quantity],
                ['colIdx' => 5, 'value' => $row->status],
            ],
        ], $rows);

        $data = [
            'typeName' => 'table',
            'offset'   => $offset,
            'limit'    => $limit,
            'hasNext'  => ($offset + $limit) < $total,
            'items'    => $items,
        ];

        return self::ok(self::data_container('1f0c9d2a-0001-6d7e-8f90-kstcangar0001', $data));
    }

    public static function get_keuangan(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $type   = sanitize_text_field($request->get_param('type') ?? '');
        $date   = sanitize_text_field($request->get_param('date') ?? '');
        $month  = sanitize_text_field($request->get_param('month') ?? '');
        $status = sanitize_text_field($request->get_param('status') ?? '');
        $offset = max(0, (int)($request->get_param('offset') ?? 0));
        $limit  = min(50, max(1, (int)($request->get_param('limit') ?? 15)));

        $where = ['1=1']; $params = [];
        if ($type)   { $where[] = 'type = %s';                         $params[] = $type; }
        if ($date)   { $where[] = 'date = %s';                          $params[] = $date; }
        if ($month)  { $where[] = "DATE_FORMAT(date,'%%Y-%%m') = %s";  $params[] = $month; }
        if ($status) { $where[] = 'status = %s';                        $params[] = $status; }

        $where_sql = implode(' AND ', $where);

        $total = (int)($params
            ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}kst_finances WHERE $where_sql", ...$params))
            : $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kst_finances WHERE $where_sql"));

        $query = "SELECT * FROM {$wpdb->prefix}kst_finances WHERE $where_sql ORDER BY date DESC LIMIT %d OFFSET %d";
        $rows  = $wpdb->get_results($wpdb->prepare($query, ...array_merge($params, [$limit, $offset])));

        $items = array_map(fn($row) => [
            'rowId'     => (string)$row->finance_id,
            'createdAt' => $row->created_at,
            'updatedAt' => null,
            'colValues' => [
                ['colIdx' => 0, 'value' => $row->type],
                ['colIdx' => 1, 'value' => (float)$row->amount],
                ['colIdx' => 2, 'value' => $row->category],
                ['colIdx' => 3, 'value' => $row->description],
                ['colIdx' => 4, 'value' => $row->date],
                ['colIdx' => 5, 'value' => $row->status],
            ],
        ], $rows);

        $data = [
            'typeName' => 'table',
            'offset'   => $offset,
            'limit'    => $limit,
            'hasNext'  => ($offset + $limit) < $total,
            'items'    => $items,
        ];

        return self::ok(self::data_container('1f0c9d2a-0002-6d7e-8f90-kstcangar0002', $data));
    }

    public static function get_keuangan_rekap(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $month = sanitize_text_field($request->get_param('month') ?? date('Y-m'));

        $rekap = $wpdb->get_results($wpdb->prepare("
            SELECT date,
                   SUM(CASE WHEN type='INCOME'  THEN amount ELSE 0 END) AS income,
                   SUM(CASE WHEN type='EXPENSE' THEN amount ELSE 0 END) AS expense
            FROM {$wpdb->prefix}kst_finances
            WHERE DATE_FORMAT(date,'%%Y-%%m') = %s AND status = 'validated'
            GROUP BY date ORDER BY date ASC
        ", $month));

        // Format sebagai timeSeries — net per hari
        $values = array_map(fn($row) => [
            'timestamp' => $row->date . 'T00:00:00+07:00',
            'value'     => (float)$row->income - (float)$row->expense,
        ], $rekap);

        $range_start = !empty($values) ? $values[0]['timestamp'] : date('Y-m-01T00:00:00+07:00');
        $range_end   = !empty($values) ? end($values)['timestamp'] : date('Y-m-t T00:00:00+07:00');

        $data = [
            'typeName'   => 'timeSeries',
            'rangeStart' => $range_start,
            'rangeEnd'   => $range_end,
            'unit'       => 'IDR',
            'hasMore'    => false,
            'value'      => $values,
        ];

        return self::ok(self::data_container('1f0c9d2a-0003-6d7e-8f90-kstcangar0003', $data));
    }

    public static function get_stok(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $week    = sanitize_text_field($request->get_param('week') ?? KSTCangar_Helpers::get_week());
        $item_id = (int)($request->get_param('item_id') ?? 0);
        $offset  = max(0, (int)($request->get_param('offset') ?? 0));
        $limit   = min(50, max(1, (int)($request->get_param('limit') ?? 15)));

        $where = ['o.week = %s']; $params = [$week];
        if ($item_id) { $where[] = 'o.item_id = %d'; $params[] = $item_id; }
        $where_sql = implode(' AND ', $where);

        $total = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kst_stock_opname o WHERE $where_sql", ...$params));

        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT o.*, i.name AS item_name, i.unit
            FROM {$wpdb->prefix}kst_stock_opname o
            JOIN {$wpdb->prefix}kst_items i ON o.item_id = i.item_id
            WHERE $where_sql ORDER BY i.name ASC LIMIT %d OFFSET %d
        ", ...array_merge($params, [$limit, $offset])));

        $items = array_map(fn($row) => [
            'rowId'     => (string)$row->opname_id,
            'createdAt' => $row->created_at,
            'updatedAt' => null,
            'colValues' => [
                ['colIdx' => 0, 'value' => $row->item_name],
                ['colIdx' => 1, 'value' => $row->unit],
                ['colIdx' => 2, 'value' => (int)$row->initial_stock],
                ['colIdx' => 3, 'value' => (int)$row->stock_in],
                ['colIdx' => 4, 'value' => (int)$row->stock_out],
                ['colIdx' => 5, 'value' => (int)$row->stock_return],
                ['colIdx' => 6, 'value' => (int)$row->system_stock],
                ['colIdx' => 7, 'value' => (int)$row->physical_stock],
                ['colIdx' => 8, 'value' => (int)$row->difference],
                ['colIdx' => 9, 'value' => $row->status],
            ],
        ], $rows);

        $data = [
            'typeName' => 'table',
            'offset'   => $offset,
            'limit'    => $limit,
            'hasNext'  => ($offset + $limit) < $total,
            'items'    => $items,
        ];

        return self::ok(self::data_container('1f0c9d2a-0004-6d7e-8f90-kstcangar0004', $data));
    }

    public static function get_stok_items(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT item_id, name, unit FROM {$wpdb->prefix}kst_items WHERE is_active = 1 ORDER BY name ASC"
        );
        return self::ok($rows);
    }

    public static function get_summary(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $today = date('Y-m-d');
        $month = date('Y-m');

        $booking_today   = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE date=%s AND status!='cancelled'", $today));
        $booking_pending = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE status='pending'");
        $booking_month   = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kst_bookings WHERE DATE_FORMAT(date,'%%Y-%%m')=%s AND status='confirmed'", $month));
        $income_today    = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}kst_finances WHERE date=%s AND type='INCOME' AND status='validated'", $today));
        $expense_today   = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}kst_finances WHERE date=%s AND type='EXPENSE' AND status='validated'", $today));
        $income_month    = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}kst_finances WHERE DATE_FORMAT(date,'%%Y-%%m')=%s AND type='INCOME' AND status='validated'", $month));
        $expense_month   = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}kst_finances WHERE DATE_FORMAT(date,'%%Y-%%m')=%s AND type='EXPENSE' AND status='validated'", $month));

        $summary = [
            'generated_at' => self::timestamp(),
            'booking'      => ['today' => $booking_today, 'pending' => $booking_pending, 'confirmed_month' => $booking_month],
            'keuangan'     => [
                'income_today'  => $income_today,  'expense_today'  => $expense_today,
                'net_today'     => $income_today - $expense_today,
                'income_month'  => $income_month,  'expense_month'  => $expense_month,
                'net_month'     => $income_month - $expense_month,
            ],
        ];

        return self::ok(self::data_container('1f0c9d2a-0005-6d7e-8f90-kstcangar0005', [
            'typeName' => 'text',
            'value'    => json_encode($summary),
        ]));
    }

    // ══════════════════════════════════════════════════════
    // QUERY AGREGAT
    // ══════════════════════════════════════════════════════

    /**
     * POST /query
     * Batch query multiple data dalam satu request.
     */
    public static function query(WP_REST_Request $request): WP_REST_Response {
        $body    = $request->get_json_params();
        $queries = $body['queries'] ?? [];

        if (!is_array($queries) || empty($queries)) {
            return self::err(400, 'Field queries wajib diisi dan berupa array.');
        }

        if (count($queries) > 80) {
            return self::err(413, 'Maksimal 80 query per request.');
        }

        // Map code → endpoint handler
        $code_map = [
            '1f0c9d2a-0001-6d7e-8f90-kstcangar0001' => 'get_booking',
            '1f0c9d2a-0002-6d7e-8f90-kstcangar0002' => 'get_keuangan',
            '1f0c9d2a-0003-6d7e-8f90-kstcangar0003' => 'get_keuangan_rekap',
            '1f0c9d2a-0004-6d7e-8f90-kstcangar0004' => 'get_stok',
            '1f0c9d2a-0005-6d7e-8f90-kstcangar0005' => 'get_summary',
        ];

        $results = [];

        foreach ($queries as $q) {
            $code   = $q['code'] ?? '';
            $params = $q['params'] ?? [];

            if (!isset($code_map[$code])) {
                $results[] = [
                    'code'      => $code,
                    'createdAt' => self::timestamp(),
                    'updatedAt' => null,
                    'data'      => null,
                    'error'     => ['code' => 404, 'message' => "Data dengan code '$code' tidak ditemukan."],
                ];
                continue;
            }

            // Buat sub-request dengan params dari query
            $sub_request = new WP_REST_Request('GET');
            foreach ($params as $key => $value) {
                $sub_request->set_param($key, $value);
            }
            // Teruskan JWT payload
            $sub_request->set_param('_jwt_payload', $request->get_param('_jwt_payload'));
            $sub_request->set_param('_jwt_role',    $request->get_param('_jwt_role'));

            $handler  = $code_map[$code];
            $response = self::$handler($sub_request);
            $body_arr = $response->get_data();

            if ($response->get_status() !== 200) {
                $results[] = [
                    'code'      => $code,
                    'createdAt' => self::timestamp(),
                    'updatedAt' => null,
                    'data'      => null,
                    'error'     => $body_arr['error'] ?? ['code' => 500, 'message' => 'Internal error.'],
                ];
            } else {
                $container = $body_arr['response'];
                $results[] = [
                    'code'      => $container['code'],
                    'createdAt' => $container['createdAt'],
                    'updatedAt' => $container['updatedAt'],
                    'data'      => $container['data'],
                ];
            }
        }

        return self::ok($results);
    }
}
