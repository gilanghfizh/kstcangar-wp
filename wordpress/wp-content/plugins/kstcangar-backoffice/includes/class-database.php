<?php
defined('ABSPATH') || exit;

class KSTCangar_Database {

    /**
     * Dipanggil saat plugin diaktifkan.
     * Membuat semua tabel yang dibutuhkan sistem.
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Tabel: items ──────────────────────────────────
        // Master data barang (nama barang, satuan)
        $sql_items = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kst_items (
            item_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(100) NOT NULL,
            unit       VARCHAR(20)  NOT NULL DEFAULT 'pcs',
            is_active  TINYINT(1)   NOT NULL DEFAULT 1,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (item_id)
        ) $charset;";

        // ── Tabel: stock_movements ────────────────────────
        // Catatan barang masuk/keluar/retur harian
        $sql_movements = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kst_stock_movements (
            movement_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            item_id     INT UNSIGNED  NOT NULL,
            date        DATE          NOT NULL,
            type        ENUM('IN','OUT','RETURN') NOT NULL,
            quantity    INT           NOT NULL DEFAULT 0,
            description TEXT,
            created_by  BIGINT UNSIGNED NOT NULL,
            created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (movement_id),
            KEY idx_item_date (item_id, date)
        ) $charset;";

        // ── Tabel: stock_opname ───────────────────────────
        // Rekap mingguan stok fisik vs sistem + selisih
        $sql_opname = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kst_stock_opname (
            opname_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id       INT UNSIGNED NOT NULL,
            week          VARCHAR(20)  NOT NULL COMMENT 'Format: YYYY-WXX',
            initial_stock INT          NOT NULL DEFAULT 0,
            stock_in      INT          NOT NULL DEFAULT 0,
            stock_out     INT          NOT NULL DEFAULT 0,
            stock_return  INT          NOT NULL DEFAULT 0,
            system_stock  INT          NOT NULL DEFAULT 0 COMMENT 'Dihitung otomatis',
            physical_stock INT         NOT NULL DEFAULT 0,
            difference    INT          NOT NULL DEFAULT 0 COMMENT 'system - physical',
            note          TEXT,
            status        ENUM('draft','validated','rejected') NOT NULL DEFAULT 'draft',
            validated_by  BIGINT UNSIGNED,
            validated_at  DATETIME,
            created_by    BIGINT UNSIGNED NOT NULL,
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (opname_id),
            UNIQUE KEY unique_item_week (item_id, week)
        ) $charset;";

        // ── Tabel: bookings ───────────────────────────────
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kst_bookings (
            booking_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_type ENUM('glamping','cafe','camping') NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20),
            date         DATE         NOT NULL,
            quantity     INT          NOT NULL DEFAULT 1,
            status       ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
            notes        TEXT,
            created_by   BIGINT UNSIGNED NOT NULL,
            validated_by BIGINT UNSIGNED,
            validated_at DATETIME,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (booking_id),
            KEY idx_date_status (date, status)
        ) $charset;";

        // ── Tabel: finances ───────────────────────────────
        $sql_finances = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kst_finances (
            finance_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type        ENUM('INCOME','EXPENSE') NOT NULL,
            amount      DECIMAL(12,2) NOT NULL,
            category    VARCHAR(50),
            description TEXT,
            date        DATE         NOT NULL,
            status      ENUM('draft','validated','rejected') NOT NULL DEFAULT 'draft',
            validated_by BIGINT UNSIGNED,
            validated_at DATETIME,
            created_by  BIGINT UNSIGNED NOT NULL,
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (finance_id),
            KEY idx_date_type (date, type)
        ) $charset;";

        dbDelta($sql_items);
        dbDelta($sql_movements);
        dbDelta($sql_opname);
        dbDelta($sql_bookings);
        dbDelta($sql_finances);
    }
}
