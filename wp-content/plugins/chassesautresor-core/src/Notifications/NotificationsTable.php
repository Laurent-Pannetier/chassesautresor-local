<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Install and upgrade notification events and their per-user delivery state. */
final class NotificationsTable
{
    public const SCHEMA_VERSION = '3';

    private const VERSION_OPTION = 'cat_notifications_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $notifications = $wpdb->prefix . 'notifications';
        $recipients = $wpdb->prefix . 'notification_recipients';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$notifications} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(40) NOT NULL,
            source_type VARCHAR(40) NOT NULL,
            source_id BIGINT UNSIGNED NULL,
            title VARCHAR(190) NOT NULL,
            body TEXT NOT NULL,
            priority SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            channel VARCHAR(20) NOT NULL DEFAULT 'center',
            event_key VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_key (event_key),
            KEY source (source_type, source_id),
            KEY created_at (created_at)
        ) {$charset};
        CREATE TABLE {$recipients} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            notification_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NULL,
            overlay_claimed_at DATETIME NULL,
            overlay_claim_token VARCHAR(64) NULL,
            overlay_seen_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY notification_user (notification_id, user_id),
            KEY user_read (user_id, read_at),
            KEY user_overlay (user_id, overlay_seen_at)
        ) {$charset};";

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
