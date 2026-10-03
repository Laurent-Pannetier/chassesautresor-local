<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatisticsRepository;
use ChassesAuTresor\Core\Progress\RiddleStatisticsRepository;
use ChassesAuTresor\Core\Progress\UserAttemptStatisticsRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptStatisticsRepository.php';

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

final class IntermediateStepStatisticsDatabaseStub
{
    public string $prefix = 'wp_';
    public string $users = 'wp_users';
    public string $posts = 'wp_posts';
    public string $postmeta = 'wp_postmeta';
    public string $lastQuery = '';

    public function prepare(string $query, ...$arguments): string
    {
        return $query;
    }

    public function get_var(string $query): int
    {
        $this->lastQuery = $query;
        return 0;
    }

    public function get_row(string $query, string $output): array
    {
        $this->lastQuery = $query;
        return [];
    }

    public function get_results(string $query, string $output = ''): array
    {
        $this->lastQuery = $query;
        return [];
    }
}

final class IntermediateStepStatisticsIsolationTest extends TestCase
{
    public function testRiddleAggregatesIgnoreIntermediateStepInteractions(): void
    {
        $database = new IntermediateStepStatisticsDatabaseStub();
        $repository = new RiddleStatisticsRepository($database);

        $repository->aggregateAttempts(42, 'COUNT(*)');

        self::assertStringContainsString('enigme_id = %d AND etape_id IS NULL', $database->lastQuery);
    }

    public function testRiddleSolverListOnlyUsesFinalAttempts(): void
    {
        $database = new IntermediateStepStatisticsDatabaseStub();
        $repository = new RiddleStatisticsRepository($database);

        $repository->listSolvers(42);

        self::assertSame(2, substr_count($database->lastQuery, 'etape_id IS NULL'));
    }

    public function testHuntAndAccountStatisticsIgnoreIntermediateStepInteractions(): void
    {
        $database = new IntermediateStepStatisticsDatabaseStub();
        $huntRepository = new HuntStatisticsRepository($database);
        $accountRepository = new UserAttemptStatisticsRepository($database);

        $huntRepository->countAttempts([42]);
        self::assertStringContainsString('etape_id IS NULL', $database->lastQuery);

        $accountRepository->summarize(7);
        self::assertStringContainsString('etape_id IS NULL', $database->lastQuery);

        $accountRepository->countForUser(7);
        self::assertStringContainsString('t.etape_id IS NULL', $database->lastQuery);
    }
}
