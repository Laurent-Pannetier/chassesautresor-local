<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Install and upgrade the player progress table for intermediate riddle steps. */
final class RiddleStepProgressTable {
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_riddle_step_progress_schema_version';

    public static function install(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'enigme_etapes_progression';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            enigme_id BIGINT UNSIGNED NOT NULL,
            etape_id BIGINT UNSIGNED NOT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'trouvee',
            date_decouverte DATETIME NOT NULL,
            tentative_uid VARCHAR(64) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_step (user_id, etape_id),
            KEY user_riddle (user_id, enigme_id),
            KEY step_status (etape_id, statut)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void {
        if (get_option(self::VERSION_OPTION) !== self::SCHEMA_VERSION) {
            self::install();
        }
    }
}
