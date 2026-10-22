<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** MySQL trophy award ledger. */
final class TrophyAwardRepository implements TrophyAwardStore
{
    public function __construct(private readonly object $wpdb)
    {
    }

    public function transaction(callable $callback): mixed
    {
        $lock = $this->wpdb->prefix . 'cat_trophy_awards';
        $got = $this->wpdb->get_var($this->wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock));
        if ((string) $got !== '1') {
            throw new \RuntimeException('trophy award lock unavailable');
        }
        $this->wpdb->query('START TRANSACTION');
        try {
            $result = $callback();
            $this->wpdb->query('COMMIT');

            return $result;
        } catch (\Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        } finally {
            $this->wpdb->query($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    public function insert(
        int $trophyId,
        string $recipientType,
        int $recipientId,
        int $actorUserId,
        string $eventKey,
        array $context
    ): int {
        if ($this->exists($trophyId, $recipientType, $recipientId)) {
            return 0;
        }

        $this->wpdb->suppress_errors(true);
        $inserted = $this->wpdb->insert(
            $this->table(),
            [
                'trophy_id' => $trophyId,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'actor_user_id' => $actorUserId,
                'context_type' => $this->contextType($context),
                'context_id' => $this->contextId($context),
                'event_key' => substr($eventKey, 0, 190),
                'context' => $this->encode($context),
                'awarded_at' => $this->now(),
            ],
            ['%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s']
        );
        $this->wpdb->suppress_errors(false);
        if ($inserted === false) {
            if ($this->exists($trophyId, $recipientType, $recipientId)) {
                return 0;
            }
            throw new \RuntimeException('trophy award insert failed');
        }

        return (int) $this->wpdb->insert_id;
    }

    public function listForRecipients(int $userId, int $teamId = 0): array
    {
        if ($userId <= 0) {
            return [];
        }

        if ($teamId > 0) {
            $query = $this->wpdb->prepare(
                "SELECT * FROM {$this->table()}
                WHERE (recipient_type = 'user' AND recipient_id = %d)
                    OR (recipient_type = 'team' AND recipient_id = %d)
                ORDER BY awarded_at DESC, id DESC",
                $userId,
                $teamId
            );
        } else {
            $query = $this->wpdb->prepare(
                "SELECT * FROM {$this->table()}
                WHERE recipient_type = 'user' AND recipient_id = %d
                ORDER BY awarded_at DESC, id DESC",
                $userId
            );
        }
        $rows = $this->wpdb->get_results($query, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public function countForRecipients(int $userId, int $teamId = 0): int
    {
        if ($userId <= 0) {
            return 0;
        }

        if ($teamId > 0) {
            return (int) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(DISTINCT trophy_id) FROM {$this->table()}
                WHERE (recipient_type = 'user' AND recipient_id = %d)
                    OR (recipient_type = 'team' AND recipient_id = %d)",
                $userId,
                $teamId
            ));
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT trophy_id) FROM {$this->table()}
            WHERE recipient_type = 'user' AND recipient_id = %d",
            $userId
        ));
    }

    private function exists(int $trophyId, string $recipientType, int $recipientId): bool
    {
        return (bool) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT 1 FROM {$this->table()}
            WHERE trophy_id = %d AND recipient_type = %s AND recipient_id = %d LIMIT 1",
            $trophyId,
            $recipientType,
            $recipientId
        ));
    }

    /** @param array<string, mixed> $context */
    private function contextType(array $context): string
    {
        $type = sanitize_key((string) ($context['type'] ?? ''));

        return substr($type, 0, 40);
    }

    /** @param array<string, mixed> $context */
    private function contextId(array $context): int
    {
        return max(0, (int) ($context['id'] ?? 0));
    }

    /** @param array<string, mixed> $context */
    private function encode(array $context): string
    {
        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($context);
        }

        return (string) json_encode($context);
    }

    private function table(): string
    {
        return $this->wpdb->prefix . 'trophy_attributions';
    }

    private function now(): string
    {
        return function_exists('current_time')
            ? (string) current_time('mysql')
            : gmdate('Y-m-d H:i:s');
    }
}
