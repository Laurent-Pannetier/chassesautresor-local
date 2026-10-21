<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\ProgressionOverviewRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/ProgressionOverviewRepository.php';

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

final class ProgressionOverviewDatabaseStub
{
    public string $prefix = 'wp_';
    public string $lastQuery = '';
    public array $results = [];
    public $var = 0;
    public ?array $row = null;

    public function prepare(string $query, ...$arguments): string
    {
        $this->lastQuery = $query;

        return $query;
    }

    public function get_var(string $query)
    {
        $this->lastQuery = $query;

        return $this->var;
    }

    public function get_row(string $query, string $output = '')
    {
        $this->lastQuery = $query;

        return $this->row;
    }

    public function get_results(string $query, string $output = ''): array
    {
        $this->lastQuery = $query;

        return $this->results;
    }
}

final class ProgressionOverviewRepositoryTest extends TestCase
{
    public function testPlayerAttemptTotalsIgnoreIntermediateSteps(): void
    {
        $database = new ProgressionOverviewDatabaseStub();
        $database->row = ['total' => 4, 'success' => 1, 'pending' => 2];
        $repository = new ProgressionOverviewRepository($database);

        $summary = $repository->summarizeUserAttempts(7, [40, 0, 40]);

        self::assertSame(['total' => 4, 'success' => 1, 'pending' => 2], $summary);
        self::assertStringContainsString('etape_id IS NULL', $database->lastQuery);
        self::assertStringContainsString('enigme_id IN (%d)', $database->lastQuery);
    }

    public function testUnknownStatusDoesNotQuery(): void
    {
        $database = new ProgressionOverviewDatabaseStub();
        $database->lastQuery = 'untouched';
        $repository = new ProgressionOverviewRepository($database);

        self::assertSame(0, $repository->countPlayersWithStatus(40, ['drop table']));
        self::assertSame('untouched', $database->lastQuery);
    }

    public function testStepAttemptsStayAttachedToTheirStep(): void
    {
        $database = new ProgressionOverviewDatabaseStub();
        $database->results = [
            ['etape_id' => '101', 'attempts' => '8'],
        ];
        $repository = new ProgressionOverviewRepository($database);

        $counts = $repository->countAttemptsByStep([101], '2026-10-01 00:00:00', '2026-10-02 00:00:00', [3]);

        self::assertSame([101 => 8], $counts);
        self::assertStringContainsString('etape_id IN (%d)', $database->lastQuery);
        self::assertStringNotContainsString('etape_id IS NULL', $database->lastQuery);
        self::assertStringContainsString('user_id NOT IN (%d)', $database->lastQuery);
    }

    public function testActivityIsGroupedByRiddle(): void
    {
        $database = new ProgressionOverviewDatabaseStub();
        $database->results = [[
            'enigme_id' => '40',
            'attempts' => '3',
            'last_at' => '2026-10-01 08:00:00',
        ]];
        $repository = new ProgressionOverviewRepository($database);

        $activity = $repository->userRiddleActivity(7, [40]);

        self::assertSame(3, $activity[40]['attempts']);
        self::assertSame('2026-10-01 08:00:00', $activity[40]['last_at']);
        self::assertStringContainsString('etape_id IS NULL', $database->lastQuery);
    }
}
