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
}
