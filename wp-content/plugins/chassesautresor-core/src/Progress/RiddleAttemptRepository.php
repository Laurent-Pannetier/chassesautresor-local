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

    public function getLastInsertId(): int
    {
        return max(0, (int) $this->wpdb->insert_id);
    }

    public function findByUid(string $uid): ?object
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $attempt = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE tentative_uid = %s", $uid)
        );

        return is_object($attempt) ? $attempt : null;
    }

    public function findForRiddle(int $riddleId, int $limit, int $offset): array
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $attempts = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE enigme_id = %d "
                    . "ORDER BY (resultat = 'attente') DESC, date_tentative DESC LIMIT %d OFFSET %d",
                $riddleId,
                $limit,
                $offset
            )
        );

        return is_array($attempts) ? $attempts : [];
    }

    public function findLatestPendingForUserAndRiddle(int $userId, int $riddleId): ?object
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $attempt = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id, date_tentative FROM {$table} "
                    . 'WHERE user_id = %d AND enigme_id = %d AND traitee = 0 '
                    . 'ORDER BY date_tentative DESC LIMIT 1',
                $userId,
                $riddleId
            )
        );

        return is_object($attempt) ? $attempt : null;
    }

    public function deleteForRiddle(int $riddleId): int
    {
        $deleted = $this->wpdb->delete(
            $this->wpdb->prefix . 'enigme_tentatives',
            ['enigme_id' => $riddleId],
            ['%d']
        );

        return is_int($deleted) ? $deleted : 0;
    }

    public function findUserRiddleStatus(int $userId, int $riddleId): ?string
    {
        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $status = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT statut FROM {$table} WHERE user_id = %d AND enigme_id = %d",
                $userId,
                $riddleId
            )
        );

        return is_string($status) ? $status : null;
    }

    public function markPendingAsProcessed(string $uid, string $result): bool
    {
        $updated = $this->wpdb->update(
            $this->wpdb->prefix . 'enigme_tentatives',
            ['resultat' => $result, 'traitee' => 1],
            ['tentative_uid' => $uid, 'resultat' => 'attente', 'traitee' => 0],
            ['%s', '%d'],
            ['%s', '%s', '%d']
        );

        return $updated === 1;
    }

    public function hasSuccessfulAttempt(int $userId, int $riddleId): bool
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $attemptId = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT 1 FROM {$table} "
                    . "WHERE user_id = %d AND enigme_id = %d AND resultat = 'bon' LIMIT 1",
                $userId,
                $riddleId
            )
        );

        return $attemptId !== null;
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

    public function countFailuresForUserAndRiddleBetween(
        int $userId,
        int $riddleId,
        string $startAt,
        string $endAt
    ): int {
        $table = $this->wpdb->prefix . 'enigme_tentatives';

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} "
                    . "WHERE user_id = %d AND enigme_id = %d AND resultat IN ('faux','variante') "
                    . 'AND date_tentative BETWEEN %s AND %s',
                $userId,
                $riddleId,
                $startAt,
                $endAt
            )
        );
    }
}
