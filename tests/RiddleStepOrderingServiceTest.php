<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepOrderingService;
use PHPUnit\Framework\TestCase;

final class RiddleStepOrderingServiceTest extends TestCase {
    public function testBuildsMenuOrderUpdatesForACompletePermutation(): void {
        $service = new RiddleStepOrderingService();

        self::assertSame(
            [
                ['id' => 13, 'menu_order' => 0],
                ['id' => 11, 'menu_order' => 1],
                ['id' => 12, 'menu_order' => 2],
            ],
            $service->buildUpdatePlan([11, 12, 13], ['13', 11, 12])
        );
    }

    /** @dataProvider invalidOrderProvider */
    public function testRejectsIncompleteDuplicatedOrForeignIds(array $submittedIds): void {
        self::assertSame(
            [],
            (new RiddleStepOrderingService())->buildUpdatePlan([11, 12, 13], $submittedIds)
        );
    }

    public function invalidOrderProvider(): array {
        return [
            'missing ID' => [[11, 12]],
            'duplicate ID' => [[11, 12, 12]],
            'foreign ID' => [[11, 12, 99]],
            'invalid ID' => [[11, 12, 0]],
        ];
    }
}
