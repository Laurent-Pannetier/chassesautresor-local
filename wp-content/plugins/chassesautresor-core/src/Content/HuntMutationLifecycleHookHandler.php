<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\HuntStatusUpdater;

/** Own hunt status refresh hooks in the core plugin. */
final class HuntMutationLifecycleHookHandler
{
    public static function register(callable $addAction): void
    {
        $callback = [self::class, 'refreshStatus'];
        $addAction('chassesautresor_hunt_dates_updated', $callback);
        $addAction('chassesautresor_hunt_fields_updated', $callback);
        $addAction('chassesautresor_hunt_status_refresh_requested', $callback);
    }

    public static function refreshStatus(int $huntId): void
    {
        (new HuntStatusUpdater())->refresh($huntId);
    }
}
