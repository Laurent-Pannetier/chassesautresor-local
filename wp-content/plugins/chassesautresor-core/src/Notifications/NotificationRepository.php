<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** MySQL persistence for the notification center. */
final class NotificationRepository implements NotificationStore
{
    public function __construct(private readonly object $wpdb)
    {
    }

    public function create(array $notification): int
    {
        $eventKey = (string) ($notification['event_key'] ?? '');
        $existing = $this->idByEventKey($eventKey);
        if ($existing > 0) {
            return $existing;
        }

        $inserted = $this->wpdb->insert(
            $this->notifications(),
            [
                'type' => (string) $notification['type'],
                'source_type' => (string) $notification['source_type'],
                'source_id' => $notification['source_id'],
                'title' => (string) $notification['title'],
                'body' => (string) $notification['body'],
                'priority' => (int) $notification['priority'],
                'channel' => (string) $notification['channel'],
                'event_key' => $eventKey,
                'created_at' => (string) $notification['created_at'],
            ],
            ['%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
        );
        if ($inserted === false) {
            $existing = $this->idByEventKey($eventKey);
            if ($existing > 0) {
                return $existing;
            }
            throw new \RuntimeException('notification insert failed');
        }

        return (int) $this->wpdb->insert_id;
    }

    public function addRecipient(int $notificationId, int $userId): void
    {
        if ($notificationId <= 0 || $userId <= 0 || $this->recipientExists($notificationId, $userId)) {
            return;
        }

        $inserted = $this->wpdb->insert(
            $this->recipients(),
            [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'created_at' => $this->now(),
            ],
            ['%d', '%d', '%s']
        );
        if ($inserted === false && !$this->recipientExists($notificationId, $userId)) {
            throw new \RuntimeException('notification recipient insert failed');
        }
    }

    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT n.id, n.type, n.source_type, n.source_id, n.title, n.body,
                    n.priority, n.channel, n.created_at, r.read_at, r.overlay_seen_at
                FROM {$this->notifications()} n
                INNER JOIN {$this->recipients()} r ON r.notification_id = n.id
                WHERE r.user_id = %d AND n.channel IN ('center', 'both')
                ORDER BY n.created_at DESC, n.id DESC
                LIMIT %d OFFSET %d",
                $userId,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$this->recipients()} r
            INNER JOIN {$this->notifications()} n ON n.id = r.notification_id
            WHERE r.user_id = %d AND r.read_at IS NULL AND n.channel IN ('center', 'both')",
            $userId
        ));
    }

    public function countForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$this->recipients()} r
            INNER JOIN {$this->notifications()} n ON n.id = r.notification_id
            WHERE r.user_id = %d AND n.channel IN ('center', 'both')",
            $userId
        ));
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $updated = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->recipients()} SET read_at = COALESCE(read_at, %s)
            WHERE notification_id = %d AND user_id = %d",
            $this->now(),
            $notificationId,
            $userId
        ));

        return $updated !== false && $this->recipientExists($notificationId, $userId);
    }

    public function markAllRead(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->recipients()} SET read_at = %s WHERE user_id = %d AND read_at IS NULL",
            $this->now(),
            $userId
        ));
    }

    public function nextOverlay(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $lock = $this->wpdb->prefix . 'cat_notification_overlay_' . $userId;
        $got = $this->wpdb->get_var($this->wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock));
        if ((string) $got !== '1') {
            return null;
        }

        try {
            $now = $this->now();
            $activeClaim = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT 1 FROM {$this->recipients()}
                WHERE user_id = %d
                    AND overlay_seen_at IS NULL
                    AND overlay_claimed_at >= DATE_SUB(%s, INTERVAL 2 MINUTE)
                LIMIT 1",
                $userId,
                $now
            ));
            if ((string) $activeClaim === '1') {
                return null;
            }

            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT n.id, n.type, n.source_type, n.source_id, n.title, n.body,
                        n.priority, n.channel, n.created_at, r.read_at, r.overlay_seen_at
                    FROM {$this->notifications()} n
                    INNER JOIN {$this->recipients()} r ON r.notification_id = n.id
                    WHERE r.user_id = %d
                        AND r.overlay_seen_at IS NULL
                        AND (
                            r.overlay_claimed_at IS NULL
                            OR r.overlay_claimed_at < DATE_SUB(%s, INTERVAL 2 MINUTE)
                        )
                        AND n.channel IN ('overlay', 'both')
                    ORDER BY n.priority DESC, n.created_at ASC, n.id ASC
                    LIMIT 1",
                    $userId,
                    $now
                ),
                ARRAY_A
            );
            if (!is_array($row)) {
                return null;
            }
            $claimToken = bin2hex(random_bytes(16));
            $claimed = $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->recipients()}
                SET overlay_claimed_at = %s, overlay_claim_token = %s
                WHERE notification_id = %d
                    AND user_id = %d
                    AND overlay_seen_at IS NULL
                    AND (
                        overlay_claimed_at IS NULL
                        OR overlay_claimed_at < DATE_SUB(%s, INTERVAL 2 MINUTE)
                    )",
                $now,
                $claimToken,
                (int) $row['id'],
                $userId,
                $now
            ));
            if ($claimed !== 1) {
                return null;
            }
            $row['claim_token'] = $claimToken;

            return $row;
        } finally {
            $this->wpdb->query($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    public function renewOverlayClaim(int $notificationId, int $userId, string $claimToken): bool
    {
        if ($notificationId <= 0 || $userId <= 0 || !$this->claimTokenIsValid($claimToken)) {
            return false;
        }

        $updated = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->recipients()} SET overlay_claimed_at = %s
            WHERE notification_id = %d
                AND user_id = %d
                AND overlay_claim_token = %s
                AND overlay_seen_at IS NULL",
            $this->now(),
            $notificationId,
            $userId,
            $claimToken
        ));

        return $updated !== false && $updated > 0;
    }

    public function markOverlaySeen(int $notificationId, int $userId, string $claimToken): bool
    {
        if ($notificationId <= 0 || $userId <= 0 || !$this->claimTokenIsValid($claimToken)) {
            return false;
        }

        $updated = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->recipients()}
            SET overlay_seen_at = COALESCE(overlay_seen_at, %s),
                overlay_claimed_at = NULL,
                overlay_claim_token = NULL
            WHERE notification_id = %d AND user_id = %d AND overlay_claim_token = %s",
            $this->now(),
            $notificationId,
            $userId,
            $claimToken
        ));

        return $updated !== false && $updated > 0;
    }

    private function idByEventKey(string $eventKey): int
    {
        if ($eventKey === '') {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->notifications()} WHERE event_key = %s LIMIT 1",
            $eventKey
        ));
    }

    private function recipientExists(int $notificationId, int $userId): bool
    {
        return (bool) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT 1 FROM {$this->recipients()}
            WHERE notification_id = %d AND user_id = %d LIMIT 1",
            $notificationId,
            $userId
        ));
    }

    private function claimTokenIsValid(string $claimToken): bool
    {
        return preg_match('/\A[a-f0-9]{32}\z/', $claimToken) === 1;
    }

    private function notifications(): string
    {
        return $this->wpdb->prefix . 'notifications';
    }

    private function recipients(): string
    {
        return $this->wpdb->prefix . 'notification_recipients';
    }

    private function now(): string
    {
        return function_exists('current_time')
            ? (string) current_time('mysql')
            : gmdate('Y-m-d H:i:s');
    }
}
