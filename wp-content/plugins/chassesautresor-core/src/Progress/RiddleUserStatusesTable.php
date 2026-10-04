<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Install and upgrade per-user riddle status table.
 */
class RiddleUserStatusesTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_enigme_statuts_utilisateur_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'enigme_statuts_utilisateur';
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            user_id BIGINT UNSIGNED NOT NULL,
            enigme_id BIGINT UNSIGNED NOT NULL,
            statut ENUM('non_commencee','en_cours','abandonnee','echouee','resolue','terminee','soumis')
                NOT NULL DEFAULT 'non_commencee',
            date_mise_a_jour DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (user_id, enigme_id)
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
