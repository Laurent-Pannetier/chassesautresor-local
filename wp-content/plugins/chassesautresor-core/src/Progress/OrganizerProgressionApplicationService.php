<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Turn hunt and riddle statistics into the organizer progression dashboard. */
final class OrganizerProgressionApplicationService
{
    private ?ProgressionOverviewRepository $dashboard;
    private $overview;
    private $summary;
    private $solverAttempts;
    private $stepCatalog;
    private $excludedUserIds;
    private $hintTitle;

    public function __construct(
        ?ProgressionOverviewRepository $dashboard = null,
        ?callable $overview = null,
        ?callable $summary = null,
        ?callable $solverAttempts = null,
        ?callable $stepCatalog = null,
        ?callable $excludedUserIds = null,
        ?callable $hintTitle = null
    ) {
        $this->dashboard = $dashboard;
        $this->overview = $overview;
        $this->summary = $summary;
        $this->solverAttempts = $solverAttempts;
        $this->stepCatalog = $stepCatalog;
        $this->excludedUserIds = $excludedUserIds;
        $this->hintTitle = $hintTitle;
    }

    /**
     * @return array{summary:array<string,mixed>,riddles:array<int,array<string,mixed>>}
     */
    public function build(int $huntId, string $period = 'total'): array
    {
        $periodService = new StatisticsPeriodService();
        $period = $periodService->normalize($period);
        if ($huntId <= 0) {
            return ['summary' => [], 'riddles' => []];
        }

        [$start, $end] = $periodService->range($period);
        $summaryProvider = $this->summary ?? [$this, 'defaultSummary'];
        $overviewProvider = $this->overview ?? [$this, 'defaultOverview'];
        $summary = (array) $summaryProvider($huntId, $period);
        $riddles = [];
        foreach ((array) $overviewProvider($huntId, $period) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $riddles[] = $this->enrich($row, $start, $end);
        }

        return [
            'summary' => $summary,
            'riddles' => $riddles,
        ];
    }

    /** @param int[] $attemptCounts */
    public function median(array $attemptCounts): ?int
    {
        $attemptCounts = array_values(array_map('intval', $attemptCounts));
        if ($attemptCounts === []) {
            return null;
        }

        sort($attemptCounts);
        $count = count($attemptCounts);
        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return $attemptCounts[$middle];
        }

        return (int) round(($attemptCounts[$middle - 1] + $attemptCounts[$middle]) / 2);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function enrich(array $row, ?string $start, ?string $end): array
    {
        $riddleId = (int) ($row['id'] ?? 0);
        $excludedProvider = $this->excludedUserIds ?? [$this, 'defaultExcluded'];
        $excluded = array_values(array_map('intval', (array) $excludedProvider($riddleId)));
        $dashboard = $this->dashboard();
        $breakdown = $dashboard->attemptBreakdown($riddleId, $start, $end, $excluded);
        $solverProvider = $this->solverAttempts ?? [$this, 'defaultSolverAttempts'];
        $catalogProvider = $this->stepCatalog ?? [$this, 'defaultStepCatalog'];
        $catalog = array_values(array_filter(
            (array) $catalogProvider($riddleId),
            'is_array'
        ));
        $stepIds = array_values(array_filter(array_map(
            static fn (array $step): int => (int) ($step['id'] ?? 0),
            $catalog
        )));
        $reached = $dashboard->countPlayersByStep($stepIds, $excluded);
        $stepAttempts = $dashboard->countAttemptsByStep($stepIds, $start, $end, $excluded);
        $engaged = (int) ($row['participants'] ?? 0);
        $stepsDetail = [];
        foreach ($catalog as $step) {
            $stepId = (int) ($step['id'] ?? 0);
            if ($stepId <= 0) {
                continue;
            }
            $stepsDetail[] = [
                'title' => (string) ($step['title'] ?? ''),
                'reached' => (int) ($reached[$stepId] ?? 0),
                'engaged' => $engaged,
                'attempts' => (int) ($stepAttempts[$stepId] ?? 0),
            ];
        }

        $hint = $dashboard->topHint($riddleId, $excluded);
        $topHint = null;
        if ($hint !== null) {
            $titleProvider = $this->hintTitle ?? [$this, 'defaultHintTitle'];
            $title = trim((string) $titleProvider((int) $hint['indice_id']));
            $topHint = [
                'title' => $title !== '' ? $title : __('Indice', 'chassesautresor-com'),
                'unlocks' => (int) $hint['unlocks'],
            ];
        }

        $stepCount = (int) ($row['steps'] ?? count($stepsDetail));
        $row['funnel'] = [
            'engaged' => $engaged,
            'with_step' => $stepCount > 0 ? (int) ($row['step_players'] ?? 0) : null,
            'submitted' => $dashboard->countPlayersWithStatus($riddleId, ['soumis'], $excluded),
            'solved' => (int) ($row['trouves'] ?? 0),
        ];
        $row['yield'] = [
            'success' => (int) $breakdown['bon'],
            'total' => (int) $breakdown['total'],
        ];
        $row['pending'] = (int) $breakdown['pending'];
        $row['median_attempts'] = $this->median((array) $solverProvider($riddleId, $excluded));
        $row['top_hint'] = $topHint;
        $row['steps_detail'] = $stepsDetail;

        return $row;
    }

