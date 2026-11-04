<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Move existing step images into _enigmes, hash predictable names, then
 * replace the HTTP deny rules. Runs once, on the first request after deploy.
 */
final class RiddleImageStaticMigration
{
    public const OPTION_FLAG = 'cta_riddle_images_static_files_v1';

    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'maybeRun'], 5);
    }

    public static function maybeRun(): void
    {
        if (!function_exists('get_option') || get_option(self::OPTION_FLAG)) {
            return;
        }

        if (!self::run()) {
            return;
        }

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
        if (function_exists('update_option')) {
            update_option(self::OPTION_FLAG, time(), false);
        }
    }

    public static function run(): bool
    {
        $storage = new RiddleStepImageStorageService();
        $storage->migrateExisting();
        if ($storage->hasPublicStepImage()) {
            return false;
        }

        return (new RiddleImageProtectionService())->openStoredRiddles();
    }
}
