<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionManagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionManagementService.php';

class SolutionManagementServiceTest extends TestCase {
    private SolutionManagementService $service;

    protected function setUp(): void {
        $this->service = new SolutionManagementService();
    }

    public function testRequestedPageIsKeptInsideAvailableRange(): void {
        $this->assertSame(1, $this->service->normalizePage(0, 3));
        $this->assertSame(2, $this->service->normalizePage(2, 3));
        $this->assertSame(3, $this->service->normalizePage(8, 3));
        $this->assertSame(8, $this->service->normalizePage(8, 0));
    }

    public function testHuntStatusReportsAvailableRiddlesAndExistingSolutions(): void {
        $this->assertSame(
            [
                'has_solution_chasse' => 0,
                'has_solution_enigme' => 1,
                'has_enigmes' => 1,
                'has_solutions' => 1,
                'total_solutions' => 2,
            ],
            $this->service->buildHuntStatus(false, true, 3, 1, 2)
        );
    }

    public function testSelectedRiddleDoesNotCountAsHuntSolutionWithoutKnownRiddleTotals(): void {
        $status = $this->service->buildHuntStatus(false, true, 0, 0, -1);

        $this->assertSame(1, $status['has_solution_enigme']);
        $this->assertSame(0, $status['has_solutions']);
        $this->assertSame(0, $status['total_solutions']);
    }
}
