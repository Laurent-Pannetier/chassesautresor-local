<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntModerationNotificationService;
use ChassesAuTresor\Core\Messages\AccountMessageService;
use PHPUnit\Framework\TestCase;

final class ModerationAccountMessageRecorder extends AccountMessageService
{
    /** @var array<int, array<string, mixed>> */
    public array $flash = [];
    /** @var array<int, array<string, mixed>> */
    public array $persistent = [];

    public function __construct()
    {
    }

    public function addFlash(int $userId, array $payload): int
    {
        $this->flash[] = [$userId, $payload];
        return 1;
    }

    public function addPersistent(
        int $userId,
        string $key,
        array $payload,
        ?string $locale = null,
        ?int $expires = null
    ): int {
        $this->persistent[] = [$userId, $key, $payload];
        return 1;
    }
}

final class HuntModerationNotificationServiceTest extends TestCase
{
    public function testApprovalCreatesFlashAndSendsEmail(): void
    {
        $messages = new ModerationAccountMessageRecorder();
        $emails = [];
        $service = new HuntModerationNotificationService(
            $messages,
            static function (...$arguments) use (&$emails): void { $emails[] = $arguments; },
            static fn(int $organizerId): array => ['organizer@example.test']
        );

        $service->notify('valider', 5, 10, [7], 'La chasse', 'https://example.test/chasse');

        self::assertSame(7, $messages->flash[0][0]);
        self::assertSame('success', $messages->flash[0][1]['type']);
        self::assertSame(['organizer@example.test'], $emails[0][0]);
    }

    public function testCorrectionCreatesScopedPersistentMessages(): void
    {
        $messages = new ModerationAccountMessageRecorder();
        $service = new HuntModerationNotificationService(
            $messages,
            static function (): void {},
            static fn(int $organizerId): array => ['organizer@example.test']
        );

        $service->notify('correction', 5, 10, [7], 'La chasse', 'https://example.test/chasse', 'À revoir');

        self::assertCount(2, $messages->persistent);
        self::assertSame('correction_chasse_10', $messages->persistent[0][1]);
        self::assertSame(10, $messages->persistent[0][2]['chasse_scope']);
        self::assertStringContainsString('À revoir', $messages->persistent[0][2]['text']);
    }
}
