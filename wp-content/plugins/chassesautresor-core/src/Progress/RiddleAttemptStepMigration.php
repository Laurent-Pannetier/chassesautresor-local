<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Add the optional step reference required by intermediate-step attempts. */
final class RiddleAttemptStepMigration {
    public const SCHEMA_VERSION = '1';

    private const VERSION_OPTION = 'cat_riddle_attempt_step_schema_version';

    public static function install(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'enigme_tentatives';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return;
        }

        $column = $wpdb->get_var("SHOW COLUMNS FROM {$table} LIKE 'etape_id'");
        if ($column === null) {
            $result = $wpdb->query(
                "ALTER TABLE {$table} ADD etape_id BIGINT UNSIGNED NULL AFTER enigme_id, "
                . 'ADD KEY etape_id (etape_id)'
            );
            if ($result === false) {
                return;
            }
        }

        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
    }

    public static function maybeUpgrade(): void {
        if (get_option(self::VERSION_OPTION) !== self::SCHEMA_VERSION) {
            self::install();
        }
    }
}
