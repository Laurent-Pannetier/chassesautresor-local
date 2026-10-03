<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Install and upgrade the active retry-delay table. */
final class RiddleRetryTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_riddle_retry_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'enigme_delais_soumission';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            user_id BIGINT UNSIGNED NOT NULL,
            enigme_id BIGINT UNSIGNED NOT NULL,
            retry_at_utc DATETIME NOT NULL,
            source_tentative_uid VARCHAR(64) NULL,
            updated_at_utc DATETIME NOT NULL,
            PRIMARY KEY  (user_id, enigme_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        if (get_option(self::VERSION_OPTION) !== self::SCHEMA_VERSION) {
            self::install();
        }
    }
}
