<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Install and upgrade the immutable trophy attribution ledger. */
final class TrophyAttributionsTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_trophy_attributions_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'trophy_attributions';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            trophy_id BIGINT UNSIGNED NOT NULL,
            recipient_type VARCHAR(10) NOT NULL,
            recipient_id BIGINT UNSIGNED NOT NULL,
            actor_user_id BIGINT UNSIGNED NOT NULL,
            context_type VARCHAR(40) NULL,
            context_id BIGINT UNSIGNED NULL,
            event_key VARCHAR(190) NOT NULL,
            context LONGTEXT NULL,
            awarded_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY trophy_recipient (trophy_id, recipient_type, recipient_id),
            KEY actor_user (actor_user_id),
            KEY event_key (event_key),
            KEY awarded_at (awarded_at)
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
