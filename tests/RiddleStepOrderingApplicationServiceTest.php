<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepOrderingApplicationService;
use PHPUnit\Framework\TestCase;

final class RiddleStepOrderingApplicationServiceTest extends TestCase {
    public function testAppliesACompleteValidatedOrdering(): void {
        $updates = [];
        $service = new RiddleStepOrderingApplicationService();
        $result = $service->reorder(
            42,
            [12, 11],
            static fn (): array => [11, 12],
            static function (array $post) use (&$updates): int {
                $updates[] = $post;
                return $post['ID'];
            }
        );

        self::assertTrue($result);
        self::assertSame(
            [
                ['ID' => 12, 'menu_order' => 0],
                ['ID' => 11, 'menu_order' => 1],
            ],
            $updates
        );
    }

    public function testRejectsForeignIdsEvenWhenTheRiddleHasNoSteps(): void {
        $service = new RiddleStepOrderingApplicationService();

        self::assertFalse($service->reorder(42, [99], static fn (): array => []));
        self::assertTrue($service->reorder(42, [], static fn (): array => []));
    }
}
