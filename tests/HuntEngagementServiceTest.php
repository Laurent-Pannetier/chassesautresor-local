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
    public array $participantRange = [];

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

    public function countParticipants(int $huntId, ?string $startAt = null, ?string $endAt = null): int
    {
        $this->participantRange = [$huntId, $startAt, $endAt];

        return 3;
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

    public function testParticipantCountSupportsAnOptionalDateRange(): void
    {
        $repository = new HuntEngagementRepositoryStub();
        $service = new HuntEngagementService($repository);

        $this->assertSame(
            3,
            $service->countParticipants(12, '2026-09-01 00:00:00', '2026-09-30 23:59:59')
        );
        $this->assertSame(
            [12, '2026-09-01 00:00:00', '2026-09-30 23:59:59'],
            $repository->participantRange
        );
        $this->assertSame(0, $service->countParticipants(0));
    }
}
