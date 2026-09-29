<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Persist and query hunt winners.
 */
class HuntWinnerRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function save(int $userId, int $huntId, string $wonAt): void
    {
        $this->wpdb->replace(
            $this->wpdb->prefix . 'chasse_winners',
            [
                'user_id' => $userId,
                'chasse_id' => $huntId,
                'date_win' => $wonAt,
            ],
            ['%d', '%d', '%s']
        );
    }

    public function countByUser(int $userId): int
    {
        $table = $this->wpdb->prefix . 'chasse_winners';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $userId)
        );
    }
}
