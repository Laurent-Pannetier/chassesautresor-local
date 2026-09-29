<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusService.php';

class HuntStatusServiceTest extends TestCase
{
    private const NOW = 2_000;

    private HuntStatusService $service;

    protected function setUp(): void
    {
        $this->service = new HuntStatusService();
    }

    public function testUnvalidatedHuntAlwaysRemainsInRevision(): void
    {
        $this->assertSame('revision', $this->calculate('creation', 1_000, 1_500, 1_200, 10));
    }

    public function testDiscoveredHuntIsFinished(): void
    {
        $this->assertSame('termine', $this->calculate('valide', 1_000, null, 1_500));
    }

    public function testExpiredHuntIsFinished(): void
    {
        $this->assertSame('termine', $this->calculate('valide', 1_000, 1_999));
    }

    public function testUnlimitedHuntIgnoresItsEndDate(): void
    {
        $this->assertSame('en_cours', $this->calculate('valide', 1_000, 1_500, null, 0, true));
    }

    public function testStartedFreeHuntIsInProgress(): void
    {
        $this->assertSame('en_cours', $this->calculate('valide', self::NOW));
    }

    public function testStartedPaidHuntIsPaid(): void
    {
        $this->assertSame('payante', $this->calculate('valide', 1_000, null, null, 50));
    }

    public function testFutureHuntIsUpcoming(): void
    {
        $this->assertSame('a_venir', $this->calculate('valide', 2_001));
    }

    public function testStatusIsPreservedWhenValidatedHuntHasNoStartDate(): void
    {
        $this->assertSame('a_venir', $this->calculate('valide', null, null, null, 0, false, 'a_venir'));
    }

    private function calculate(
        string $validationStatus,
        ?int $startTimestamp,
        ?int $endTimestamp = null,
        ?int $discoveryTimestamp = null,
        int $pointCost = 0,
        bool $isUnlimited = false,
        string $currentStatus = 'revision'
    ): string {
        return $this->service->calculate(
            $validationStatus,
            $startTimestamp,
            $endTimestamp,
            $discoveryTimestamp,
            $pointCost,
            $isUnlimited,
            self::NOW,
            $currentStatus
        );
    }
}
