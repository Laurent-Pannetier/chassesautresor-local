<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Hunt-scoped aggregates for current team rosters. */
class TeamPerformanceRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @return int[] */
    public function memberIds(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $table = $this->wpdb->prefix . 'equipe_places';
        $ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE equipe_id = %d ORDER BY user_id ASC",
            $teamId
        ));

        return array_values(array_filter(array_map('intval', (array) $ids)));
    }

    /**
     * @param int[] $memberIds
     * @param int[] $riddleIds
     * @return array{resolved:int,started:int,attempts:int,hints:int}
     */
    public function summary(array $memberIds, int $huntId, array $riddleIds): array
    {
        $memberIds = $this->positiveIds($memberIds);
        $riddleIds = $this->positiveIds($riddleIds);
        $empty = ['resolved' => 0, 'started' => 0, 'attempts' => 0, 'hints' => 0];
        if ($memberIds === [] || $riddleIds === []) {
            return $empty;
        }

        [$usersSql, $usersParams] = $this->inClause('user_id', $memberIds);
        [$riddlesSql, $riddleParams] = $this->inClause('enigme_id', $riddleIds);
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $engagements = $this->wpdb->prefix . 'engagements';
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $hints = $this->wpdb->prefix . 'indices_deblocages';

        return [
            'resolved' => $this->value(
                "SELECT COUNT(DISTINCT enigme_id) FROM {$statuses}"
                . " WHERE {$usersSql} AND {$riddlesSql}"
                . " AND statut IN ('resolue', 'terminee', 'terminée')",
                array_merge($usersParams, $riddleParams)
            ),
            'started' => $this->value(
                "SELECT COUNT(DISTINCT enigme_id) FROM {$engagements}"
                . " WHERE {$usersSql} AND {$riddlesSql}",
                array_merge($usersParams, $riddleParams)
            ),
            'attempts' => $this->value(
                "SELECT COUNT(*) FROM {$attempts}"
                . " WHERE {$usersSql} AND {$riddlesSql} AND etape_id IS NULL",
                array_merge($usersParams, $riddleParams)
            ),
            'hints' => $this->value(
                "SELECT COUNT(DISTINCT indice_id) FROM {$hints}"
                . " WHERE {$usersSql} AND (chasse_id = %d OR {$riddlesSql})",
                array_merge($usersParams, [$huntId], $riddleParams)
            ),
        ];
    }

    /**
     * @param int[] $memberIds
     * @param int[] $riddleIds
     * @return array<int, array<string, mixed>>
     */
    public function memberProgress(array $memberIds, array $riddleIds): array
    {
        $memberIds = $this->positiveIds($memberIds);
        $riddleIds = $this->positiveIds($riddleIds);
        if ($memberIds === [] || $riddleIds === []) {
            return [];
        }

        [$usersSql, $usersParams] = $this->inClause('u.ID', $memberIds);
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT u.ID AS user_id, COALESCE(NULLIF(u.display_name, ''), u.user_login) AS username,"
            . " COUNT(DISTINCT CASE WHEN s.statut IN ('resolue', 'terminee', 'terminée')"
            . ' THEN s.enigme_id END) AS resolved'
            . " FROM {$this->wpdb->users} u LEFT JOIN {$statuses} s"
            . " ON s.user_id = u.ID AND s.enigme_id IN ({$placeholders})"
            . " WHERE {$usersSql} GROUP BY u.ID, u.display_name, u.user_login"
            . ' ORDER BY resolved DESC, LOWER(COALESCE(NULLIF(u.display_name, \'\'), u.user_login)) ASC';

        return $this->rows($sql, array_merge($riddleIds, $usersParams));
    }

    /**
     * Teams and unaffiliated players share one board. A solo counts as a team of one.
     * Only entries with at least one solved riddle are ranked.
     *
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     * @return array<int, array<string, mixed>>
     */
    public function rankedEntries(int $huntId, array $riddleIds, array $excludedUserIds = []): array
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($huntId <= 0 || $riddleIds === []) {
            return [];
        }

        $rows = array_values(array_filter(
            array_merge(
                $this->teamRows($huntId, $riddleIds),
                $this->soloRows($huntId, $riddleIds, $excludedUserIds)
            ),
            static fn (array $row): bool => (int) ($row['resolved'] ?? 0) > 0
        ));
        usort($rows, static function (array $left, array $right): int {
            $byResolved = (int) ($right['resolved'] ?? 0) <=> (int) ($left['resolved'] ?? 0);
            if ($byResolved !== 0) {
                return $byResolved;
            }
            $byTime = strcmp(self::sortTime($left), self::sortTime($right));
            if ($byTime !== 0) {
                return $byTime;
            }
            $byName = strcasecmp((string) ($left['team_name'] ?? ''), (string) ($right['team_name'] ?? ''));
            if ($byName !== 0) {
                return $byName;
            }
            $byTeam = (int) ($left['team_id'] ?? 0) <=> (int) ($right['team_id'] ?? 0);
            if ($byTeam !== 0) {
                return $byTeam;
            }

            return (int) ($left['user_id'] ?? 0) <=> (int) ($right['user_id'] ?? 0);
        });
        foreach ($rows as $index => $row) {
            unset($rows[$index]['reached_at']);
        }

        return $rows;
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     * @return array<int, array<string, mixed>>
     */
    public function leaderboard(
        int $huntId,
        array $riddleIds,
        int $limit,
        int $offset,
        array $excludedUserIds = []
    ): array {
        return array_slice(
            $this->rankedEntries($huntId, $riddleIds, $excludedUserIds),
            max(0, $offset),
            max(1, $limit)
        );
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     */
    public function leaderboardCount(int $huntId, array $riddleIds, array $excludedUserIds = []): int
    {
        return count($this->rankedEntries($huntId, $riddleIds, $excludedUserIds));
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     */
    public function leaderboardPosition(
        int $teamId,
        int $userId,
        int $huntId,
        array $riddleIds,
        array $excludedUserIds = []
    ): int {
        foreach ($this->rankedEntries($huntId, $riddleIds, $excludedUserIds) as $index => $row) {
            $rowTeamId = (int) ($row['team_id'] ?? 0);
            if ($teamId > 0 && $rowTeamId === $teamId) {
                return $index + 1;
            }
            if ($teamId <= 0 && $userId > 0 && (int) ($row['user_id'] ?? 0) === $userId) {
                return $index + 1;
            }
        }

        return 0;
    }

    /** @param array<string, mixed> $row */
    private static function sortTime(array $row): string
    {
        $time = trim((string) ($row['reached_at'] ?? ''));

        return $time !== '' ? $time : '9999-99-99 99:99:99';
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, array<string, mixed>>
     */
    private function teamRows(int $huntId, array $riddleIds): array
    {
        [$participantsSql, $participantParams] = $this->participantsSql($huntId, $riddleIds);
        [$scoresSql, $scoreParams] = $this->teamScoresSql($riddleIds);
        $teams = $this->wpdb->prefix . 'equipes';
        $places = $this->wpdb->prefix . 'equipe_places';
        $sql = "SELECT e.id AS team_id, 0 AS user_id, e.nom AS team_name, e.image_id,"
            . " COALESCE(mc.members, 0) AS members, sc.resolved, sc.reached_at"
            . " FROM {$teams} e JOIN {$places} ep ON ep.equipe_id = e.id"
            . " JOIN ({$participantsSql}) p ON p.user_id = ep.user_id"
            . " LEFT JOIN (SELECT equipe_id, COUNT(*) AS members FROM {$places} GROUP BY equipe_id) mc"
            . ' ON mc.equipe_id = e.id'
            . " JOIN ({$scoresSql}) sc ON sc.team_id = e.id"
            . ' WHERE e.dissolved_at IS NULL'
            . ' GROUP BY e.id, e.nom, e.image_id, mc.members, sc.resolved, sc.reached_at';

        return $this->rows($sql, array_merge($participantParams, $scoreParams));
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     * @return array<int, array<string, mixed>>
     */
    private function soloRows(int $huntId, array $riddleIds, array $excludedUserIds): array
    {
        [$participantsSql, $participantParams] = $this->participantsSql($huntId, $riddleIds);
        [$scoresSql, $scoreParams] = $this->soloScoresSql($riddleIds);
        [$excludeSql, $excludeParams] = $this->excludeClause($excludedUserIds);
        $users = $this->wpdb->users;
        $places = $this->wpdb->prefix . 'equipe_places';
        $teams = $this->wpdb->prefix . 'equipes';
        $sql = "SELECT 0 AS team_id, u.ID AS user_id,"
            . " COALESCE(NULLIF(u.display_name, ''), u.user_login) AS team_name,"
            . " 0 AS image_id, 1 AS members, sc.resolved, sc.reached_at"
            . " FROM {$users} u"
            . " JOIN ({$participantsSql}) p ON p.user_id = u.ID"
            . " JOIN ({$scoresSql}) sc ON sc.user_id = u.ID"
            . " WHERE NOT EXISTS (SELECT 1 FROM {$places} ep"
            . " JOIN {$teams} e ON e.id = ep.equipe_id AND e.dissolved_at IS NULL"
            . " WHERE ep.user_id = u.ID){$excludeSql}";

        return $this->rows($sql, array_merge($participantParams, $scoreParams, $excludeParams));
    }

    /**
     * @param int[] $riddleIds
     * @return array{0:string,1:int[]}
     */
    private function participantsSql(int $huntId, array $riddleIds): array
    {
        $engagements = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(', ', array_fill(0, count($riddleIds), '%d'));

        return [
            "SELECT DISTINCT user_id FROM {$engagements}"
                . " WHERE chasse_id = %d OR enigme_id IN ({$placeholders})",
            array_merge([$huntId], $riddleIds),
        ];
    }

    /**
     * @param int[] $riddleIds
     * @return array{0:string,1:int[]}
     */
    private function teamScoresSql(array $riddleIds): array
    {
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $places = $this->wpdb->prefix . 'equipe_places';
        $placeholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
        $attemptPlaceholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT ep.equipe_id AS team_id, COUNT(DISTINCT s.enigme_id) AS resolved,"
            . " MAX(COALESCE(fc.solved_at, s.date_mise_a_jour)) AS reached_at"
            . " FROM {$places} ep JOIN {$statuses} s ON s.user_id = ep.user_id"
            . " LEFT JOIN (SELECT user_id, enigme_id, MIN(date_tentative) AS solved_at"
            . " FROM {$attempts} WHERE etape_id IS NULL AND resultat = 'bon'"
            . " AND enigme_id IN ({$attemptPlaceholders}) GROUP BY user_id, enigme_id) fc"
            . ' ON fc.user_id = s.user_id AND fc.enigme_id = s.enigme_id'
            . " WHERE s.enigme_id IN ({$placeholders})"
            . " AND s.statut IN ('resolue', 'terminee', 'terminée') GROUP BY ep.equipe_id";

        return [$sql, array_merge($riddleIds, $riddleIds)];
    }

    /**
     * @param int[] $riddleIds
     * @return array{0:string,1:int[]}
     */
    private function soloScoresSql(array $riddleIds): array
    {
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $placeholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
        $attemptPlaceholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT s.user_id, COUNT(DISTINCT s.enigme_id) AS resolved,"
            . " MAX(COALESCE(fc.solved_at, s.date_mise_a_jour)) AS reached_at"
            . " FROM {$statuses} s"
            . " LEFT JOIN (SELECT user_id, enigme_id, MIN(date_tentative) AS solved_at"
            . " FROM {$attempts} WHERE etape_id IS NULL AND resultat = 'bon'"
            . " AND enigme_id IN ({$attemptPlaceholders}) GROUP BY user_id, enigme_id) fc"
            . ' ON fc.user_id = s.user_id AND fc.enigme_id = s.enigme_id'
            . " WHERE s.enigme_id IN ({$placeholders})"
            . " AND s.statut IN ('resolue', 'terminee', 'terminée') GROUP BY s.user_id";

        return [$sql, array_merge($riddleIds, $riddleIds)];
    }

    /**
     * @param int[] $excludedUserIds
     * @return array{0:string,1:int[]}
     */
    private function excludeClause(array $excludedUserIds): array
    {
        $excludedUserIds = $this->positiveIds($excludedUserIds);
        if ($excludedUserIds === []) {
            return ['', []];
        }

        $placeholders = implode(', ', array_fill(0, count($excludedUserIds), '%d'));

        return [" AND u.ID NOT IN ({$placeholders})", $excludedUserIds];
    }

    /** @param int[] $ids @return int[] */
    private function positiveIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
    }

    /** @param int[] $ids @return array{0:string,1:int[]} */
    private function inClause(string $column, array $ids): array
    {
        return [
            $column . ' IN (' . implode(', ', array_fill(0, count($ids), '%d')) . ')',
            $ids,
        ];
    }

    /** @param array<int, int|string> $params @return array<int, array<string, mixed>> */
    private function rows(string $sql, array $params): array
    {
        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    /** @param array<int, int|string> $params */
    private function value(string $sql, array $params): int
    {
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
    }
}
