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
}
