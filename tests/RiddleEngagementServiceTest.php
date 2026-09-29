<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleEngagementRepository;
use ChassesAuTresor\Core\Progress\RiddleEngagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleEngagementRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleEngagementService.php';

class RiddleEngagementRepositoryStub extends RiddleEngagementRepository
{
    public array $arguments = [];
    public bool $engaged = true;
    public bool $inserted = true;
    public array $insertArguments = [];

    public function __construct()
    {
    }

    public function exists(int $userId, int $riddleId): bool
    {
        $this->arguments = [$userId, $riddleId];
        return $this->engaged;
    }

    public function insert(int $userId, int $riddleId, string $engagedAt): bool
    {
        $this->insertArguments = [$userId, $riddleId, $engagedAt];
        return $this->inserted;
    }
}

class RiddleEngagementServiceTest extends TestCase
{
    public function testEngagementLookupIsValidatedAndDelegated(): void
    {
        $repository = new RiddleEngagementRepositoryStub();
        $service = new RiddleEngagementService($repository);

        $this->assertTrue($service->isEngaged(7, 10));
        $this->assertSame([7, 10], $repository->arguments);
        $this->assertFalse($service->isEngaged(0, 10));
        $this->assertFalse($service->isEngaged(7, 0));

        $repository->engaged = false;
        $this->assertFalse($service->isEngaged(7, 10));
    }

    public function testRepositoryQueriesRiddleEngagement(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $arguments = [];
            public $result = '1';
            public $insertResult = 1;
            public array $insertArguments = [];

            public function prepare(string $query, ...$arguments): string
            {
                $this->arguments = $arguments;
                return $query;
            }

            public function get_var(string $query)
            {
                return $this->result;
            }

            public function insert(string $table, array $data, array $format)
            {
                $this->insertArguments = [$table, $data, $format];
                return $this->insertResult;
            }
        };
        $repository = new RiddleEngagementRepository($wpdb);

        $this->assertTrue($repository->exists(7, 10));
        $this->assertSame([7, 10], $wpdb->arguments);

        $wpdb->result = null;
        $this->assertFalse($repository->exists(7, 10));

        $this->assertTrue($repository->insert(7, 10, '2026-09-29 12:00:00'));
        $this->assertSame(
            [
                'wp_engagements',
                ['user_id' => 7, 'enigme_id' => 10, 'date_engagement' => '2026-09-29 12:00:00'],
                ['%d', '%d', '%s'],
            ],
            $wpdb->insertArguments
        );

        $wpdb->insertResult = false;
        $this->assertFalse($repository->insert(7, 10, '2026-09-29 12:00:00'));
    }

    public function testEnsureEngagedPreservesExistingAndReportsCreation(): void
    {
        $repository = new RiddleEngagementRepositoryStub();
        $service = new RiddleEngagementService($repository);

        $this->assertSame(
            ['success' => true, 'created' => false],
            $service->ensureEngaged(7, 10, '2026-09-29 12:00:00')
        );
        $this->assertSame([], $repository->insertArguments);

        $repository->engaged = false;
        $this->assertSame(
            ['success' => true, 'created' => true],
            $service->ensureEngaged(7, 10, '2026-09-29 12:00:00')
        );
        $this->assertSame([7, 10, '2026-09-29 12:00:00'], $repository->insertArguments);

        $repository->inserted = false;
        $this->assertSame(
            ['success' => false, 'created' => false],
            $service->ensureEngaged(7, 10, '2026-09-29 12:00:00')
        );
        $this->assertSame(
            ['success' => false, 'created' => false],
            $service->ensureEngaged(0, 10, '2026-09-29 12:00:00')
        );
    }
}
