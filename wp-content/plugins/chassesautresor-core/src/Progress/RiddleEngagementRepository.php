<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query riddle-level player engagements.
 */
class RiddleEngagementRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function exists(int $userId, int $riddleId): bool
    {
        $table = $this->wpdb->prefix . 'engagements';

        return (bool) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT 1 FROM {$table} WHERE user_id = %d AND enigme_id = %d AND indice_id IS NULL LIMIT 1",
                $userId,
                $riddleId
            )
        );
    }

    public function findEngagedAt(int $userId, int $riddleId): ?string
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return null;
        }

        $table = $this->wpdb->prefix . 'engagements';
        $value = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT MIN(date_engagement) FROM {$table}"
                . ' WHERE user_id = %d AND enigme_id = %d AND indice_id IS NULL',
                $userId,
                $riddleId
            )
        );

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function insert(int $userId, int $riddleId, string $engagedAt): bool
    {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'engagements',
            [
                'user_id' => $userId,
                'enigme_id' => $riddleId,
                'date_engagement' => $engagedAt,
            ],
            ['%d', '%d', '%s']
        ) !== false;
    }
}
