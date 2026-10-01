<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\RiddleSystemStateUpdater;

/** Own riddle state and completeness refresh hooks in the core plugin. */
final class RiddleMutationLifecycleHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('chassesautresor_riddle_created', [self::class, 'refreshState']);
        $addAction('chassesautresor_riddle_state_refresh_requested', [self::class, 'refreshState']);
        $addAction('chassesautresor_riddle_completeness_refresh_requested', [self::class, 'refreshCompleteness']);
    }

    public static function refreshState(int $riddleId): void
    {
        (new RiddleSystemStateUpdater())->refresh($riddleId);
    }

    public static function refreshCompleteness(int $riddleId): void
    {
        (new CompletionCacheManager())->refresh($riddleId);
    }
}
