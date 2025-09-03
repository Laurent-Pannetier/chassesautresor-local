<?php

declare(strict_types=1);

/**
 * Handle storage and retrieval of user messages.
 */
class UserMessageRepository
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
        $this->table = $wpdb->prefix . 'user_messages';
    }

    /**
     * Insert a new message and return its identifier or 0 on failure.
     */
    public function insert(
        int $userId,
        string $message,
        string $status,
        ?string $expiresAt = null,
        ?string $locale = null
    ): int {
        $result = $this->wpdb->insert(
            $this->table,
            [
                'user_id'    => $userId,
                'message'    => $message,
                'status'     => $status,
                'expires_at' => $expiresAt,
                'locale'     => $locale,
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if (false === $result) {
            error_log('Failed to insert user message: ' . $this->wpdb->last_error);

            return 0;
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Update an existing message.
     */
    public function update(int $id, array $data): void
    {
        $formats = [];
        foreach ($data as $key => $value) {
            $formats[] = is_int($value) ? '%d' : '%s';
        }

        $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id],
            $formats,
            ['%d']
        );
    }

    /**
     * Delete a message by ID.
     */
    public function delete(int $id): void
    {
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    /**
     * Retrieve messages filtered by user, status or expiration.
     *
     * @param int|null    $userId  Optional user identifier.
     * @param string|null $status  Optional status filter.
     * @param bool|null   $expired True to get expired messages, false for active messages, null for all.
     *
     * @return array[]
     */
    public function get(?int $userId = null, ?string $status = null, ?bool $expired = null): array
    {
        $rows = $this->wpdb->get_results("SELECT * FROM {$this->table}", ARRAY_A);
        $now  = current_time('mysql');

        return array_values(array_filter($rows, function ($row) use ($userId, $status, $expired, $now) {
            if ($userId !== null && (int) $row['user_id'] !== $userId) {
                return false;
            }

            if ($status !== null && $row['status'] !== $status) {
                return false;
            }

            if ($expired === true) {
                return $row['expires_at'] !== null && $row['expires_at'] < $now;
            }

            if ($expired === false) {
                return $row['expires_at'] === null || $row['expires_at'] >= $now;
            }

            return true;
        }));
    }

    /**
     * Remove messages with an expired timestamp.
     */
    public function purgeExpired(): void
    {
        $sql = "DELETE FROM {$this->table} WHERE expires_at IS NOT NULL AND expires_at < NOW()";
        $this->wpdb->query($sql);
    }
}
