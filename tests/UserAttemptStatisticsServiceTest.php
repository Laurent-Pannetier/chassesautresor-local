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

    public function __construct()
    {
    }

    public function summarize(int $userId): array
    {
        $this->userId = $userId;

        return ['pending' => 2, 'total' => 7, 'success' => 4];
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
}
