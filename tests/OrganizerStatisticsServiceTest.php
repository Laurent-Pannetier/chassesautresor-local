<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\OrganizerStatisticsService;
use PHPUnit\Framework\TestCase;

final class OrganizerStatisticsServiceTest extends TestCase
{
    public function testUniquePlayersAreCountedOnceAcrossTheOrganizerHunts(): void
    {
        $service = new OrganizerStatisticsService();
        $seen = [];
        $count = $service->countUniquePlayers(
            [4, 0, 9],
            [1, 2],
            static function (array $huntIds, array $excluded) use (&$seen): int {
                $seen = [$huntIds, $excluded];

                return 6;
            }
        );

        self::assertSame(6, $count);
        self::assertSame([[4, 9], [1, 2]], $seen);
    }

    public function testEmptyHuntListSkipsThePlayerQuery(): void
    {
        $service = new OrganizerStatisticsService();
        $called = false;
        $count = $service->countUniquePlayers([0], [], static function () use (&$called): int {
            $called = true;

            return 3;
        });

        self::assertSame(0, $count);
        self::assertFalse($called);
    }

    public function testCollectedPointsSumEachHunt(): void
    {
        $service = new OrganizerStatisticsService();
        $total = $service->sumCollectedPoints([3, 0, 8], static fn (int $huntId): int => $huntId * 10);

        self::assertSame(110, $total);
    }
}
