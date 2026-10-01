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

    public function beginTransaction(): void
    {
        $this->wpdb->query('START TRANSACTION');
    }

    public function commit(): void
    {
        $this->wpdb->query('COMMIT');
    }

    public function rollBack(): void
    {
        $this->wpdb->query('ROLLBACK');
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

    /**
     * @param int[] $hintIds
     * @return int[]
     */
    public function findUnlockedHintIds(int $userId, array $hintIds): array
    {
        $hintIds = array_values(array_unique(array_filter(array_map('intval', $hintIds))));
        if ($userId <= 0 || $hintIds === []) {
            return [];
        }

        $table = $this->wpdb->prefix . 'indices_deblocages';
        $placeholders = implode(', ', array_fill(0, count($hintIds), '%d'));
        $query = "SELECT indice_id FROM {$table} WHERE user_id = %d AND indice_id IN ({$placeholders})";
        $rows = $this->wpdb->get_col($this->wpdb->prepare($query, $userId, ...$hintIds));

        return array_values(array_unique(array_map('intval', (array) $rows)));
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
