<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleEngagementApplicationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleEngagementApplicationService.php';

final class RiddleEngagementApplicationServiceTest extends TestCase {
    public function testUpdatesStatusBeforePersistingEngagement(): void {
        $events = [];
        $result = (new RiddleEngagementApplicationService())->engage(
            7,
            12,
            static function (...$arguments) use (&$events): bool {
                $events[] = ['status', $arguments];
                return true;
            },
            static function (...$arguments) use (&$events): bool {
                $events[] = ['persist', $arguments];
                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertSame([
            ['status', [12, 7, 'en_cours', true]],
            ['persist', [7, 12]],
        ], $events);
    }

    public function testStopsWhenStatusMutationFails(): void {
        $persisted = false;
        $result = (new RiddleEngagementApplicationService())->engage(
            7,
            12,
            static fn (): bool => false,
            static function () use (&$persisted): bool {
                $persisted = true;
                return true;
            }
        );

        $this->assertFalse($result);
        $this->assertFalse($persisted);
    }
}
