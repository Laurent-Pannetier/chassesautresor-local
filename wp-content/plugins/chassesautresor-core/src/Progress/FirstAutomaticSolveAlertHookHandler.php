<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Alert staff the first time a player solves an automatically validated riddle. */
final class FirstAutomaticSolveAlertHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('enigme_resolue', [self::class, 'handle'], 10, 2);
    }

    public static function handle(int $userId, int $riddleId): void
    {
        (new AttemptAlertService())->notifyFirstAutomaticSolve($userId, $riddleId);
    }
}
