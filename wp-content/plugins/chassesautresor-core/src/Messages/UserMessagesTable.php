<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/**
 * Install and upgrade the user messages database table.
 */
class UserMessagesTable
{
    public const SCHEMA_VERSION = '1';

    private const SCHEMA_VERSION_OPTION = 'chassesautresor_core_user_messages_schema_version';

    /**
     * Install or update the user messages table without deleting existing data.
     */
    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'user_messages';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            message LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL,
            locale VARCHAR(10) NULL,
            KEY user_id (user_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION);
    }

    /**
     * Apply schema changes after a plugin update when activation does not run.
     */
    public static function maybeUpgrade(): void
    {
        if (get_option(self::SCHEMA_VERSION_OPTION) === self::SCHEMA_VERSION) {
            return;
        }

        self::install();
    }
}
