<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleRelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipService.php';

class RiddleRelationshipServiceTest extends TestCase {
    private RiddleRelationshipService $service;

    protected function setUp(): void {
        $this->service = new RiddleRelationshipService();
    }

    public function testHuntIdIsResolvedFromAcfValueShapes(): void {
        $this->assertSame(12, $this->service->resolveHuntId(12));
        $this->assertSame(12, $this->service->resolveHuntId([(object) ['ID' => 12]]));
        $this->assertSame(12, $this->service->resolveHuntId((object) ['ID' => 12]));
        $this->assertSame(0, $this->service->resolveHuntId([]));
    }

    public function testCurrentRiddleIsExcludedFromSelectablePrerequisites(): void {
        $this->assertSame([10, 30], $this->service->getSelectableRiddleIds([10, 20, 30, 20], 20));
        $this->assertSame([0], $this->service->getSelectableRiddleIds([20], 20));
    }

    public function testOnlyExistingUniqueRiddlesAreRetained(): void {
        $existing = $this->service->filterExistingRiddleIds(
            [10, '20', 20, 30, 0],
            static fn (int $id): bool => in_array($id, [10, 30], true)
        );

        $this->assertSame([10, 30], $existing);
    }
}
