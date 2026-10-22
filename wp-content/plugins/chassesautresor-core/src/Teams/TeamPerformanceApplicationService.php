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
    public function build(int $teamId, int $huntId, int $page = 1, int $userId = 0): array
    {
        $riddleIds = $this->riddleIds($huntId);
        $memberIds = $this->repository->memberIds($teamId);
        $summary = $this->repository->summary($memberIds, $huntId, $riddleIds);

        return [
            'hunt_id' => $huntId,
            'team_id' => $teamId,
            'user_id' => $userId,
            'resolvable' => count($riddleIds),
            'resolved' => (int) $summary['resolved'],
            'started' => (int) $summary['started'],
            'attempts' => (int) $summary['attempts'],
            'hints' => (int) $summary['hints'],
            'members' => $this->repository->memberProgress($memberIds, $riddleIds),
            'leaderboard' => $this->leaderboard($teamId, $userId, $huntId, $page, $riddleIds),
        ];
    }

    /** @return array<string, mixed> */
    public function ranking(int $teamId, int $huntId, int $page, int $userId = 0): array
    {
        return $this->leaderboard($teamId, $userId, $huntId, $page, $this->riddleIds($huntId));
    }

    /**
     * @param int[] $riddleIds
     * @return array<string, mixed>
     */
    private function leaderboard(
        int $teamId,
        int $userId,
        int $huntId,
        int $page,
        array $riddleIds
    ): array {
        $rows = $this->repository->rankedEntries($huntId, $riddleIds, $this->excludedUserIds($huntId));
        $total = count($rows);
        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($pages, $page));
        $position = 0;
        foreach ($rows as $index => $row) {
            $rowTeamId = (int) ($row['team_id'] ?? 0);
            if ($teamId > 0 && $rowTeamId === $teamId) {
                $position = $index + 1;
            } elseif ($teamId <= 0 && $userId > 0 && (int) ($row['user_id'] ?? 0) === $userId) {
                $position = $index + 1;
            }
        }

        return [
            'rows' => array_slice($rows, ($page - 1) * 10, 10),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_team_id' => $teamId,
            'current_user_id' => $userId,
            'current_position' => $position,
        ];
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

        return array_values(array_unique(array_filter(
            array_map('intval', $excluded),
            static fn (int $id): bool => $id > 0
        )));
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
