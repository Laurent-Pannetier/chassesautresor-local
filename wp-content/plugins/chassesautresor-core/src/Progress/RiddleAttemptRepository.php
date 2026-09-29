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
}
