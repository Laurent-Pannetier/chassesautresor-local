<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Persist and retrieve riddle attempts.
 */
class RiddleAttemptRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function insert(array $attempt): bool
    {
        return $this->wpdb->insert($this->wpdb->prefix . 'enigme_tentatives', $attempt) !== false;
    }

    public function findByUid(string $uid): ?object
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $attempt = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE tentative_uid = %s", $uid)
        );

        return is_object($attempt) ? $attempt : null;
    }

    public function countForRiddle(int $riddleId): int
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE enigme_id = %d", $riddleId)
        );
    }

    public function countPendingForRiddle(int $riddleId): int
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE enigme_id = %d AND resultat = 'attente' AND traitee = 0",
                $riddleId
            )
        );
    }

    public function countForUserAndRiddleBetween(
        int $userId,
        int $riddleId,
        string $startAt,
        string $endAt
    ): int {
        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} "
                    . 'WHERE user_id = %d AND enigme_id = %d AND date_tentative BETWEEN %s AND %s',
                $userId,
                $riddleId,
                $startAt,
                $endAt
            )
        );
    }
}
