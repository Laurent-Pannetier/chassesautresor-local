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
}
