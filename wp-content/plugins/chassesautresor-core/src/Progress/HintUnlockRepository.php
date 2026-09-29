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
}
