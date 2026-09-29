<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query hint unlocks recorded for players.
 */
class HintUnlockRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function exists(int $userId, int $hintId): bool
    {
        $table = $this->wpdb->prefix . 'indices_deblocages';

        return (bool) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT 1 FROM {$table} WHERE user_id = %d AND indice_id = %d LIMIT 1",
                $userId,
                $hintId
            )
        );
    }

    public function insertUnlock(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        int $pointsSpent,
        string $unlockedAt
    ): bool {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'indices_deblocages',
            [
                'user_id' => $userId,
                'indice_id' => $hintId,
                'chasse_id' => $huntId,
                'enigme_id' => $riddleId,
                'points_depenses' => $pointsSpent,
                'date_deblocage' => $unlockedAt,
            ],
            ['%d', '%d', '%d', '%d', '%d', '%s']
        ) !== false;
    }

    public function insertEngagement(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        string $engagedAt
    ): bool {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'engagements',
            [
                'user_id' => $userId,
                'enigme_id' => $riddleId,
                'chasse_id' => $huntId,
                'indice_id' => $hintId,
                'date_engagement' => $engagedAt,
            ],
            ['%d', '%d', '%d', '%d', '%s']
        ) !== false;
    }
}
