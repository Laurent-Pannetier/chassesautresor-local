<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Run a one-shot migration of step images into protected storage.
 */
final class RiddleStepImageStorageLifecycle
{
    public const OPTION_FLAG = 'cta_riddle_step_images_migrated_v2';
    public const CRON_HOOK = 'cta_migrate_riddle_step_images';

    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'maybeSchedule'], 20);
        $addAction(self::CRON_HOOK, [self::class, 'runMigration'], 10);
    }

    public static function maybeSchedule(): void
    {
        if (get_option(self::OPTION_FLAG)) {
            return;
        }
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 60, self::CRON_HOOK);
        }
    }

    public static function runMigration(): void
    {
        if (get_option(self::OPTION_FLAG)) {
            return;
        }

        (new RiddleStepImageStorageService())->migrateExisting();
        update_option(self::OPTION_FLAG, time(), false);
    }
}
