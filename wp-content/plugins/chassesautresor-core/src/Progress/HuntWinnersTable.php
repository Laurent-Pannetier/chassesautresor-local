<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Install and upgrade the hunt winners table.
 */
class HuntWinnersTable
{
    public const SCHEMA_VERSION = 1;

    private const VERSION_OPTION = 'cat_hunt_winners_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'chasse_winners';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id INT NOT NULL AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            chasse_id BIGINT NOT NULL,
            date_win DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_chasse (user_id, chasse_id),
            KEY chasse_id (chasse_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        if ((int) get_option(self::VERSION_OPTION, 0) < self::SCHEMA_VERSION) {
            self::install();
        }
    }
}
