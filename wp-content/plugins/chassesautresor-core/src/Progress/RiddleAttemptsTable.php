<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Install and upgrade the riddle attempts table.
 */
class RiddleAttemptsTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_enigme_tentatives_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'enigme_tentatives';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tentative_uid VARCHAR(64) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            enigme_id BIGINT UNSIGNED NOT NULL,
            etape_id BIGINT UNSIGNED NULL,
            reponse_saisie TEXT NULL,
            resultat ENUM('bon','variante','faux','attente') NOT NULL DEFAULT 'attente',
            points_utilises INT UNSIGNED NULL DEFAULT 0,
            date_tentative DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
            ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            traitee TINYINT(1) NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY tentative_uid (tentative_uid),
            KEY user_enigme (user_id, enigme_id),
            KEY etape_id (etape_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        if (get_option(self::VERSION_OPTION) === self::SCHEMA_VERSION) {
            return;
        }

        self::install();
    }
}
