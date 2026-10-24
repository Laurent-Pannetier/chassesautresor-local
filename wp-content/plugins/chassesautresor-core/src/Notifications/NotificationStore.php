<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Persistence contract for notification events and deliveries. */
interface NotificationStore
{
    /** @param array<string, mixed> $notification */
    public function create(array $notification): int;

    public function eventId(string $eventKey): int;

    public function addRecipient(int $notificationId, int $userId): void;

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array;

    public function countForUser(int $userId): int;

    public function unreadCount(int $userId): int;

    public function markRead(int $notificationId, int $userId): bool;

    public function markAllRead(int $userId): void;

    /** @return array<string, mixed>|null */
    public function nextOverlay(int $userId): ?array;

    public function renewOverlayClaim(int $notificationId, int $userId, string $claimToken): bool;

    public function markOverlaySeen(int $notificationId, int $userId, string $claimToken): bool;
}
