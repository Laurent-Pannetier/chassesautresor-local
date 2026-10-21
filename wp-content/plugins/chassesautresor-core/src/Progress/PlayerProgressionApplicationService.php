<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Assemble one player's hunt progression from stored attempts, statuses and steps. */
final class PlayerProgressionApplicationService
{
    private ProgressionOverviewRepository $dashboard;

    public function __construct(ProgressionOverviewRepository $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @param callable(int):int[]|null $riddleIdsProvider
     * @param callable(int,int):array<string,mixed>|null $progressProvider
     * @param callable(int):array<string,mixed>|null $catalogProvider
     * @return array{
     *     resolved:int,
     *     resolvable:int,
     *     engaged:int,
     *     total:int,
     *     attempts_success:int,
     *     attempts_total:int,
     *     attempts_pending:int,
     *     hints:int,
     *     riddles:array<int, array<string, mixed>>
     * }
     */
    public function build(
        int $userId,
        int $huntId,
        ?callable $riddleIdsProvider = null,
        ?callable $progressProvider = null,
        ?callable $catalogProvider = null
    ): array {
        $empty = $this->emptyOverview();
        if ($userId <= 0 || $huntId <= 0) {
            return $empty;
        }

        $riddleIdsProvider = $riddleIdsProvider ?? [$this, 'defaultRiddleIds'];
        $progressProvider = $progressProvider ?? [$this, 'defaultProgress'];
        $catalogProvider = $catalogProvider ?? [$this, 'defaultCatalog'];

        $riddleIds = $this->positiveIds((array) $riddleIdsProvider($huntId));
        $progress = (array) $progressProvider($huntId, $userId);
        $attempts = $this->dashboard->summarizeUserAttempts($userId, $riddleIds);
        $activity = $this->dashboard->userRiddleActivity($userId, $riddleIds);
        $statuses = $this->dashboard->userStatuses($userId, $riddleIds);
        $steps = $this->dashboard->userCompletedSteps($userId, $riddleIds);

        $riddles = [];
        foreach ($riddleIds as $riddleId) {
            $catalog = (array) $catalogProvider($riddleId);
            $status = strtolower(trim((string) ($statuses[$riddleId]['status'] ?? 'non_commencee')));
            if ($status === 'terminée') {
                $status = 'terminee';
            }
            if ($status === '') {
                $status = 'non_commencee';
            }
            $dates = array_values(array_filter([
                (string) ($activity[$riddleId]['last_at'] ?? ''),
                (string) ($statuses[$riddleId]['updated_at'] ?? ''),
                (string) ($steps[$riddleId]['last_at'] ?? ''),
            ], static fn (string $date): bool => $date !== ''));
            rsort($dates);

            $riddles[] = [
                'id' => $riddleId,
                'title' => (string) ($catalog['title'] ?? ''),
                'url' => (string) ($catalog['url'] ?? ''),
                'status' => $status,
                'steps_done' => (int) ($steps[$riddleId]['count'] ?? 0),
                'steps_total' => (int) ($catalog['steps_total'] ?? 0),
                'attempts' => (int) ($activity[$riddleId]['attempts'] ?? 0),
                'last_activity' => $dates[0] ?? '',
            ];
        }

        return [
            'resolved' => (int) ($progress['resolues'] ?? 0),
            'resolvable' => (int) ($progress['resolvables'] ?? 0),
            'engaged' => (int) ($progress['engagees'] ?? 0),
            'total' => (int) ($progress['total'] ?? count($riddleIds)),
            'attempts_success' => (int) ($attempts['success'] ?? 0),
            'attempts_total' => (int) ($attempts['total'] ?? 0),
            'attempts_pending' => (int) ($attempts['pending'] ?? 0),
            'hints' => $this->dashboard->countUnlockedHints($userId, $huntId, $riddleIds),
            'riddles' => $riddles,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyOverview(): array
    {
        return [
            'resolved' => 0,
            'resolvable' => 0,
            'engaged' => 0,
            'total' => 0,
            'attempts_success' => 0,
            'attempts_total' => 0,
            'attempts_pending' => 0,
            'hints' => 0,
            'riddles' => [],
        ];
    }

    /** @return int[] */
    private function defaultRiddleIds(int $huntId): array
    {
        if ($huntId <= 0 || !function_exists('recuperer_ids_enigmes_pour_chasse')) {
            return [];
        }

        return array_map('intval', (array) recuperer_ids_enigmes_pour_chasse($huntId));
    }

    /** @return array<string, int> */
    private function defaultProgress(int $huntId, int $userId): array
    {
        if (!function_exists('chasse_calculer_progression_utilisateur')) {
            return ['engagees' => 0, 'total' => 0, 'resolues' => 0, 'resolvables' => 0];
        }

        return chasse_calculer_progression_utilisateur($huntId, $userId);
    }

    /** @return array{title:string,url:string,steps_total:int} */
    private function defaultCatalog(int $riddleId): array
    {
        $steps = (new \ChassesAuTresor\Core\Content\RiddleStepQueryService())->findOrderedIds($riddleId);
        $url = function_exists('get_permalink') ? get_permalink($riddleId) : '';

        return [
            'title' => function_exists('get_the_title') ? (string) get_the_title($riddleId) : '',
            'url' => is_string($url) ? $url : '',
            'steps_total' => count($steps),
        ];
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function positiveIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
    }
}
