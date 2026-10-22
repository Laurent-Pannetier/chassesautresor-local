<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Assemble one player's hunt progression from stored attempts, statuses and steps. */
final class PlayerProgressionApplicationService
{
    private ProgressionOverviewRepository $dashboard;
    private ?PlayerProgressionRepository $playerData;

    public function __construct(
        ProgressionOverviewRepository $dashboard,
        ?PlayerProgressionRepository $playerData = null
    )
    {
        $this->dashboard = $dashboard;
        $this->playerData = $playerData;
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
        $excluded = $this->excludedUserIds($huntId);
        $playerData = $this->playerData();

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
                'thumbnail_url' => (string) ($catalog['thumbnail_url'] ?? ''),
                'status' => $status,
                'steps_done' => (int) ($steps[$riddleId]['count'] ?? 0),
                'steps_total' => (int) ($catalog['steps_total'] ?? 0),
                'attempts' => (int) ($activity[$riddleId]['attempts'] ?? 0),
                'last_activity' => $dates[0] ?? '',
                'rank' => $playerData->riddleRank($userId, $riddleId, $excluded),
            ];
        }

        return [
            'hunt_id' => $huntId,
            'resolved' => (int) ($progress['resolues'] ?? 0),
            'resolvable' => (int) ($progress['resolvables'] ?? 0),
            'engaged' => (int) ($progress['engagees'] ?? 0),
            'total' => (int) ($progress['total'] ?? count($riddleIds)),
            'attempts_success' => (int) ($attempts['success'] ?? 0),
            'attempts_total' => (int) ($attempts['total'] ?? 0),
            'attempts_pending' => (int) ($attempts['pending'] ?? 0),
            'hints' => $this->dashboard->countUnlockedHints($userId, $huntId, $riddleIds),
            'rank' => $playerData->huntPosition($userId, $huntId, $riddleIds, $excluded),
            'riddles' => $riddles,
        ];
    }

    /**
     * General hunt ranking, then one ranking per riddle.
     *
     * @param callable(int):int[]|null $riddleIdsProvider
     * @return array{hunt_id:int,leaderboard:array<string,mixed>,riddles:array<int,array<string,mixed>>}
     */
    public function rankingPage(int $userId, int $huntId, ?callable $riddleIdsProvider = null): array
    {
        $riddleIdsProvider = $riddleIdsProvider ?? [$this, 'defaultRiddleIds'];
        $riddleIds = $this->positiveIds((array) $riddleIdsProvider($huntId));
        $riddles = [];
        foreach ($riddleIds as $riddleId) {
            $riddles[] = [
                'id' => $riddleId,
                'title' => $this->riddleTitle($riddleId),
                'ranking' => $this->riddleRanking($userId, $huntId, $riddleId, 1, $riddleIdsProvider),
            ];
        }

        return [
            'hunt_id' => $huntId,
            'leaderboard' => $this->leaderboard($userId, $huntId, 1, $riddleIdsProvider),
            'riddles' => $riddles,
        ];
    }

    /**
     * @param callable(int):int[]|null $riddleIdsProvider
     * @return array<string, mixed>
     */
    public function leaderboard(
        int $userId,
        int $huntId,
        int $page,
        ?callable $riddleIdsProvider = null
    ): array {
        $riddleIdsProvider = $riddleIdsProvider ?? [$this, 'defaultRiddleIds'];
        $riddleIds = $this->positiveIds((array) $riddleIdsProvider($huntId));
        $excluded = $this->excludedUserIds($huntId);
        $total = $this->playerData()->countHuntLeaderboardPlayers($huntId, $riddleIds, $excluded);
        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($pages, $page));

        return [
            'rows' => $this->playerData()->huntLeaderboard(
                $huntId,
                $riddleIds,
                $excluded,
                10,
                ($page - 1) * 10
            ),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_user_id' => $userId,
            'current_position' => $this->playerData()->huntPosition(
                $userId,
                $huntId,
                $riddleIds,
                $excluded
            ),
        ];
    }

    /**
     * A riddle ranking is published only when at least two players are eligible.
     *
     * @param callable(int):int[]|null $riddleIdsProvider
     * @return array<string, mixed>
     */
    public function riddleRanking(
        int $userId,
        int $huntId,
        int $riddleId,
        int $page,
        ?callable $riddleIdsProvider = null
    ): array {
        $empty = [
            'rows' => [],
            'page' => 1,
            'pages' => 1,
            'total' => 0,
            'current_user_id' => $userId,
        ];
        $riddleIdsProvider = $riddleIdsProvider ?? [$this, 'defaultRiddleIds'];
        $riddleIds = $this->positiveIds((array) $riddleIdsProvider($huntId));
        if (!in_array($riddleId, $riddleIds, true)) {
            return $empty;
        }

        $excluded = $this->excludedUserIds($huntId);
        $total = $this->playerData()->countEligibleRiddlePlayers($riddleId, $excluded);
        if ($total < 2) {
            return $empty;
        }

        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($pages, $page));
        $standing = $total > 10
            ? $this->playerData()->riddleStanding($userId, $riddleId, $excluded)
            : ['rank' => 0, 'row' => null];
        $viewer = is_array($standing['row'] ?? null) ? $standing['row'] : null;
        if (is_array($viewer)) {
            $viewer['rank'] = (int) $standing['rank'];
        }

        return [
            'rows' => $this->playerData()->riddleRanking(
                $riddleId,
                $excluded,
                10,
                ($page - 1) * 10
            ),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_user_id' => $userId,
            'current_position' => (int) ($standing['rank'] ?? 0),
            'viewer' => $viewer,
        ];
    }

    /** @return array<string, mixed> */
    public function attempts(int $userId, int $huntId, int $riddleId, int $page): array
    {
        $riddleIds = $this->positiveIds($this->defaultRiddleIds($huntId));
        if (!in_array($riddleId, $riddleIds, true)) {
            return ['rows' => [], 'page' => 1, 'pages' => 1, 'total' => 0];
        }

        $total = $this->playerData()->countUserAttempts($userId, $riddleId);
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($pages, $page));

        return [
            'rows' => $this->playerData()->userAttempts($userId, $riddleId, 20, ($page - 1) * 20),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyOverview(): array
    {
        return [
            'hunt_id' => 0,
            'resolved' => 0,
            'resolvable' => 0,
            'engaged' => 0,
            'total' => 0,
            'attempts_success' => 0,
            'attempts_total' => 0,
            'attempts_pending' => 0,
            'hints' => 0,
            'rank' => 0,
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

    private function riddleTitle(int $riddleId): string
    {
        return function_exists('get_the_title') ? (string) get_the_title($riddleId) : '';
    }

    /** @return array{title:string,url:string,thumbnail_url:string,steps_total:int} */
    private function defaultCatalog(int $riddleId): array
    {
        $steps = (new \ChassesAuTresor\Core\Content\RiddleStepQueryService())->findOrderedIds($riddleId);
        $url = function_exists('get_permalink') ? get_permalink($riddleId) : '';
        $thumbnailUrl = function_exists('get_the_post_thumbnail_url')
            ? get_the_post_thumbnail_url($riddleId, 'medium')
            : '';

        return [
            'title' => function_exists('get_the_title') ? (string) get_the_title($riddleId) : '',
            'url' => is_string($url) ? $url : '',
            'thumbnail_url' => is_string($thumbnailUrl) ? $thumbnailUrl : '',
            'steps_total' => count($steps),
        ];
    }

    private function playerData(): PlayerProgressionRepository
    {
        if ($this->playerData instanceof PlayerProgressionRepository) {
            return $this->playerData;
        }

        global $wpdb;
        $this->playerData = new PlayerProgressionRepository($wpdb);

        return $this->playerData;
    }

    /** @return int[] */
    private function excludedUserIds(int $huntId): array
    {
        $excluded = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];
        if ($huntId > 0 && function_exists('get_field')) {
            $organizer = get_field('chasse_cache_organisateur', $huntId);
            $organizerId = is_object($organizer) && isset($organizer->ID)
                ? (int) $organizer->ID
                : (is_array($organizer) ? (int) ($organizer['ID'] ?? $organizer['id'] ?? 0) : (int) $organizer);
            if ($organizerId > 0) {
                foreach ((array) get_field('utilisateurs_associes', $organizerId) as $user) {
                    if (is_object($user) && isset($user->ID)) {
                        $excluded[] = (int) $user->ID;
                    } elseif (is_array($user)) {
                        $excluded[] = (int) ($user['ID'] ?? $user['id'] ?? 0);
                    } else {
                        $excluded[] = (int) $user;
                    }
                }
            }
        }

        return $this->positiveIds($excluded);
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
