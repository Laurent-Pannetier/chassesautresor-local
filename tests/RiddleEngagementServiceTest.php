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

    public function __construct()
    {
    }

    public function exists(int $userId, int $riddleId): bool
    {
        $this->arguments = [$userId, $riddleId];
        return $this->engaged;
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

            public function prepare(string $query, ...$arguments): string
            {
                $this->arguments = $arguments;
                return $query;
            }

            public function get_var(string $query)
            {
                return $this->result;
            }
        };
        $repository = new RiddleEngagementRepository($wpdb);

        $this->assertTrue($repository->exists(7, 10));
        $this->assertSame([7, 10], $wpdb->arguments);

        $wpdb->result = null;
        $this->assertFalse($repository->exists(7, 10));
    }
}
