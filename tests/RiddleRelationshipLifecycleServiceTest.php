<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleRelationshipLifecycleService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipLifecycleService.php';

class RiddleRelationshipLifecycleServiceTest extends TestCase {
    private RiddleRelationshipLifecycleService $service;

    protected function setUp(): void {
        $this->service = new RiddleRelationshipLifecycleService();
    }

    public function testAttachRequiresAValidHunt(): void {
        $calls = [];
        $attached = $this->service->attach(
            42,
            [(object) ['ID' => 12]],
            static fn (int $huntId): bool => $huntId === 12,
            static function (int $huntId, int $riddleId) use (&$calls): bool {
                $calls[] = [$huntId, $riddleId];
                return true;
            }
        );

        $this->assertTrue($attached);
        $this->assertSame([[12, 42]], $calls);
        $this->assertFalse($this->service->attach(42, 99, static fn (): bool => false, static fn (): bool => true));
    }

    public function testDetachRemovesRelationshipThenCleansOrphans(): void {
        $calls = [];
        $detached = $this->service->detach(
            42,
            12,
            static function (int $huntId, int $riddleId) use (&$calls): bool {
                $calls[] = ['detach', $huntId, $riddleId];
                return true;
            },
            static function () use (&$calls): void {
                $calls[] = ['cleanup'];
            }
        );

        $this->assertTrue($detached);
        $this->assertSame([['detach', 12, 42], ['cleanup']], $calls);
    }

    public function testDetachSkipsCallbacksWithoutHunt(): void {
        $called = false;

        $this->assertFalse($this->service->detach(
            42,
            null,
            static function () use (&$called): bool {
                $called = true;
                return true;
            },
            static function () use (&$called): void {
                $called = true;
            }
        ));
        $this->assertFalse($called);
    }
}
