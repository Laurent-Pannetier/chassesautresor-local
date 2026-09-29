<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatisticsRepository;
use ChassesAuTresor\Core\Progress\HuntStatisticsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsService.php';

class HuntStatisticsRepositoryStub extends HuntStatisticsRepository
{
    public array $arguments = [];

    public function __construct()
    {
    }

    public function countAttempts(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        $this->arguments = [$riddleIds, $startAt, $endAt];

        return 8;
    }

    public function sumCollectedPoints(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        $this->arguments = [$riddleIds, $startAt, $endAt];

        return 21;
    }

    public function countEngagements(int $huntId): int
    {
        $this->arguments = [$huntId];

        return 13;
    }
}

class HuntStatisticsServiceTest extends TestCase
{
    public function testAggregatesAreDelegatedWithDateRange(): void
    {
        $repository = new HuntStatisticsRepositoryStub();
        $service = new HuntStatisticsService($repository);

        $this->assertSame(8, $service->countAttempts([10, 11], '2026-09-01', '2026-09-30'));
        $this->assertSame([[10, 11], '2026-09-01', '2026-09-30'], $repository->arguments);
        $this->assertSame(21, $service->sumCollectedPoints([10, 11]));
    }

    public function testEmptyHuntReturnsZeroWithoutQuery(): void
    {
        $repository = new HuntStatisticsRepositoryStub();
        $service = new HuntStatisticsService($repository);

        $this->assertSame(0, $service->countAttempts([]));
        $this->assertSame(0, $service->sumCollectedPoints([]));
        $this->assertSame([], $repository->arguments);
    }

    public function testEngagementCountIsDelegatedForAValidHunt(): void
    {
        $repository = new HuntStatisticsRepositoryStub();
        $service = new HuntStatisticsService($repository);

        $this->assertSame(13, $service->countEngagements(12));
        $this->assertSame([12], $repository->arguments);
        $this->assertSame(0, $service->countEngagements(0));
    }
}
