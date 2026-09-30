<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleOrderingService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleManagementService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleOrderingService.php';

class RiddleOrderingServiceTest extends TestCase {
    public function testOnlyValidatedRiddlesArePersistedInSubmittedOrder(): void {
        $updates = [];

        $count = (new RiddleOrderingService())->apply(
            [30, 99, 10, 30],
            [10, 20, 30],
            static function (int $riddleId, int $menuOrder) use (&$updates): void {
                $updates[] = ['ID' => $riddleId, 'menu_order' => $menuOrder];
            }
        );

        $this->assertSame(2, $count);
        $this->assertSame(
            [
                ['ID' => 30, 'menu_order' => 0],
                ['ID' => 10, 'menu_order' => 1],
            ],
            $updates
        );
    }

    public function testEmptyValidatedOrderDoesNotPersistAnything(): void {
        $called = false;

        $count = (new RiddleOrderingService())->apply(
            [99],
            [10, 20],
            static function () use (&$called): void {
                $called = true;
            }
        );

        $this->assertSame(0, $count);
        $this->assertFalse($called);
    }
}
