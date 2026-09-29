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
    public function listSolvers(int $id, array $excludedUserIds = []): array
    {
        $this->arguments = [$id, $excludedUserIds];
        return [[
            'user_id' => '7',
            'username' => 'alice',
            'resolution_date' => '2026-09-29 10:00:00',
            'tentatives' => '2',
        ]];
    }
    public function listParticipants(int $id, array $excluded, int $limit, int $offset, string $orderBy, string $order): array
    {
        $this->arguments = [$id, $excluded, $limit, $offset, $orderBy, $order];
        return [['user_id' => 7]];
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

    public function testSolversAreNormalizedAndInternalAccountsExcluded(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);
        $this->assertSame([[
            'user_id' => 7,
            'username' => 'alice',
            'date' => '2026-09-29 10:00:00',
            'tentatives' => 2,
        ]], $service->listSolvers(10, [1, 2]));
        $this->assertSame([10, [1, 2]], $repository->arguments);
        $this->assertSame([], $service->listSolvers(0));
    }

    public function testParticipantListIsDelegatedWithSafeParameters(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);
        $this->assertSame([['user_id' => 7]], $service->listParticipants(10, [1, 2], 25, 0, 'date', 'ASC'));
        $this->assertSame([10, [1, 2], 25, 0, 'date', 'ASC'], $repository->arguments);
        $this->assertSame([], $service->listParticipants(0, [], 25, 0, 'date', 'ASC'));
    }
}
