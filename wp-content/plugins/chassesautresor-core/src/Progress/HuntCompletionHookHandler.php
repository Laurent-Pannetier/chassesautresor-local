<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Own the application entry point triggered whenever a riddle is solved.
 */
final class HuntCompletionHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('enigme_resolue', [self::class, 'handle'], 10, 2);
    }

    public static function handle(int $userId, int $riddleId): void
    {
        global $wpdb;

        $completion = (new HuntCompletionService(
            CoreServiceFactory::huntProgress($wpdb),
            new HuntRiddleClassifier()
        ))->evaluate($userId, $riddleId);

        if (!$completion['is_automatic'] || !$completion['has_riddles'] || !$completion['is_complete']) {
            return;
        }

        if (function_exists('gerer_chasse_terminee')) {
            gerer_chasse_terminee($completion['hunt_id']);
        }
    }
}
