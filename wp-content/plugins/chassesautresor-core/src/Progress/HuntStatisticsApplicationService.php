<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Build hunt statistics without theme-provided business callbacks. */
final class HuntStatisticsApplicationService
{
    private HuntStatisticsService $statistics;
    private HuntEngagementService $engagements;

    public function __construct(HuntStatisticsService $statistics, HuntEngagementService $engagements)
    {
        $this->statistics = $statistics;
        $this->engagements = $engagements;
    }

    public function canManage(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }
        if (current_user_can('manage_options')) {
            return true;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $userIds = $organizerId !== null ? (array) get_field('utilisateurs_associes', $organizerId) : [];

        return in_array($userId, $relationships->normalizeIds($userIds), true);
    }

    public function summary(int $huntId, string $period): array
    {
        [$start, $end] = (new StatisticsPeriodService())->range($period);
        $riddleIds = $this->riddleIds($huntId);
        $excluded = $this->excludedUserIds($huntId);
        $participants = $this->engagements->countParticipants($huntId, $start, $end, $excluded);

        return [
            'participants' => $participants,
            'tentatives' => $this->statistics->countAttempts($riddleIds, $start, $end),
            'points' => $this->statistics->sumCollectedPoints($riddleIds, $start, $end),
            'engagement_rate' => (int) round($this->statistics->calculateEngagementRate(
                $participants,
                $riddleIds,
                $start,
                $end,
                $excluded
            )),
        ];
    }

    public function participants(
        int $huntId,
        int $limit,
        int $offset,
        string $orderBy,
        string $order
    ): array {
        $riddleIds = $this->riddleIds($huntId);
        $rows = $this->statistics->listParticipants(
            $huntId,
            $riddleIds,
            $this->excludedUserIds($huntId),
            $limit,
            $offset,
            $orderBy,
            $order
        );

        return array_map(function (array $row) use ($riddleIds): array {
            $engagedIds = array_map(
                'intval',
                $this->statistics->findEngagedRiddleIds((int) $row['user_id'], $riddleIds)
            );

            return [
                'username' => $row['username'],
                'date_inscription' => $row['date_inscription'],
                'enigmes' => array_map(static fn (int $id): array => [
                    'id' => $id,
                    'title' => get_the_title($id),
                    'url' => get_permalink($id),
                ], $engagedIds),
                'nb_engagees' => (int) ($row['nb_engagees'] ?? 0),
                'nb_resolues' => (int) ($row['nb_resolues'] ?? 0),
            ];
        }, $rows);
    }

    public function participantCount(int $huntId): int
    {
        return $this->engagements->countParticipants($huntId, null, null, $this->excludedUserIds($huntId));
    }

    private function riddleIds(int $huntId): array
    {
        $query = (new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId);

        return $query === [] ? [] : array_map('intval', (array) get_posts($query));
    }

    private function excludedUserIds(int $huntId): array
    {
        $relationships = new RelationshipService();
        $excluded = (array) get_users(['role' => 'administrator', 'fields' => 'ids']);
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId !== null) {
            $excluded = array_merge($excluded, (array) get_field('utilisateurs_associes', $organizerId));
        }

        return array_values(array_unique($relationships->normalizeIds($excluded)));
    }
}
