<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Install and upgrade the hint unlock ledger table.
 */
class HintUnlocksTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_indices_deblocages_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'indices_deblocages';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            indice_id BIGINT UNSIGNED NOT NULL,
            chasse_id BIGINT UNSIGNED NULL,
            enigme_id BIGINT UNSIGNED NULL,
            points_depenses INT UNSIGNED NOT NULL DEFAULT 0,
            date_deblocage DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_indice (user_id, indice_id),
            KEY indice_id (indice_id),
            KEY chasse_id (chasse_id),
            KEY enigme_id (enigme_id)
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
