<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntEngagementRepository;
use ChassesAuTresor\Core\Progress\HuntEngagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntEngagementRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntEngagementService.php';

class HuntEngagementRepositoryStub extends HuntEngagementRepository
{
    public bool $exists = false;
    public array $inserted = [];

    public function __construct()
    {
    }

    public function exists(int $userId, int $huntId): bool
    {
        return $this->exists;
    }

    public function countByHunt(int $huntId): int
    {
        return 4;
    }

    public function insert(int $userId, int $huntId, string $engagedAt): bool
    {
        $this->inserted = [$userId, $huntId, $engagedAt];

        return true;
    }
}

class HuntEngagementServiceTest extends TestCase
{
    public function testEngageRejectsExistingEngagement(): void
    {
        $repository = new HuntEngagementRepositoryStub();
        $repository->exists = true;
        $service = new HuntEngagementService($repository);

        $this->assertFalse($service->engage(7, 12, '2026-09-29 12:00:00'));
        $this->assertSame([], $repository->inserted);
    }

    public function testEngagePersistsNewEngagement(): void
    {
        $repository = new HuntEngagementRepositoryStub();
        $service = new HuntEngagementService($repository);

        $this->assertTrue($service->engage(7, 12, '2026-09-29 12:00:00'));
        $this->assertSame([7, 12, '2026-09-29 12:00:00'], $repository->inserted);
        $this->assertSame(4, $service->countPlayers(12));
    }

    public function testInvalidIdentifiersAreRejected(): void
    {
        $service = new HuntEngagementService(new HuntEngagementRepositoryStub());

        $this->assertFalse($service->isEngaged(0, 12));
        $this->assertFalse($service->engage(7, 0, '2026-09-29 12:00:00'));
        $this->assertSame(0, $service->countPlayers(0));
    }
}
