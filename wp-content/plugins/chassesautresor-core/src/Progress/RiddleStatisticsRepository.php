<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class RiddleStatisticsRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function aggregateAttempts(
        int $riddleId,
        string $expression,
        ?string $result = null,
        ?string $startAt = null,
        ?string $endAt = null
    ): int {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $where = 'enigme_id = %d';
        $params = [$riddleId];
        if ($result !== null) {
            $where .= ' AND resultat = %s';
            $params[] = $result;
        }
        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT {$expression} FROM {$table} WHERE {$where}", ...$params)
        );
    }

    public function countEngagedPlayers(
        int $riddleId,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): int {
        $table = $this->wpdb->prefix . 'engagements';
        $where = 'enigme_id = %d';
        $params = [$riddleId];
        if ($startAt !== null && $endAt !== null) {
            $where .= ' AND date_engagement BETWEEN %s AND %s';
            $params[] = $startAt;
            $params[] = $endAt;
        }
        if ($excludedUserIds !== []) {
            $where .= ' AND user_id NOT IN (' . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE {$where}", ...$params)
        );
    }

    public function listSolvers(int $riddleId, array $excludedUserIds = []): array
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $exclude = '';
        $params = [$riddleId];
        if ($excludedUserIds !== []) {
            $exclude = ' AND user_id NOT IN ('
                . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }
        $params[] = $riddleId;
        $sql = "SELECT r.user_id, u.user_login AS username, r.resolution_date, COUNT(*) AS tentatives "
            . "FROM (SELECT user_id, MIN(date_tentative) AS resolution_date FROM {$table} "
            . "WHERE enigme_id = %d AND resultat = 'bon'{$exclude} GROUP BY user_id) r "
            . "JOIN {$table} t ON t.enigme_id = %d AND t.user_id = r.user_id "
            . "AND t.date_tentative <= r.resolution_date JOIN {$this->wpdb->users} u ON u.ID = r.user_id "
            . 'GROUP BY r.user_id, u.user_login, r.resolution_date ORDER BY r.resolution_date ASC';
        return (array) $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
    }
}
