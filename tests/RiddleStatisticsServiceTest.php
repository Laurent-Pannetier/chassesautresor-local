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
    public function countSolvedPlayers(int $id): int
    {
        $this->arguments = [$id];
        return 2;
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
    public function listParticipantHints(int $id, int $userId): array
    {
        $this->arguments = [$id, $userId];
        return [['indice_id' => '9', 'date_deblocage' => '2026-10-24 13:04:00']];
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

    public function testSolvedPlayersCountIsDelegated(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);

        $this->assertSame(2, $service->countSolvedPlayers(10));
        $this->assertSame([10], $repository->arguments);
        $this->assertSame(0, $service->countSolvedPlayers(0));
    }

    public function testResolutionRateUsesEngagedAndSolvedPlayers(): void
    {
        $service = new RiddleStatisticsService(new RiddleStatisticsRepositoryStub());

        $this->assertEqualsWithDelta(66.67, $service->calculateResolutionRate(10), 0.01);
        $this->assertSame(0.0, $service->calculateResolutionRate(0));
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

    public function testParticipantHintsAreDelegated(): void
    {
        $repository = new RiddleStatisticsRepositoryStub();
        $service = new RiddleStatisticsService($repository);

        $this->assertSame(
            [['indice_id' => '9', 'date_deblocage' => '2026-10-24 13:04:00']],
            $service->listParticipantHints(10, 7)
        );
        $this->assertSame([10, 7], $repository->arguments);
        $this->assertSame([], $service->listParticipantHints(0, 7));
    }

    public function testParticipantQueryCountsDistinctHints(): void
    {
        $database = new class {
            public string $prefix = 'wp_';
            public string $users = 'wp_users';
            public string $last = '';
            public function prepare(string $sql, ...$arguments): string
            {
                $this->last = $sql;

                return $sql;
            }
            public function get_results(string $sql, string $output = ''): array
            {
                $this->last = $sql;

                return [];
            }
        };
        $repository = new RiddleStatisticsRepository($database);

        $repository->listParticipants(10, [3], 25, 0, 'date', 'ASC');
        $this->assertStringContainsString('indices_deblocages', $database->last);
        $this->assertStringContainsString('COUNT(DISTINCT indice_id)', $database->last);

        $repository->listParticipantHints(10, 7);
        $this->assertStringContainsString('date_deblocage ASC', $database->last);
        $this->assertStringContainsString('user_id = %d', $database->last);
    }
}
