<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Build the hunt performance view for a player's current team. */
final class TeamPerformanceApplicationService
{
    public function __construct(private readonly TeamPerformanceRepository $repository)
    {
    }

    /** @return array<string, mixed> */
    public function build(int $teamId, int $huntId, int $page = 1): array
    {
        $riddleIds = $this->riddleIds($huntId);
        $memberIds = $this->repository->memberIds($teamId);
        $summary = $this->repository->summary($memberIds, $huntId, $riddleIds);

        return [
            'hunt_id' => $huntId,
            'team_id' => $teamId,
            'resolvable' => count($riddleIds),
            'resolved' => (int) $summary['resolved'],
            'started' => (int) $summary['started'],
            'attempts' => (int) $summary['attempts'],
            'hints' => (int) $summary['hints'],
            'members' => $this->repository->memberProgress($memberIds, $riddleIds),
            'leaderboard' => $this->leaderboard($teamId, $huntId, $page, $riddleIds),
        ];
    }

    /** @return array<string, mixed> */
    public function ranking(int $teamId, int $huntId, int $page): array
    {
        return $this->leaderboard($teamId, $huntId, $page, $this->riddleIds($huntId));
    }

    /**
     * @param int[] $riddleIds
     * @return array<string, mixed>
     */
    private function leaderboard(int $teamId, int $huntId, int $page, array $riddleIds): array
    {
        $total = $this->repository->leaderboardCount($huntId, $riddleIds);
        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($pages, $page));

        return [
            'rows' => $this->repository->leaderboard($huntId, $riddleIds, 10, ($page - 1) * 10),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_team_id' => $teamId,
            'current_position' => $this->repository->leaderboardPosition($teamId, $huntId, $riddleIds),
        ];
    }

    /** @return int[] */
    private function riddleIds(int $huntId): array
    {
        if ($huntId <= 0 || !function_exists('recuperer_ids_enigmes_pour_chasse')) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', (array) recuperer_ids_enigmes_pour_chasse($huntId)),
            static fn (int $id): bool => $id > 0
        )));
    }
}
