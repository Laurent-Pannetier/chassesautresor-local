<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Serialize every submission for one player and one riddle across PHP workers. */
class RiddleSubmissionLock
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function acquire(int $userId, int $riddleId): bool
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT GET_LOCK(%s, 0)', $this->key($userId, $riddleId))
        ) === 1;
    }

    public function release(int $userId, int $riddleId): void
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return;
        }

        $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $this->key($userId, $riddleId))
        );
    }

    private function key(int $userId, int $riddleId): string
    {
        return "cat_submission_{$userId}_{$riddleId}";
    }
}
