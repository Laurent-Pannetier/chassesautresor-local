<?php

declare(strict_types=1);

/**
 * Handle points storage in a dedicated table instead of user meta.
 */
class PointsRepository
{
    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Fully qualified table name.
     *
     * @var string
     */
    private string $table;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'user_points';
    }

    /**
     * Return current balance for a user.
     */
    public function getBalance(int $userId): int
    {
        $sql = $this->wpdb->prepare(
            "SELECT balance FROM {$this->table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $userId
        );

        $balance = $this->wpdb->get_var($sql);

        return $balance !== null ? (int) $balance : 0;
    }

    /**
     * Record a points operation and return the new balance.
     */
    public function addPoints(int $userId, int $delta, string $reason = ''): int
    {
        $current = $this->getBalance($userId);
        $newBalance = max(0, $current + $delta);

        $this->wpdb->insert(
            $this->table,
            [
                'user_id' => $userId,
                'balance' => $newBalance,
                'points'  => $delta,
                'reason'  => $reason,
            ],
            ['%d', '%d', '%d', '%s']
        );

        return $newBalance;
    }

    /**
     * Record a conversion request with pending status and return the inserted row ID.
     */
    public function logConversionRequest(int $userId, int $points): int
    {
        $current = $this->getBalance($userId);
        $newBalance = max(0, $current + $points);

        $this->wpdb->insert(
            $this->table,
            [
                'user_id'        => $userId,
                'balance'        => $newBalance,
                'points'         => $points,
                'reason'         => 'conversion',
                'request_status' => 'pending',
                'request_date'   => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s']
        );

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Update status and related dates for a conversion request.
     */
    public function updateRequestStatus(int $id, string $status, array $dates = []): void
    {
        $data = ['request_status' => $status];
        $format = ['%s'];

        if (isset($dates['settlement_date'])) {
            $data['settlement_date'] = $dates['settlement_date'];
            $format[] = '%s';
        }

        if (isset($dates['cancelled_date'])) {
            $data['cancelled_date'] = $dates['cancelled_date'];
            $format[] = '%s';
        }

        if (isset($dates['cancellation_reason'])) {
            $data['cancellation_reason'] = $dates['cancellation_reason'];
            $format[] = '%s';
        }

        $this->wpdb->update($this->table, $data, ['id' => $id], $format, ['%d']);
    }
}
