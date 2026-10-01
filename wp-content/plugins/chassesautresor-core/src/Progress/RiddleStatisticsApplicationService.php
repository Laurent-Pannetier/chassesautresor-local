<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Build riddle statistics from core repositories and WordPress relationships. */
final class RiddleStatisticsApplicationService
{
    private RiddleStatisticsService $statistics;

    public function __construct(RiddleStatisticsService $statistics)
    {
        $this->statistics = $statistics;
    }

    public function summary(int $riddleId, string $period): array
    {
        [$start, $end] = (new StatisticsPeriodService())->range($period);
        $mode = (string) (get_field('enigme_mode_validation', $riddleId) ?? 'automatique');
        $cost = (int) get_field('enigme_tentative_cout_points', $riddleId);
        $summary = [
            'participants' => $this->statistics->countEngagedPlayers(
                $riddleId,
                $start,
                $end,
                $this->excludedUserIds($riddleId)
            ),
        ];

        if ($mode !== 'aucune') {
            $summary['tentatives'] = $this->statistics->countAttempts($riddleId, $start, $end);
            $summary['solutions'] = $this->statistics->countCorrectSolutions($riddleId, $start, $end);
        }
        if ($cost > 0) {
            $summary['points'] = $this->statistics->sumSpentPoints($riddleId, $start, $end);
        }

        return $summary;
    }

    public function participants(
        int $riddleId,
        int $limit,
        int $offset,
        string $orderBy,
        string $order
    ): array {
        return $this->statistics->listParticipants(
            $riddleId,
            $this->excludedUserIds($riddleId),
            $limit,
            $offset,
            $orderBy,
            $order
        );
    }

    public function participantCount(int $riddleId): int
    {
        return $this->statistics->countEngagedPlayers(
            $riddleId,
            null,
            null,
            $this->excludedUserIds($riddleId)
        );
    }

    private function excludedUserIds(int $riddleId): array
    {
        $excluded = (array) get_users(['role' => 'administrator', 'fields' => 'ids']);
        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $organizerId = $huntId !== null
            ? $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId))
            : null;
        if ($organizerId !== null) {
            $excluded = array_merge($excluded, (array) get_field('utilisateurs_associes', $organizerId));
        }

        return array_values(array_unique($relationships->normalizeIds($excluded)));
    }
}
