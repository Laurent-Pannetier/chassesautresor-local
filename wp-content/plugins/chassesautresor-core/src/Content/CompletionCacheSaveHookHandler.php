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
        if (is_numeric($postId)) {
            (new CompletionCacheManager())->refresh((int) $postId);
        }
    }
}
