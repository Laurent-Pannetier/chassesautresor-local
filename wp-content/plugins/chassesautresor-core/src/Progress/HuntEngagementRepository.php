<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Persist and query hunt-level engagements.
 */
class HuntEngagementRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function exists(int $userId, int $huntId): bool
    {
        $table = $this->wpdb->prefix . 'engagements';

        return (bool) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT 1 FROM {$table} WHERE user_id = %d AND chasse_id = %d AND enigme_id IS NULL LIMIT 1",
                $userId,
                $huntId
            )
        );
    }

    public function countByHunt(int $huntId): int
    {
        $table = $this->wpdb->prefix . 'engagements';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE chasse_id = %d", $huntId)
        );
    }

    public function countParticipants(
        int $huntId,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): int
    {
        $table = $this->wpdb->prefix . 'engagements';
        $where = 'chasse_id = %d AND enigme_id IS NULL';
        $params = [$huntId];

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
            $this->wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE {$where}",
                ...$params
            )
        );
    }

    public function insert(int $userId, int $huntId, string $engagedAt): bool
    {
        $inserted = $this->wpdb->insert(
            $this->wpdb->prefix . 'engagements',
            [
                'user_id' => $userId,
                'chasse_id' => $huntId,
                'date_engagement' => $engagedAt,
            ],
            ['%d', '%d', '%s']
        );

        return (bool) $inserted && empty($this->wpdb->last_error);
    }

    public function countUniquePlayersForHunts(array $huntIds, array $excludedUserIds = []): int
    {
        $table = $this->wpdb->prefix . 'engagements';
        $where = 'enigme_id IS NULL AND chasse_id IN ('
            . implode(',', array_fill(0, count($huntIds), '%d')) . ')';
        $params = $huntIds;
        if ($excludedUserIds !== []) {
            $where .= ' AND user_id NOT IN (' . implode(',', array_fill(0, count($excludedUserIds), '%d')) . ')';
            $params = array_merge($params, $excludedUserIds);
        }
        return count($this->wpdb->get_col($this->wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table} WHERE {$where}",
            $params
        )));
    }
}
