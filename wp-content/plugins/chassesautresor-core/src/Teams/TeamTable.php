<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Install and upgrade the team, membership and active-seat tables. */
final class TeamTable
{
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_equipes_schema_version';

    public static function install(): void
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $teams = $wpdb->prefix . 'equipes';
        $members = $wpdb->prefix . 'equipe_membres';
        $places = $wpdb->prefix . 'equipe_places';
        $sql = "CREATE TABLE {$teams} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nom VARCHAR(40) NOT NULL,
            nom_key VARCHAR(40) NOT NULL,
            image_id BIGINT UNSIGNED NULL,
            join_code CHAR(8) NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            dissolved_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY nom_key (nom_key),
            UNIQUE KEY join_code (join_code),
            KEY created_by (created_by)
        ) {$charset};
        CREATE TABLE {$members} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            equipe_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            statut VARCHAR(12) NOT NULL DEFAULT 'actif',
            joined_at DATETIME NOT NULL,
            left_at DATETIME NULL,
            excluded_by BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY equipe_user (equipe_id, user_id),
            KEY equipe_statut (equipe_id, statut),
            KEY user_id (user_id)
        ) {$charset};
        CREATE TABLE {$places} (
            user_id BIGINT UNSIGNED NOT NULL,
            equipe_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY  (user_id),
            KEY equipe_id (equipe_id)
        ) {$charset};";

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
