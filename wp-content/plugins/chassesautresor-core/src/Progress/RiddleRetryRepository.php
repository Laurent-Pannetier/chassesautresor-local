<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Persist the active retry delay independently from the attempt history. */
final class RiddleRetryRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function find(int $userId, int $riddleId): ?object
    {
        $table = $this->wpdb->prefix . 'enigme_delais_soumission';
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT retry_at_utc, source_tentative_uid FROM {$table} WHERE user_id = %d AND enigme_id = %d",
                $userId,
                $riddleId
            )
        );

        return is_object($row) ? $row : null;
    }

    public function save(
        int $userId,
        int $riddleId,
        string $retryAtUtc,
        string $attemptUid,
        string $updatedAtUtc
    ): bool {
        $table = $this->wpdb->prefix . 'enigme_delais_soumission';
        $sql = $this->wpdb->prepare(
            "INSERT INTO {$table} "
                . '(user_id, enigme_id, retry_at_utc, source_tentative_uid, updated_at_utc) '
                . 'VALUES (%d, %d, %s, %s, %s) ON DUPLICATE KEY UPDATE '
                . 'retry_at_utc = VALUES(retry_at_utc), '
                . 'source_tentative_uid = VALUES(source_tentative_uid), '
                . 'updated_at_utc = VALUES(updated_at_utc)',
            $userId,
            $riddleId,
            $retryAtUtc,
            $attemptUid,
            $updatedAtUtc
        );

        return $this->wpdb->query($sql) !== false;
    }

    public function deleteForRiddle(int $riddleId): int
    {
        $deleted = $this->wpdb->delete(
            $this->wpdb->prefix . 'enigme_delais_soumission',
            ['enigme_id' => $riddleId],
            ['%d']
        );

        return is_int($deleted) ? $deleted : 0;
    }
}
