<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query completion state for hunt riddles.
 */
class HuntProgressRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @param int[] $riddleIds */
    public function countSolved(int $userId, array $riddleIds): int
    {
        if ($riddleIds === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'enigme_statuts_utilisateur';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT COUNT(DISTINCT enigme_id) FROM {$table} "
            . "WHERE user_id = %d AND statut IN ('resolue','terminee','terminée') "
            . "AND enigme_id IN ({$placeholders})";

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, array_merge([$userId], $riddleIds))
        );
    }

    /** @param int[] $riddleIds */
    public function countEngaged(int $userId, array $riddleIds): int
    {
        if ($riddleIds === []) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'engagements';
        $placeholders = implode(',', array_fill(0, count($riddleIds), '%d'));
        $sql = "SELECT COUNT(DISTINCT enigme_id) FROM {$table} "
            . "WHERE user_id = %d AND enigme_id IN ({$placeholders})";

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, array_merge([$userId], $riddleIds))
        );
    }
}
