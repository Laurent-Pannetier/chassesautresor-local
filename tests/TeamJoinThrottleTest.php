<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamJoinThrottle;
use PHPUnit\Framework\TestCase;

final class TeamJoinThrottleTest extends TestCase
{
    public function testFailuresBlockFurtherGuessesUntilReset(): void
    {
        $counts = [];
        $throttle = new TeamJoinThrottle(
            static function (int $userId) use (&$counts): int {
                return $counts[$userId] ?? 0;
            },
            static function (int $userId, int $count) use (&$counts): void {
                $counts[$userId] = $count;
            }
        );

        self::assertTrue($throttle->allows(4));
        for ($index = 0; $index < TeamJoinThrottle::LIMIT; $index++) {
            $throttle->recordFailure(4);
        }
        self::assertFalse($throttle->allows(4));
        $throttle->reset(4);
        self::assertTrue($throttle->allows(4));
    }
}
