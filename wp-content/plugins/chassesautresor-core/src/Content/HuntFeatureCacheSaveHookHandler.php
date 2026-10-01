<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Refresh hunt feature flags after related content changes.
 */
class HuntFeatureCacheSaveHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('save_post_enigme', [self::class, 'handleRiddle'], 20, 1);
        $addAction('save_post_indice', [self::class, 'handleHint'], 20, 1);
        $addAction('save_post_solution', [self::class, 'handleSolution'], 20, 1);
    }

    public static function handleRiddle(int $postId): void
    {
        (new HuntFeatureCacheManager())->handleRiddleSave($postId);
    }

    public static function handleHint(int $postId): void
    {
        (new HuntFeatureCacheManager())->handleHintSave($postId);
    }

    public static function handleSolution(int $postId): void
    {
        (new HuntFeatureCacheManager())->handleSolutionSave($postId);
    }
}
