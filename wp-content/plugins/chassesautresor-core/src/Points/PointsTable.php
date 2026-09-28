<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/**
 * Install and upgrade the points ledger table.
 */
class PointsTable
{
    public const SCHEMA_VERSION = '1';

    private const SCHEMA_VERSION_OPTION = 'chassesautresor_core_points_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'user_points';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            balance INT UNSIGNED NOT NULL DEFAULT 0,
            points INT NOT NULL DEFAULT 0,
            amount_eur DECIMAL(10,2) NULL,
            reason VARCHAR(255) NOT NULL DEFAULT '',
            origin_type ENUM('admin','chasse','enigme','indice','tentative','achat','conversion') DEFAULT 'admin',
            origin_id BIGINT UNSIGNED NULL,
            request_status ENUM('pending','approved','paid','refused','cancelled') DEFAULT 'pending',
            request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            settlement_date DATETIME NULL,
            cancelled_date DATETIME NULL,
            cancellation_reason VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        if (get_option(self::SCHEMA_VERSION_OPTION) === self::SCHEMA_VERSION) {
            return;
        }

        self::install();
    }
}
