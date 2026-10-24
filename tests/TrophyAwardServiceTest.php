<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Notifications\NotificationStore;
use ChassesAuTresor\Core\Trophies\TrophyAwardService;
use ChassesAuTresor\Core\Trophies\TrophyAwardStore;
use ChassesAuTresor\Core\Trophies\TrophyDefinition;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/MemoryTeamStore.php';

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

final class MemoryTrophyAwardStore implements TrophyAwardStore
{
    /** @var array<int, array<string, mixed>> */
    public array $awards = [];

    public function transaction(callable $callback): mixed
    {
        return $callback();
    }

    public function insert(
        int $trophyId,
        string $recipientType,
        int $recipientId,
        int $actorUserId,
        string $eventKey,
        array $context
    ): int {
        foreach ($this->awards as $award) {
            if (
                $award['trophy_id'] === $trophyId
                && $award['recipient_type'] === $recipientType
                && $award['recipient_id'] === $recipientId
            ) {
                return 0;
            }
        }
        $id = count($this->awards) + 1;
        $this->awards[$id] = [
            'trophy_id' => $trophyId,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'actor_user_id' => $actorUserId,
            'event_key' => $eventKey,
            'context' => $context,
        ];

        return $id;
    }

    public function listForRecipients(int $userId, int $teamId = 0): array
    {
        return [];
    }

    public function countForRecipients(int $userId, int $teamId = 0): int
    {
        return 0;
    }
}

final class TrophyNotificationStore implements NotificationStore
{
    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];

    /** @var array<int, int[]> */
    public array $recipients = [];

    public function create(array $notification): int
    {
        $id = count($this->notifications) + 1;
        $this->notifications[$id] = $notification;

        return $id;
    }

    public function eventId(string $eventKey): int
    {
        foreach ($this->notifications as $id => $existing) {
            if (($existing['event_key'] ?? '') === $eventKey) {
                return (int) $id;
            }
        }

        return 0;
    }

    public function addRecipient(int $notificationId, int $userId): void
    {
        $this->recipients[$notificationId][$userId] = $userId;
    }

    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return [];
    }

    public function unreadCount(int $userId): int
    {
        return 0;
    }

    public function countForUser(int $userId): int
    {
        return 0;
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return false;
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
        return false;
    }

    public function markOverlaySeen(int $notificationId, int $userId, string $claimToken): bool
    {
        return false;
    }
}

final class TrophyAwardServiceTest extends TestCase
{
    public function testBothScopeCreatesOneUserAndOneTeamAwardOnlyOnce(): void
    {
        $awards = new MemoryTrophyAwardStore();
        $notifications = new TrophyNotificationStore();
        $teams = new MemoryTeamStore();
        $teamId = $teams->insertTeam('Alpha', 'alpha', null, 'ABCD2345', 1);
        $teams->insertActiveMember($teamId, 1);
        $teams->insertActiveMember($teamId, 2);
        $teams->insertPlace(1, $teamId);
        $teams->insertPlace(2, $teamId);
        $service = new TrophyAwardService(
            $awards,
            new NotificationService($notifications),
            $teams
        );
        $trophy = new TrophyDefinition(
            9,
            'Pionnier',
            'Premier trophée.',
            0,
            'both',
            'code_event',
            [],
            true,
            true,
            '',
            '',
            100,
            'both'
        );

        self::assertSame([1, 2], $service->award($trophy, 1, 'event:test'));
        self::assertSame([], $service->award($trophy, 1, 'event:test'));
        self::assertSame(['user', 'team'], array_column($awards->awards, 'recipient_type'));
        self::assertCount(1, $notifications->notifications);
        self::assertSame([1, 2], array_values($notifications->recipients[1]));
    }
}
