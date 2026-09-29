<?php
declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatisticsRepository;
use ChassesAuTresor\Core\Progress\RiddleStatisticsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsService.php';

class RiddleStatisticsRepositoryStub extends RiddleStatisticsRepository
{
    public array $arguments = [];
    public function __construct() {}
    public function aggregateAttempts(int $id, string $expression, ?string $result = null, ?string $start = null, ?string $end = null): int
    {
        $this->arguments = [$id, $expression, $result, $start, $end];
        return 5;
    }
    public function countEngagedPlayers(
        int $id,
        ?string $start = null,
        ?string $end = null,
        array $excludedUserIds = []
    ): int {
        $this->arguments = [$id, $start, $end, $excludedUserIds];
        return 3;
    }
}

class RiddleStatisticsServiceTest extends TestCase
{
    public function testAttemptAggregatesAreDelegated(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);
        $this->assertSame(5, $service->countAttempts(10));
        $this->assertSame([10, 'COUNT(*)', null, null, null], $repository->arguments);
        $this->assertSame(5, $service->sumSpentPoints(10));
        $this->assertSame(5, $service->countCorrectSolutions(10));
        $this->assertSame(0, $service->countAttempts(0));
    }

    public function testEngagedPlayersExcludeInternalAccounts(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);
        $this->assertSame(3, $service->countEngagedPlayers(10, null, null, [1, 2]));
        $this->assertSame([10, null, null, [1, 2]], $repository->arguments);
        $this->assertSame(0, $service->countEngagedPlayers(0));
    }
}
