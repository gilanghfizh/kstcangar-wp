<?php
defined('ABSPATH') || exit;

/**
 * KSTCangar_JWT
 * Implementasi JWT manual tanpa library eksternal.
 * Algoritma: HS256 (HMAC-SHA256)
 */
class KSTCangar_JWT {

    /**
     * Secret key untuk signing JWT.
     * Diambil dari WordPress secret key agar unik per instalasi.
     */
    private static function get_secret(): string {
        return defined('AUTH_KEY') ? AUTH_KEY : 'kstcangar-fallback-secret-key-2026';
    }

    /**
     * Generate JWT token.
     *
     * @param array $payload Data yang ingin disimpan di token
     * @param int   $expires Durasi valid dalam detik (default: 8 jam)
     */
    public static function generate(array $payload, int $expires = 28800): string {
        $header = self::base64url_encode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payload['iat'] = time();           // issued at
        $payload['exp'] = time() + $expires; // expiry

        $payload_encoded = self::base64url_encode(json_encode($payload));

        $signature = self::base64url_encode(
            hash_hmac('sha256', "$header.$payload_encoded", self::get_secret(), true)
        );

        return "$header.$payload_encoded.$signature";
    }

    /**
     * Verifikasi dan decode JWT token.
     *
     * @return array|null Payload jika valid, null jika tidak valid/expired
     */
    public static function verify(string $token): ?array {
        $parts = explode('.', $token);

        if (count($parts) !== 3) return null;

        [$header, $payload_encoded, $signature] = $parts;

        // Verifikasi signature
        $expected_sig = self::base64url_encode(
            hash_hmac('sha256', "$header.$payload_encoded", self::get_secret(), true)
        );

        if (!hash_equals($expected_sig, $signature)) return null;

        // Decode payload
        $payload = json_decode(self::base64url_decode($payload_encoded), true);

        if (!$payload) return null;

        // Cek expiry
        if (isset($payload['exp']) && time() > $payload['exp']) return null;

        return $payload;
    }

    /**
     * Ambil token dari Authorization header.
     * Format: "Authorization: Bearer {token}"
     */
    public static function get_token_from_header(): ?string {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (!$auth_header) {
            // Fallback untuk beberapa konfigurasi Apache
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // ── Helpers ───────────────────────────────────────────

    private static function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
