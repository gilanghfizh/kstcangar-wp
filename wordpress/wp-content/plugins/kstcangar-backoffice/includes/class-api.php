<?php
defined('ABSPATH') || exit;

class KSTCangar_API {

    const KST_IDENTIFIER = 'kst-cangar';

    const CONTRACT_VERSION = '0.0.1';

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {

        $namespace = 'kstcangar/v1';

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

        register_rest_route($namespace, '/health', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'health_check'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/contract', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_contract'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/data/booking', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get_booking'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'create_booking'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
        ]);

        register_rest_route($namespace, '/data/booking/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [self::class, 'update_booking'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'delete_booking'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
        ]);

        register_rest_route($namespace, '/data/keuangan', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_keuangan'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/data/keuangan/rekap', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_keuangan_rekap'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/data/stok', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get_stok'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'create_stok'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
        ]);

        register_rest_route($namespace, '/data/stok/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [self::class, 'update_stok'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'delete_stok'],
                'permission_callback' => [self::class, 'check_auth'],
            ],
        ]);

        register_rest_route($namespace, '/data/stok/items', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stok_items'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/data/summary', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_summary'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        register_rest_route($namespace, '/query', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'query'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    private static function timestamp(): string {
        return current_time('c'); 
    }

    private static function ok($response_data, int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'timestamp' => self::timestamp(),
            'response'  => $response_data,
        ], $status);
    }

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

    private static function data_container(string $code, $data, ?string $created_at = null, ?string $updated_at = null): array {
        return [
            'code'      => $code,
            'createdAt' => $created_at ?? self::timestamp(),
            'updatedAt' => $updated_at,
            'data'      => $data,
        ];
    }

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

        $kst_roles = ['administrator','admin_kst','operator_stok','operator_booking','operator_keuangan','manajemen'];
        $user_role = 'publik';
        foreach ($user->roles as $role) {
            if (in_array($role, $kst_roles)) {
                $user_role = $role;
                break;
            }
        }

        $exp = time() + 28800;
        $access_token = KSTCangar_JWT::generate([
            'sub'      => (string)$user->ID,
            'username' => $user->user_login,
            'name'     => $user->display_name,
            'roles'    => [self::KST_IDENTIFIER => [$user_role]],
            'iss'      => get_site_url(),
            'aud'      => get_site_url(),
        ], 28800);

        $refresh_token     = bin2hex(random_bytes(32));
        $refresh_token_hash = hash('sha256', $refresh_token);
        update_user_meta($user->ID, 'kstcangar_refresh_token', $refresh_token_hash);
        update_user_meta($user->ID, 'kstcangar_refresh_exp',   time() + 604800);

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

    public static function logout(WP_REST_Request $request): WP_REST_Response {
        $refresh_token = $_COOKIE['refresh_token'] ?? '';

        if (!$refresh_token) {
            return self::err(400, 'Tidak ada refresh_token pada cookie request.');
        }

        $payload = $request->get_param('_jwt_payload');
        $user_id = $payload['sub'] ?? 0;

        delete_user_meta($user_id, 'kstcangar_refresh_token');
        delete_user_meta($user_id, 'kstcangar_refresh_exp');

        setcookie('refresh_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/wp-json/kstcangar/v1/auth/refresh',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        return self::ok([]);
    }

    public static function refresh_token(WP_REST_Request $request): WP_REST_Response {
        $refresh_token = $_COOKIE['refresh_token'] ?? '';

        if (!$refresh_token) {
            return self::err(400, 'Tidak ada refresh_token pada cookie request.');
        }

        $refresh_hash = hash('sha256', $refresh_token);

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

        $kst_roles = ['administrator','admin_kst','operator_stok','operator_booking','operator_keuangan','manajemen'];
        $user_role = 'publik';
        foreach ($user->roles as $role) {
            if (in_array($role, $kst_roles)) { $user_role = $role; break; }
        }

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

    public static function health_check(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

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

    public static function get_contract(WP_REST_Request $request): WP_REST_Response {
        $role       = $request->get_param('_jwt_role') ?? 'publik';
        $permission = sanitize_text_field($request->get_param('permission') ?? 'r');

        $all_data = [
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

    private static function get_operations(string $role, string $module): array {
        $write_roles = ['administrator', 'admin_kst', "operator_$module"];

        return in_array($role, $write_roles)
            ? ['read', 'write']
            : ['read'];
    }

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

    public static function create_stok(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $body = $request->get_json_params();
        $user_id = (int)($request->get_param('_jwt_user_id') ?? 0);

        $nama_barang  = sanitize_text_field($body['nama_barang'] ?? '');
        $satuan       = sanitize_text_field($body['satuan'] ?? 'pcs');
        $stok_awal    = (int)($body['stok_awal'] ?? 0);
        $total_masuk  = (int)($body['total_masuk'] ?? 0);
        $total_keluar = (int)($body['total_keluar'] ?? 0);
        $retur        = (int)($body['retur'] ?? 0);
        $stok_fisik   = (int)($body['stok_fisik'] ?? 0);

        if (!$nama_barang || !$satuan) {
            return self::err(400, 'Nama barang dan satuan wajib diisi.');
        }

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT item_id FROM {$wpdb->prefix}kst_items WHERE name = %s AND is_active = 1",
            $nama_barang
        ));

        if (!$item) {
            $wpdb->insert($wpdb->prefix . 'kst_items', [
                'name' => $nama_barang,
                'unit' => $satuan,
            ]);
            $item_id = $wpdb->insert_id;
        } else {
            $item_id = (int)$item->item_id;
        }

        $week         = KSTCangar_Helpers::get_week();
        $system_stock = $stok_awal + $total_masuk - $total_keluar + $retur;
        $difference   = $system_stock - $stok_fisik;

        $data = [
            'item_id'        => $item_id,
            'week'           => $week,
            'initial_stock'  => $stok_awal,
            'stock_in'       => $total_masuk,
            'stock_out'      => $total_keluar,
            'stock_return'   => $retur,
            'system_stock'   => $system_stock,
            'physical_stock' => $stok_fisik,
            'difference'     => $difference,
            'note'           => sanitize_text_field($body['ket_selisih'] ?? ''),
            'status'         => 'draft',
            'created_by'     => $user_id ?: get_current_user_id(),
        ];

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT opname_id FROM {$wpdb->prefix}kst_stock_opname WHERE item_id = %d AND week = %s",
            $item_id, $week
        ));

        if ($existing) {
            $wpdb->update($wpdb->prefix . 'kst_stock_opname', $data, ['opname_id' => $existing]);
            $opname_id = (int)$existing;
        } else {
            $wpdb->insert($wpdb->prefix . 'kst_stock_opname', $data);
            $opname_id = $wpdb->insert_id;
        }

        return self::ok([
            'message'  => 'Data stok berhasil disimpan.',
            'opnameId' => (string)$opname_id,
        ], 201);
    }

    public static function update_stok(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $opname_id = (int)$request->get_param('id');
        $body      = $request->get_json_params();

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kst_stock_opname WHERE opname_id = %d",
            $opname_id
        ));

        if (!$existing) {
            return self::err(404, 'Data stok tidak ditemukan.');
        }

        $stok_awal    = (int)($body['stok_awal'] ?? $existing->initial_stock);
        $total_masuk  = (int)($body['total_masuk'] ?? $existing->stock_in);
        $total_keluar = (int)($body['total_keluar'] ?? $existing->stock_out);
        $retur        = (int)($body['retur'] ?? $existing->stock_return);
        $stok_fisik   = (int)($body['stok_fisik'] ?? $existing->physical_stock);
        $system_stock = $stok_awal + $total_masuk - $total_keluar + $retur;
        $difference   = $system_stock - $stok_fisik;

        $data = [
            'initial_stock'  => $stok_awal,
            'stock_in'       => $total_masuk,
            'stock_out'      => $total_keluar,
            'stock_return'   => $retur,
            'system_stock'   => $system_stock,
            'physical_stock' => $stok_fisik,
            'difference'     => $difference,
            'note'           => sanitize_text_field($body['ket_selisih'] ?? $existing->note ?? ''),
        ];

        $nama_barang = sanitize_text_field($body['nama_barang'] ?? '');
        $satuan      = sanitize_text_field($body['satuan'] ?? '');
        if ($nama_barang || $satuan) {
            $item_update = [];
            if ($nama_barang) $item_update['name'] = $nama_barang;
            if ($satuan)      $item_update['unit'] = $satuan;
            $wpdb->update($wpdb->prefix . 'kst_items', $item_update, ['item_id' => $existing->item_id]);
        }

        $wpdb->update($wpdb->prefix . 'kst_stock_opname', $data, ['opname_id' => $opname_id]);

        return self::ok(['message' => 'Data stok berhasil diperbarui.']);
    }

    public static function delete_stok(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $opname_id = (int)$request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT opname_id FROM {$wpdb->prefix}kst_stock_opname WHERE opname_id = %d",
            $opname_id
        ));

        if (!$existing) {
            return self::err(404, 'Data stok tidak ditemukan.');
        }

        $wpdb->delete($wpdb->prefix . 'kst_stock_opname', ['opname_id' => $opname_id]);

        return self::ok(['message' => 'Data stok berhasil dihapus.']);
    }

    public static function create_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $body    = $request->get_json_params();
        $user_id = (int)($request->get_param('_jwt_user_id') ?? 0);

        $customer_name  = sanitize_text_field($body['nama_customer'] ?? '');
        $customer_phone = sanitize_text_field($body['no_hp'] ?? '');
        $service_type   = sanitize_text_field($body['layanan'] ?? '');
        $date           = sanitize_text_field($body['tanggal_checkin'] ?? '');
        $quantity       = max(1, (int)($body['jumlah_tamu'] ?? 1));
        $status         = sanitize_text_field($body['status_bayar'] ?? 'pending');
        $notes          = sanitize_textarea_field($body['additional_needs'] ?? '');

        if (!$customer_name || !$date || !$service_type) {
            return self::err(400, 'Nama customer, tanggal, dan jenis layanan wajib diisi.');
        }

        $service_map = [
            'glamping deluxe' => 'glamping',
            'glamping long'   => 'glamping',
            'camping ground'  => 'camping',
            'glamping'        => 'glamping',
            'cafe'            => 'cafe',
            'camping'         => 'camping',
        ];
        $service_normalized = $service_map[strtolower($service_type)] ?? 'glamping';

        $status_map = [
            'lunas'       => 'confirmed',
            'confirmed'   => 'confirmed',
            'belum lunas' => 'pending',
            'pending'     => 'pending',
            'dp'          => 'pending',
            'batal'       => 'cancelled',
            'cancelled'   => 'cancelled',
        ];
        $status_normalized = $status_map[strtolower($status)] ?? 'pending';

        $data = [
            'service_type'   => $service_normalized,
            'customer_name'  => $customer_name,
            'customer_phone' => $customer_phone,
            'date'           => $date,
            'quantity'       => $quantity,
            'status'         => $status_normalized,
            'notes'          => $notes,
            'created_by'     => $user_id ?: get_current_user_id(),
        ];

        $wpdb->insert($wpdb->prefix . 'kst_bookings', $data);
        $booking_id = $wpdb->insert_id;

        if (!$booking_id) {
            return self::err(500, 'Gagal menyimpan booking: ' . $wpdb->last_error);
        }

        return self::ok([
            'message'   => 'Booking berhasil disimpan.',
            'bookingId' => (string)$booking_id,
        ], 201);
    }

    public static function update_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $booking_id = (int)$request->get_param('id');
        $body       = $request->get_json_params();

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kst_bookings WHERE booking_id = %d",
            $booking_id
        ));

        if (!$existing) {
            return self::err(404, 'Booking tidak ditemukan.');
        }

        $service_type = sanitize_text_field($body['layanan'] ?? $existing->service_type);
        $status       = sanitize_text_field($body['status_bayar'] ?? $existing->status);

        $service_map = [
            'glamping deluxe' => 'glamping',
            'glamping long'   => 'glamping',
            'camping ground'  => 'camping',
            'glamping'        => 'glamping',
            'cafe'            => 'cafe',
            'camping'         => 'camping',
        ];
        $service_normalized = $service_map[strtolower($service_type)] ?? $existing->service_type;

        $status_map = [
            'lunas'       => 'confirmed',
            'confirmed'   => 'confirmed',
            'belum lunas' => 'pending',
            'pending'     => 'pending',
            'dp'          => 'pending',
            'batal'       => 'cancelled',
            'cancelled'   => 'cancelled',
        ];
        $status_normalized = $status_map[strtolower($status)] ?? $existing->status;

        $data = [
            'customer_name'  => sanitize_text_field($body['nama_customer'] ?? $existing->customer_name),
            'customer_phone' => sanitize_text_field($body['no_hp'] ?? $existing->customer_phone),
            'service_type'   => $service_normalized,
            'date'           => sanitize_text_field($body['tanggal_checkin'] ?? $existing->date),
            'quantity'       => max(1, (int)($body['jumlah_tamu'] ?? $existing->quantity)),
            'status'         => $status_normalized,
            'notes'          => sanitize_textarea_field($body['additional_needs'] ?? $existing->notes ?? ''),
        ];

        $wpdb->update($wpdb->prefix . 'kst_bookings', $data, ['booking_id' => $booking_id]);

        return self::ok(['message' => 'Booking berhasil diperbarui.']);
    }

    public static function delete_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $booking_id = (int)$request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT booking_id FROM {$wpdb->prefix}kst_bookings WHERE booking_id = %d",
            $booking_id
        ));

        if (!$existing) {
            return self::err(404, 'Booking tidak ditemukan.');
        }

        $wpdb->delete($wpdb->prefix . 'kst_bookings', ['booking_id' => $booking_id]);

        return self::ok(['message' => 'Booking berhasil dihapus.']);
    }

    public static function query(WP_REST_Request $request): WP_REST_Response {
        $body    = $request->get_json_params();
        $queries = $body['queries'] ?? [];

        if (!is_array($queries) || empty($queries)) {
            return self::err(400, 'Field queries wajib diisi dan berupa array.');
        }

        if (count($queries) > 80) {
            return self::err(413, 'Maksimal 80 query per request.');
        }

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

            $sub_request = new WP_REST_Request('GET');
            foreach ($params as $key => $value) {
                $sub_request->set_param($key, $value);
            }
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
