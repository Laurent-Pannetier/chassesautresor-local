<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Read player-facing rankings and submission histories for one hunt. */
class PlayerProgressionRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     * @return array<int, array<string, mixed>>
     */
    public function huntLeaderboard(
        int $huntId,
        array $riddleIds,
        array $excludedUserIds,
        int $limit,
        int $offset
    ): array {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($huntId <= 0 || $riddleIds === []) {
            return [];
        }

        [$participantsSql, $participantsParams] = $this->participantsSql($huntId, $riddleIds);
        [$scoresSql, $scoresParams] = $this->scoresSql($riddleIds);
        [$excludeSql, $excludeParams] = $this->excludeClause('p.user_id', $excludedUserIds);
        $users = $this->wpdb->users;
        $places = $this->wpdb->prefix . 'equipe_places';
        $teams = $this->wpdb->prefix . 'equipes';
        $sql = "SELECT p.user_id, COALESCE(NULLIF(u.display_name, ''), u.user_login) AS username,"
            . " COALESCE(sc.resolved, 0) AS resolved, sc.reached_at,"
            . " COALESCE(e.nom, '') AS team_name"
            . " FROM ({$participantsSql}) p"
            . " JOIN {$users} u ON u.ID = p.user_id"
            . " LEFT JOIN ({$scoresSql}) sc ON sc.user_id = p.user_id"
            . " LEFT JOIN {$places} ep ON ep.user_id = p.user_id"
            . " LEFT JOIN {$teams} e ON e.id = ep.equipe_id AND e.dissolved_at IS NULL"
            . " WHERE COALESCE(sc.resolved, 0) > 0{$excludeSql}"
            . " ORDER BY COALESCE(sc.resolved, 0) DESC,"
            . " CASE WHEN COALESCE(sc.resolved, 0) > 0 THEN sc.reached_at END ASC,"
            . " LOWER(COALESCE(NULLIF(u.display_name, ''), u.user_login)) ASC, p.user_id ASC"
            . ' LIMIT %d OFFSET %d';
        $params = array_merge(
            $participantsParams,
            $scoresParams,
            $excludeParams,
            [max(1, $limit), max(0, $offset)]
        );

        return $this->rows($sql, $params);
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     */
    public function countHuntLeaderboardPlayers(int $huntId, array $riddleIds, array $excludedUserIds): int
    {
        $riddleIds = $this->positiveIds($riddleIds);
        if ($huntId <= 0 || $riddleIds === []) {
            return 0;
        }

        [$participantsSql, $participantsParams] = $this->participantsSql($huntId, $riddleIds);
        [$scoresSql, $scoresParams] = $this->scoresSql($riddleIds);
        [$excludeSql, $excludeParams] = $this->excludeClause('p.user_id', $excludedUserIds);

        return $this->value(
            "SELECT COUNT(*) FROM ({$participantsSql}) p"
            . " LEFT JOIN ({$scoresSql}) sc ON sc.user_id = p.user_id"
            . " WHERE COALESCE(sc.resolved, 0) > 0{$excludeSql}",
            array_merge($participantsParams, $scoresParams, $excludeParams)
        );
    }

    /**
     * @param int[] $riddleIds
     * @param int[] $excludedUserIds
     */
    public function huntPosition(
        int $userId,
        int $huntId,
        array $riddleIds,
        array $excludedUserIds
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        $rows = $this->huntLeaderboard(
            $huntId,
            $riddleIds,
            $excludedUserIds,
            PHP_INT_MAX,
            0
        );
        foreach ($rows as $index => $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                return $index + 1;
            }
        }

        return 0;
    }

    /**
     * Eligible players solved the riddle, found a step, or submitted a final
     * answer that is correct, an accepted variant, or still pending.
     *
     * @param int[] $excludedUserIds
     */
    public function countEligibleRiddlePlayers(int $riddleId, array $excludedUserIds): int
    {
        if ($riddleId <= 0) {
            return 0;
        }

        [$eligibleSql, $eligibleParams] = $this->eligibleRiddlePlayersSql($riddleId);
        [$excludeSql, $excludeParams] = $this->excludeClause('user_id', $excludedUserIds);

        return $this->value(
            "SELECT COUNT(*) FROM ({$eligibleSql}) eligible WHERE 1 = 1{$excludeSql}",
            array_merge($eligibleParams, $excludeParams)
        );
    }

    /**
     * Solved players come first by elapsed time. Others are ordered by completed
     * steps, then by the time taken to reach that depth. Sort keys stay internal.
     *
     * @param int[] $excludedUserIds
     * @return array<int, array<string, mixed>>
     */
    public function riddleRanking(
        int $riddleId,
        array $excludedUserIds,
        int $limit,
        int $offset
    ): array {
        if ($riddleId <= 0) {
            return [];
        }

        $engagements = $this->wpdb->prefix . 'engagements';
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $steps = $this->wpdb->prefix . 'enigme_etapes_progression';
        $places = $this->wpdb->prefix . 'equipe_places';
        $teams = $this->wpdb->prefix . 'equipes';
        $users = $this->wpdb->users;
        [$eligibleSql, $eligibleParams] = $this->eligibleRiddlePlayersSql($riddleId);
        [$excludeSql, $excludeParams] = $this->excludeClause('base.user_id', $excludedUserIds);
        $started = 'COALESCE(eng.engaged_at, st.first_step_at, qa.first_at, s.date_mise_a_jour)';
        $reached = 'COALESCE(fc.solved_at, s.date_mise_a_jour, st.last_step_at, qa.first_at, eng.engaged_at)';
        $sql = "SELECT base.user_id, COALESCE(NULLIF(u.display_name, ''), u.user_login) AS username,"
            . " COALESCE(e.nom, '') AS team_name,"
            . " CASE WHEN s.statut IN ('resolue', 'terminee', 'terminée') THEN 1 ELSE 0 END AS solved,"
            . " TIMESTAMPDIFF(SECOND, {$started}, {$reached}) AS elapsed"
            . " FROM ({$eligibleSql}) base"
            . " JOIN {$users} u ON u.ID = base.user_id"
            . " LEFT JOIN (SELECT user_id, MIN(date_engagement) AS engaged_at FROM {$engagements}"
            . " WHERE enigme_id = %d GROUP BY user_id) eng ON eng.user_id = base.user_id"
            . " LEFT JOIN {$statuses} s ON s.user_id = base.user_id AND s.enigme_id = %d"
            . " LEFT JOIN (SELECT user_id, MIN(date_tentative) AS solved_at FROM {$attempts}"
            . " WHERE enigme_id = %d AND etape_id IS NULL AND resultat = 'bon' GROUP BY user_id) fc"
            . " ON fc.user_id = base.user_id"
            . " LEFT JOIN (SELECT user_id, COUNT(*) AS steps_done, MIN(date_decouverte) AS first_step_at,"
            . " MAX(date_decouverte) AS last_step_at FROM {$steps}"
            . " WHERE enigme_id = %d AND statut = 'trouvee' GROUP BY user_id) st"
            . " ON st.user_id = base.user_id"
            . " LEFT JOIN (SELECT user_id, MIN(date_tentative) AS first_at FROM {$attempts}"
            . " WHERE enigme_id = %d AND etape_id IS NULL"
            . " AND resultat IN ('bon', 'variante', 'attente') GROUP BY user_id) qa"
            . " ON qa.user_id = base.user_id"
            . " LEFT JOIN {$places} ep ON ep.user_id = base.user_id"
            . " LEFT JOIN {$teams} e ON e.id = ep.equipe_id AND e.dissolved_at IS NULL"
            . " WHERE 1 = 1{$excludeSql}"
            . " ORDER BY solved DESC, CASE WHEN solved = 1 THEN elapsed END ASC,"
            . " CASE WHEN solved = 0 THEN COALESCE(st.steps_done, 0) END DESC,"
            . " CASE WHEN solved = 0 THEN elapsed END ASC,"
            . " LOWER(COALESCE(NULLIF(u.display_name, ''), u.user_login)) ASC, base.user_id ASC"
            . ' LIMIT %d OFFSET %d';
        $params = array_merge(
            $eligibleParams,
            [$riddleId, $riddleId, $riddleId, $riddleId, $riddleId],
            $excludeParams,
            [max(1, $limit), max(0, $offset)]
        );
        $rows = $this->rows($sql, $params);
        foreach ($rows as $index => $row) {
            unset($rows[$index]['elapsed']);
        }

        return $rows;
    }

    /**
     * A rank exists only when the player is eligible and at least one other player is too.
     *
     * @param int[] $excludedUserIds
     */
    public function riddleRank(int $userId, int $riddleId, array $excludedUserIds): int
    {
        if ($userId <= 0 || $this->countEligibleRiddlePlayers($riddleId, $excludedUserIds) < 2) {
            return 0;
        }

        $rows = $this->riddleRanking($riddleId, $excludedUserIds, PHP_INT_MAX, 0);
        foreach ($rows as $index => $row) {
            if ((int) ($row['user_id'] ?? 0) === $userId) {
                return $index + 1;
            }
        }

        return 0;
    }

    /**
     * @return array{0:string,1:int[]}
     */
    private function eligibleRiddlePlayersSql(int $riddleId): array
    {
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $attempts = $this->wpdb->prefix . 'enigme_tentatives';
        $steps = $this->wpdb->prefix . 'enigme_etapes_progression';
        $sql = "SELECT user_id FROM {$statuses} WHERE enigme_id = %d"
            . " AND statut IN ('resolue', 'terminee', 'terminée')"
            . " UNION SELECT user_id FROM {$steps} WHERE enigme_id = %d AND statut = 'trouvee'"
            . " UNION SELECT user_id FROM {$attempts} WHERE enigme_id = %d AND etape_id IS NULL"
            . " AND resultat IN ('bon', 'variante', 'attente')";

        return [$sql, [$riddleId, $riddleId, $riddleId]];
    }

    /** @return array<int, array<string, mixed>> */
    public function userAttempts(int $userId, int $riddleId, int $limit, int $offset): array
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return [];
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return $this->rows(
            "SELECT id, reponse_saisie, resultat, date_tentative, traitee FROM {$table}"
            . ' WHERE user_id = %d AND enigme_id = %d AND etape_id IS NULL'
            . ' ORDER BY date_tentative DESC, id DESC LIMIT %d OFFSET %d',
            [$userId, $riddleId, max(1, $limit), max(0, $offset)]
        );
    }

    public function countUserAttempts(int $userId, int $riddleId): int
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return $this->value(
            "SELECT COUNT(*) FROM {$table}"
            . ' WHERE user_id = %d AND enigme_id = %d AND etape_id IS NULL',
            [$userId, $riddleId]
        );
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
    private function scoresSql(array $riddleIds): array
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
    private function excludeClause(string $column, array $excludedUserIds): array
    {
        $excludedUserIds = $this->positiveIds($excludedUserIds);
        if ($excludedUserIds === []) {
            return ['', []];
        }

        $placeholders = implode(', ', array_fill(0, count($excludedUserIds), '%d'));

        return [" AND {$column} NOT IN ({$placeholders})", $excludedUserIds];
    }

    /** @param int[] $ids @return int[] */
    private function positiveIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
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
