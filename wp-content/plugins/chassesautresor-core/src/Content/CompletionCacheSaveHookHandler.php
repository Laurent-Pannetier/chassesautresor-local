<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Refresh completion caches after ACF saves.
 */
class CompletionCacheSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handle'], 20, 1);
    }

    public static function handle($postId): void {
        if (!is_numeric($postId)) {
            return;
        }

        $postId = (int) $postId;
        if (get_post_type($postId) === RiddleStepPostTypeRegistrar::POST_TYPE) {
            $riddleId = (int) get_field('etape_enigme_associee', $postId);
            if ($riddleId > 0) {
                (new CompletionCacheManager())->refresh($riddleId);
            }
            return;
        }

        (new CompletionCacheManager())->refresh($postId);
    }
}
