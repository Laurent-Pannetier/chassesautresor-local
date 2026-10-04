<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Install and upgrade the engagements table.
 */
class EngagementsTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_engagements_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'engagements';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            enigme_id BIGINT UNSIGNED NULL,
            chasse_id BIGINT NULL,
            indice_id BIGINT UNSIGNED NULL,
            date_engagement DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY enigme_user (enigme_id, user_id),
            KEY chasse_id (chasse_id),
            UNIQUE KEY user_indice (user_id, indice_id),
            KEY indice_id (indice_id)
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
