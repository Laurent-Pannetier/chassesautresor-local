<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Award the welcome trophy when a player joins a hunt for the first time. */
final class FirstParticipationTrophyHook
{
    public const EVENT_KEY = 'bienvenue';

    public static function register(callable $addAction): void
    {
        $addAction('chasse_engagement_created', [self::class, 'handle'], 10, 2);
    }

    public static function handle(int $huntId, int $userId = 0): void
    {
        if (!self::shouldDispatch($huntId, $userId) || !function_exists('cat_dispatch_trophy_event')) {
            return;
        }

        cat_dispatch_trophy_event(self::EVENT_KEY, $userId, [
            'type' => 'chasse',
            'id' => $huntId,
        ]);
    }

    public static function shouldDispatch(int $huntId, int $userId): bool
    {
        return $huntId > 0 && $userId > 0;
    }
}
