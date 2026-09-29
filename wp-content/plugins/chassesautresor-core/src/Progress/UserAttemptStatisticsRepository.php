<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Query attempt totals for a user account.
 */
class UserAttemptStatisticsRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /** @return array{pending: int, total: int, success: int} */
    public function summarize(int $userId): array
    {
        $table = $this->wpdb->prefix . 'enigme_tentatives';
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT COUNT(*) AS total,"
                . " SUM(CASE WHEN resultat = 'attente' AND traitee = 0 THEN 1 ELSE 0 END) AS pending,"
                . " SUM(CASE WHEN resultat = 'bon' THEN 1 ELSE 0 END) AS success"
                . " FROM {$table} WHERE user_id = %d",
                $userId
            ),
            ARRAY_A
        );

        return [
            'pending' => (int) ($row['pending'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['success'] ?? 0),
        ];
    }
}
