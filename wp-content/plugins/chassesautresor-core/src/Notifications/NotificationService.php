<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Create one notification event and distribute it to its user recipients. */
final class NotificationService
{
    public const CHANNEL_CENTER = 'center';
    public const CHANNEL_OVERLAY = 'overlay';
    public const CHANNEL_BOTH = 'both';

    public function __construct(private readonly NotificationStore $store)
    {
    }

    /**
     * @param int[] $userIds
     */
    public function notify(
        string $type,
        string $sourceType,
        int $sourceId,
        string $title,
        string $body,
        int $priority,
        string $channel,
        string $eventKey,
        array $userIds
    ): int {
        $type = $this->key($type, 'system');
        $sourceType = $this->key($sourceType, 'system');
        $channel = in_array($channel, [self::CHANNEL_CENTER, self::CHANNEL_OVERLAY, self::CHANNEL_BOTH], true)
            ? $channel
            : self::CHANNEL_CENTER;
        $eventKey = trim($eventKey);
        $recipients = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $userId): bool => $userId > 0
        )));
        if ($eventKey === '' || $recipients === []) {
            return 0;
        }

        $notificationId = $this->store->create([
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => max(0, $sourceId),
            'title' => $this->plainText($title, 190),
            'body' => $this->plainText($body, 2000),
            'priority' => max(0, min(100, $priority)),
            'channel' => $channel,
            'event_key' => substr($eventKey, 0, 190),
            'created_at' => $this->now(),
        ]);
        foreach ($recipients as $userId) {
            $this->store->addRecipient($notificationId, $userId);
        }

        return $notificationId;
    }

    private function key(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '_', $value);

        return $value !== '' ? substr($value, 0, 40) : $fallback;
    }

    private function plainText(string $value, int $limit): string
    {
        $value = function_exists('wp_strip_all_tags')
            ? wp_strip_all_tags($value, true)
            : strip_tags($value);
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return function_exists('mb_substr') ? mb_substr($value, 0, $limit) : substr($value, 0, $limit);
    }

    private function now(): string
    {
        return function_exists('current_time')
            ? (string) current_time('mysql')
            : gmdate('Y-m-d H:i:s');
    }
}
