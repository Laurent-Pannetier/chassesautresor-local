<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Build and cache the statistics displayed in the riddle sidebar. */
final class RiddleSidebarStatisticsService
{
    public function __construct(
        private HuntProgressService $progress,
        private HuntEngagementService $engagements,
        private HuntStatisticsService $huntStatistics,
        private RiddleStatisticsService $riddleStatistics
    ) {
    }

    /** @return array{user: int, avg: int} */
    public function progression(int $huntId, int $userId): array
    {
        $key = 'enigme_sidebar_progression_' . $huntId . '_' . $userId;
        $cached = wp_cache_get($key, 'chassesautresor');
        if (is_array($cached)) {
            return $cached;
        }

        $args = (new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId);
        $riddleIds = $args === [] ? [] : array_map('intval', (array) get_posts($args));
        $count = count($riddleIds);
        $excludedUsers = $this->excludedUsers($huntId);
        $participants = $this->engagements->countParticipants($huntId, null, null, $excludedUsers);
        $data = [
            'user' => $count > 0
                ? (int) round(100 * $this->progress->countEngagedRiddles($userId, $riddleIds) / $count)
                : 0,
            'avg' => (int) round($this->huntStatistics->calculateEngagementRate(
                $participants,
                $riddleIds,
                null,
                null,
                $excludedUsers
            )),
        ];
        wp_cache_set($key, $data, 'chassesautresor', HOUR_IN_SECONDS);
        return $data;
    }

    public function resolution(int $riddleId): int
    {
        $key = 'enigme_sidebar_resolution_' . $riddleId;
        $cached = wp_cache_get($key, 'chassesautresor');
        if (is_int($cached)) {
            return $cached;
        }

        $rate = (int) round($this->riddleStatistics->calculateResolutionRate($riddleId));
        wp_cache_set($key, $rate, 'chassesautresor', HOUR_IN_SECONDS);
        return $rate;
    }

    /** @return int[] */
    private function excludedUsers(int $huntId): array
    {
        $relationships = new RelationshipService();
        $excluded = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId !== null) {
            $excluded = array_merge($excluded, (array) get_field('utilisateurs_associes', $organizerId));
        }
        return array_values(array_unique($relationships->normalizeIds($excluded)));
    }
}