    private function dashboard(): ProgressionOverviewRepository
    {
        if ($this->dashboard === null) {
            global $wpdb;
            $this->dashboard = new ProgressionOverviewRepository($wpdb);
        }

        return $this->dashboard;
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultOverview(int $huntId, string $period): array
    {
        global $wpdb;

        return (new RiddleStatisticsApplicationService(
            CoreServiceFactory::riddleStatistics($wpdb)
        ))->overviewForHunt($huntId, $period);
    }

    /** @return array<string, mixed> */
    private function defaultSummary(int $huntId, string $period): array
    {
        global $wpdb;

        $statistics = CoreServiceFactory::huntStatistics($wpdb);
        $summary = (new HuntStatisticsApplicationService(
            $statistics,
            CoreServiceFactory::huntEngagement($wpdb)
        ))->summary($huntId, $period);
        [$start, $end] = (new StatisticsPeriodService())->range($period);
        $query = (new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId);
        $riddleIds = $query === [] || !function_exists('get_posts')
            ? []
            : array_map('intval', (array) get_posts($query));
        $summary['resolution_rate'] = (int) round($statistics->calculateResolutionRate(
            $riddleIds,
            $start,
            $end,
            $this->defaultExcludedHunt($huntId)
        ));
        $summary['winners'] = $this->dashboard()->countWinners($huntId);
        $cap = function_exists('get_field') ? get_field('chasse_infos_nb_max_gagants', $huntId) : 0;
        $summary['winner_cap'] = (int) $cap;

        return $summary;
    }

    /** @return int[] */
    private function defaultSolverAttempts(int $riddleId, array $excludedUserIds): array
    {
        global $wpdb;

        $solvers = CoreServiceFactory::riddleStatistics($wpdb)->listSolvers($riddleId, $excludedUserIds);

        return array_map(
            static fn (array $solver): int => (int) ($solver['tentatives'] ?? 0),
            $solvers
        );
    }

    /** @return array<int, array{id:int,title:string}> */
    private function defaultStepCatalog(int $riddleId): array
    {
        $ids = (new \ChassesAuTresor\Core\Content\RiddleStepQueryService())->findOrderedIds($riddleId);
        $steps = [];
        foreach (array_values($ids) as $index => $stepId) {
            $title = function_exists('get_the_title') ? trim((string) get_the_title($stepId)) : '';
            if ($title === '') {
                $title = sprintf(
                    /* translators: %d: step position in the riddle */
                    __('Étape %d', 'chassesautresor-com'),
                    $index + 1
                );
            }
            $steps[] = ['id' => (int) $stepId, 'title' => $title];
        }

        return $steps;
    }

    /** @return int[] */
    private function defaultExcluded(int $riddleId): array
    {
        $relationships = new RelationshipService();
        $excluded = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];
        $huntId = function_exists('get_field')
            ? $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId))
            : null;
        if ($huntId !== null) {
            $excluded = array_merge($excluded, $this->organizerUserIds($huntId, $relationships));
        }

        return array_values(array_unique($relationships->normalizeIds($excluded)));
    }

    /** @return int[] */
    private function defaultExcludedHunt(int $huntId): array
    {
        $relationships = new RelationshipService();
        $excluded = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];

        return array_values(array_unique($relationships->normalizeIds(array_merge(
            $excluded,
            $this->organizerUserIds($huntId, $relationships)
        ))));
    }

    /** @return int[] */
    private function organizerUserIds(int $huntId, RelationshipService $relationships): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return [];
        }

        return $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
    }

    private function defaultHintTitle(int $hintId): string
    {
        if ($hintId <= 0 || !function_exists('get_the_title')) {
            return '';
        }

        return (string) get_the_title($hintId);
    }
}
