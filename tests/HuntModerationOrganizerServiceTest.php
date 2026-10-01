<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntModerationOrganizerService;
use PHPUnit\Framework\TestCase;

final class HuntModerationOrganizerServiceTest extends TestCase
{
    public function testPendingOrganizerAndFirstAssociatedUserArePromoted(): void
    {
        $published = [];
        $promoted = [];

        (new HuntModerationOrganizerService())->promote(
            12,
            [30, 31],
            static fn(int $id): string => 'pending',
            static function (int $id) use (&$published): void { $published[] = $id; },
            static function (int $id) use (&$promoted): void { $promoted[] = $id; }
        );

        self::assertSame([12], $published);
        self::assertSame([30], $promoted);
    }

    public function testPublishedOrganizerIsNotPublishedAgain(): void
    {
        $published = [];

        (new HuntModerationOrganizerService())->promote(
            12,
            [],
            static fn(int $id): string => 'publish',
            static function (int $id) use (&$published): void { $published[] = $id; },
            static function (): void {}
        );

        self::assertSame([], $published);
    }
}
