<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\UserAttemptStatisticsRepository;
use ChassesAuTresor\Core\Progress\UserAttemptStatisticsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptStatisticsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptStatisticsService.php';

class UserAttemptStatisticsRepositoryStub extends UserAttemptStatisticsRepository
{
    public int $userId = 0;
    public array $query = [];

    public function __construct()
    {
    }

    public function summarize(int $userId): array
    {
        $this->userId = $userId;

        return ['pending' => 2, 'total' => 7, 'success' => 4];
    }

    public function countForUser(int $userId, string $search = ''): int
    {
        $this->query = [$userId, $search];

        return 21;
    }

    public function findForUser(int $userId, string $search, int $limit, int $offset): array
    {
        $this->query = [$userId, $search, $limit, $offset];

        return [(object) ['id' => 7]];
    }
}

class UserAttemptStatisticsServiceTest extends TestCase
{
    public function testSummaryIsDelegatedForAValidUser(): void
    {
        $repository = new UserAttemptStatisticsRepositoryStub();
        $service = new UserAttemptStatisticsService($repository);

        $this->assertSame(['pending' => 2, 'total' => 7, 'success' => 4], $service->summarize(12));
        $this->assertSame(12, $repository->userId);
    }

    public function testInvalidUserReturnsAnEmptySummary(): void
    {
        $repository = new UserAttemptStatisticsRepositoryStub();
        $service = new UserAttemptStatisticsService($repository);

        $this->assertSame(['pending' => 0, 'total' => 0, 'success' => 0], $service->summarize(0));
        $this->assertSame(0, $repository->userId);
    }

    public function testPaginationIsNormalizedAndDelegated(): void
    {
        $repository = new UserAttemptStatisticsRepositoryStub();
        $service = new UserAttemptStatisticsService($repository);

        $this->assertEquals([
            'page' => 3,
            'pages' => 3,
            'total' => 21,
            'items' => [(object) ['id' => 7]],
        ], $service->paginate(12, 5, 10, '  trésor  '));
        $this->assertSame([12, 'trésor', 10, 20], $repository->query);
        $this->assertSame(
            ['page' => 1, 'pages' => 0, 'total' => 0, 'items' => []],
            $service->paginate(0, 2, 10)
        );
    }
}
