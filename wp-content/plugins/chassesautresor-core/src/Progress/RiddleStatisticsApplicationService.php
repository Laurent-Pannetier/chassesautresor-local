<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
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

    /**
     * Build a compact per-riddle overview for the account home dashboards.
     *
     * @return array<int, array{
     *     id:int,
     *     title:string,
     *     participants:int,
     *     tentatives:int,
     *     trouves:int,
     *     steps:int,
     *     step_players:int,
     *     ranking:array<int, array{username:string, tentatives:int}>
     * }>
     */
    public function overviewForHunt(
        int $huntId,
        string $period = 'total',
        int $rankingLimit = 5,
        ?callable $riddleIdsProvider = null,
        ?callable $stepIdsProvider = null,
        ?callable $stepPlayersProvider = null
    ): array {
        if ($huntId <= 0) {
            return [];
        }

        $riddleIdsProvider = $riddleIdsProvider
            ?? static function (int $id): array {
                return function_exists('recuperer_ids_enigmes_pour_chasse')
                    ? recuperer_ids_enigmes_pour_chasse($id)
                    : [];
            };
        $stepIdsProvider = $stepIdsProvider
            ?? static function (int $riddleId): array {
                return (new RiddleStepQueryService())->findOrderedIds($riddleId);
            };
        $stepPlayersProvider = $stepPlayersProvider
            ?? static function (int $riddleId): int {
                global $wpdb;

                return (new RiddleStepProgressRepository($wpdb))
                    ->countPlayersWithCompletedSteps($riddleId);
            };

        $rows = [];
        foreach ((array) $riddleIdsProvider($huntId) as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0) {
                continue;
            }

            $summary = $this->summary($riddleId, $period);
            $excluded = $this->excludedUserIds($riddleId);
            $stepIds = array_values(array_filter(array_map('intval', (array) $stepIdsProvider($riddleId))));
            $ranking = array_slice(
                $this->statistics->listSolvers($riddleId, $excluded),
                0,
                max(0, $rankingLimit)
            );

            $rows[] = [
                'id' => $riddleId,
                'title' => (string) get_the_title($riddleId),
                'participants' => (int) ($summary['participants'] ?? 0),
                'tentatives' => (int) ($summary['tentatives'] ?? 0),
                'trouves' => $this->statistics->countSolvedPlayers($riddleId),
                'steps' => count($stepIds),
                'step_players' => $stepIds === [] ? 0 : (int) $stepPlayersProvider($riddleId),
                'ranking' => array_map(
                    static fn (array $solver): array => [
                        'username' => (string) ($solver['username'] ?? ''),
                        'tentatives' => (int) ($solver['tentatives'] ?? 0),
                    ],
                    $ranking
                ),
            ];
        }

        return $rows;
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
