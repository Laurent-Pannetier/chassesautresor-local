<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\HuntStatusUpdater;
use ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Refresh privileged hunt caches before the presentation template is rendered.
 */
final class HuntViewMaintenanceHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('template_redirect', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (!is_singular('chasse')) {
            return;
        }

        $huntId = (int) get_queried_object_id();
        $userId = (int) get_current_user_id();
        if ($huntId <= 0 || !self::canMaintain($huntId, $userId)) {
            return;
        }

        (new HuntStatusUpdater())->refreshIfStale($huntId);
        (new HuntRiddleCacheSynchronizer())->maybeSynchronize($huntId, true);
        (new CompletionCacheManager())->ensureFresh($huntId);

        $cacheKey = 'chasse_infos_affichage_v2_' . $huntId;
        wp_cache_delete($cacheKey, 'chasse_affichage');
        delete_transient($cacheKey);
    }

    private static function canMaintain(int $huntId, int $userId): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if ($userId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        $organizerUsers = $relationships->normalizeIds(
            (array) get_field('utilisateurs_associes', $organizerId)
        );

        return in_array($userId, $organizerUsers, true);
    }
}
