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

    public function countEngagements(int $huntId): int
    {
        $table = $this->wpdb->prefix . 'engagements';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE chasse_id = %d", $huntId)
        );
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
        $where = "enigme_id IN ({$placeholders})";
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
