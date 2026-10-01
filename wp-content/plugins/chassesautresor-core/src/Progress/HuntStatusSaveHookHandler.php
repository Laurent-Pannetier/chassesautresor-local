<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Keep functional and native hunt statuses synchronized after ACF saves.
 */
class HuntStatusSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'refresh'], 20, 1);
        $addAction('acf/save_post', [self::class, 'synchronizePublication'], 99, 1);
    }

    public static function refresh($postId): void {
        if (is_numeric($postId) && get_post_type((int) $postId) === 'chasse') {
            delete_transient('acf_field_' . (int) $postId . '_champs_caches');
            (new HuntStatusUpdater())->refresh((int) $postId);
        }
    }

    public static function synchronizePublication($postId): void {
        if (is_numeric($postId)) {
            (new HuntStatusUpdater())->synchronizePublication((int) $postId);
        }
    }
}
