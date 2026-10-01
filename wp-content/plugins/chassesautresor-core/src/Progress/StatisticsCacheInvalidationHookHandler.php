<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Invalidate riddle and hunt statistics after participation mutations.
 */
class StatisticsCacheInvalidationHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('chasse_engagement_created', [self::class, 'clearHunt'], 10, 1);
        $addAction('enigme_engagement_created', [self::class, 'clearRiddleAndHunt'], 10, 1);
        $addAction('enigme_tentative_created', [self::class, 'clearRiddleAndHunt'], 10, 1);
    }

    public static function clearHunt(int $huntId): void
    {
        (new StatisticsCacheService())->clear('chasse', $huntId);
    }

    public static function clearRiddleAndHunt(int $riddleId): void
    {
        $cache = new StatisticsCacheService();
        $cache->clear('enigme', $riddleId);
        $hunt = get_field('enigme_chasse_associee', $riddleId, false);
        if (is_array($hunt)) {
            $hunt = reset($hunt);
        }
        $huntId = is_object($hunt) ? (int) ($hunt->ID ?? 0) : (int) $hunt;
        if ($huntId > 0) {
            $cache->clear('chasse', $huntId);
        }
    }
}
