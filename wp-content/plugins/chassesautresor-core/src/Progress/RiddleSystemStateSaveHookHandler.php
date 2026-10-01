<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Refresh a riddle system state after ACF saves.
 */
class RiddleSystemStateSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handle'], 20, 1);
    }

    public static function handle($postId): void {
        if (is_numeric($postId)
            && get_post_type((int) $postId) === 'enigme'
            && !wp_is_post_revision((int) $postId)
        ) {
            (new RiddleSystemStateUpdater())->refresh((int) $postId);
        }
    }
}
