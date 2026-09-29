<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntAccessService.php';

class HuntAccessServiceTest extends TestCase
{
    private HuntAccessService $service;

    protected function setUp(): void
    {
        $this->service = new HuntAccessService();
    }

    public function testPublishedValidatedHuntIsPublic(): void
    {
        $this->assertTrue($this->service->canView('publish', 'valide', false, false));
    }

    public function testAdministratorCanViewPendingHunt(): void
    {
        $this->assertTrue($this->service->canView('pending', 'creation', true, false));
    }

    public function testAssociatedOrganizerCanViewPendingHunt(): void
    {
        $this->assertTrue($this->service->canView('pending', 'correction', false, true));
    }

    /**
     * @dataProvider hiddenHuntProvider
     */
    public function testInvalidContextHidesHunt(array $context): void
    {
        $this->assertFalse($this->service->canView(...$context));
    }

    /** @return array<string, array{array{string, string, bool, bool}}> */
    public function hiddenHuntProvider(): array
    {
        return [
            'published but not validated' => [['publish', 'creation', true, true]],
            'pending unrelated user' => [['pending', 'creation', false, false]],
            'draft administrator' => [['draft', 'valide', true, true]],
        ];
    }

    public function testAdministratorCanViewStatistics(): void
    {
        $this->assertTrue($this->service->canViewStatistics(true, true, false));
    }

    public function testAssociatedOrganizerCanViewStatistics(): void
    {
        $this->assertTrue($this->service->canViewStatistics(true, false, true));
    }

    public function testInvalidOrUnrelatedHuntStatisticsAreDenied(): void
    {
        $this->assertFalse($this->service->canViewStatistics(false, true, true));
        $this->assertFalse($this->service->canViewStatistics(true, false, false));
    }
}
