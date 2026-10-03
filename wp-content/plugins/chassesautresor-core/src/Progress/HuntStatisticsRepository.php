<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query aggregate hunt statistics.
 */
class HuntStatisticsRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @param int[] $riddleIds */
    public function countAttempts(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        return $this->aggregateAttempts('COUNT(*)', $riddleIds, $startAt, $endAt);
    }

    /** @param int[] $riddleIds */
    public function sumCollectedPoints(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        return $this->aggregateAttempts('SUM(points_utilises)', $riddleIds, $startAt, $endAt);
    }

    public function countEngagements(int $huntId, array $excludedUserIds = []): int
    {
        $table = $this->wpdb->prefix . 'engagements';

        $where = 'chasse_id = %d';
        $params = [$huntId];
        if ($excludedUserIds !== []) {
            $where .= ' AND user_id NOT IN (' . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }
        return (int) $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params));
    }

    /** @param int[] $riddleIds */
    public function sumEngagedPlayersByRiddle(
        array $riddleIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): int {
        $table = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $where = "enigme_id IN ({$placeholders})";
        $params = $riddleIds;

        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_engagement BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        if ($excludedUserIds !== []) {
            $where .= ' AND user_id NOT IN (' . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }

        $sql = "SELECT SUM(cnt) FROM (SELECT COUNT(DISTINCT user_id) AS cnt FROM {$table} "
            . "WHERE {$where} GROUP BY enigme_id) aggregated_engagements";

        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
    }

    /** @param int[] $riddleIds */
    public function sumSolvedPlayersByRiddle(
        array $riddleIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): int {
        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $where = "enigme_id IN ({$placeholders}) AND statut IN ('resolue','terminee','terminée')";
        $params = $riddleIds;
        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_mise_a_jour BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        if ($excludedUserIds !== []) {
            $where .= ' AND user_id NOT IN (' . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }
        $sql = "SELECT SUM(cnt) FROM (SELECT COUNT(DISTINCT user_id) AS cnt FROM {$table} "
            . "WHERE {$where} GROUP BY enigme_id) aggregated_resolutions";
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
    }

    public function listParticipants(
        int $huntId,
        array $riddleIds,
        array $excludedUserIds,
        int $limit,
        int $offset,
        string $orderBy,
        string $order
    ): array {
        $engagements = $this->wpdb->prefix . 'engagements';
        $statuses = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $joinFilter = ' AND 1=0';
        $params = [];
        if ($riddleIds !== []) {
            $joinFilter = ' AND e2.enigme_id IN (' . implode(',', array_fill(0, count($riddleIds), '%d')) . ')';
            $params = $riddleIds;
        }
        $allowedOrderBy = [
            'username' => 'username',
            'participation' => 'nb_engagees',
            'resolution' => 'nb_resolues',
            'inscription' => 'date_inscription',
        ];
        $orderBy = $allowedOrderBy[$orderBy] ?? 'date_inscription';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $exclude = '';
        if ($excludedUserIds !== []) {
            $exclude = ' AND e.user_id NOT IN ('
                . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
        }
        $query = "SELECT e.user_id, u.user_login AS username, MIN(e.date_engagement) AS date_inscription,"
            . " COUNT(DISTINCT e2.enigme_id) AS nb_engagees,"
            . " COUNT(DISTINCT CASE WHEN s.statut IN ('resolue','terminee') THEN s.enigme_id END) AS nb_resolues"
            . " FROM {$engagements} e JOIN {$this->wpdb->users} u ON u.ID = e.user_id"
            . " LEFT JOIN {$engagements} e2 ON e2.user_id = e.user_id{$joinFilter}"
            . " LEFT JOIN {$statuses} s ON s.user_id = e.user_id AND s.enigme_id = e2.enigme_id"
            . " WHERE e.chasse_id = %d AND e.enigme_id IS NULL{$exclude} GROUP BY e.user_id"
            . " ORDER BY {$orderBy} {$order} LIMIT %d OFFSET %d";
        $params = array_merge($params, [$huntId], $excludedUserIds, [$limit, $offset]);
        return (array) $this->wpdb->get_results($this->wpdb->prepare($query, $params), ARRAY_A);
    }

    public function findEngagedRiddleIds(int $userId, array $riddleIds): array
    {
        if ($riddleIds === []) {
            return [];
        }
        $table = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        return array_map('intval', $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT DISTINCT enigme_id FROM {$table} WHERE user_id = %d AND enigme_id IN ({$placeholders})",
            array_merge([$userId], $riddleIds)
        )));
    }

    /** @param int[] $riddleIds */
    private function aggregateAttempts(
        string $expression,
        array $riddleIds,
        ?string $startAt,
        ?string $endAt
    ): int {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        // A hunt attempt total only represents final riddle submissions.
        $where = "enigme_id IN ({$placeholders}) AND etape_id IS NULL";
        $params = $riddleIds;

        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT {$expression} FROM {$table} WHERE {$where}", ...$params)
        );
    }
}
