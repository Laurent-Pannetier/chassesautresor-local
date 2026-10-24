<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Notifications\NotificationStore;
use PHPUnit\Framework\TestCase;

final class MemoryNotificationStore implements NotificationStore
{
    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $recipients = [];

    public function create(array $notification): int
    {
        foreach ($this->notifications as $id => $existing) {
            if ($existing['event_key'] === $notification['event_key']) {
                return $id;
            }
        }
        $id = count($this->notifications) + 1;
        $this->notifications[$id] = $notification;

        return $id;
    }

    public function eventId(string $eventKey): int
    {
        foreach ($this->notifications as $id => $existing) {
            if ($existing['event_key'] === $eventKey) {
                return (int) $id;
            }
        }

        return 0;
    }

    public function addRecipient(int $notificationId, int $userId): void
    {
        $this->recipients[$notificationId][$userId] ??= [
            'read_at' => null,
            'overlay_seen_at' => null,
        ];
    }

    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return [];
    }

    public function unreadCount(int $userId): int
    {
        $count = 0;
        foreach ($this->recipients as $recipients) {
            if (isset($recipients[$userId]) && $recipients[$userId]['read_at'] === null) {
                $count++;
            }
        }

        return $count;
    }

    public function countForUser(int $userId): int
    {
        return $this->unreadCount($userId);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        if (!isset($this->recipients[$notificationId][$userId])) {
            return false;
        }
        $this->recipients[$notificationId][$userId]['read_at'] = 'now';

        return true;
    }

    public function markAllRead(int $userId): void
    {
    }

    public function nextOverlay(int $userId): ?array
    {
        return null;
    }

    public function renewOverlayClaim(int $notificationId, int $userId, string $claimToken): bool
    {
        return isset($this->recipients[$notificationId][$userId]);
    }

    public function markOverlaySeen(int $notificationId, int $userId, string $claimToken): bool
    {
        return isset($this->recipients[$notificationId][$userId]);
    }
}

final class NotificationServiceTest extends TestCase
{
    public function testOneEventIsDistributedOnceToUniqueUsers(): void
    {
        $store = new MemoryNotificationStore();
        $service = new NotificationService($store);

        $first = $service->notify(
            'trophy_awarded',
            'trophy',
            12,
            'Bravo',
            'Un trophée',
            100,
            NotificationService::CHANNEL_BOTH,
            'award:1',
            [4, 4, 8, 0]
        );
        $second = $service->notify(
            'trophy_awarded',
            'trophy',
            12,
            'Bravo',
            'Un trophée',
            100,
            NotificationService::CHANNEL_BOTH,
            'award:1',
            [4, 8]
        );

        self::assertSame($first, $second);
        self::assertCount(1, $store->notifications);
        self::assertSame([4, 8], array_keys($store->recipients[$first]));
        self::assertSame(1, $store->unreadCount(4));
    }

    public function testLongEventKeysUseTheirFullContentForIdentity(): void
    {
        $store = new MemoryNotificationStore();
        $service = new NotificationService($store);
        $prefix = str_repeat('a', 200);

        $first = $service->notify('system', 'system', 0, 'A', '', 0, 'center', $prefix . '1', [4]);
        $second = $service->notify('system', 'system', 0, 'B', '', 0, 'center', $prefix . '2', [4]);

        self::assertNotSame($first, $second);
        self::assertCount(2, $store->notifications);
        self::assertNotSame(
            $store->notifications[$first]['event_key'],
            $store->notifications[$second]['event_key']
        );
    }
}
