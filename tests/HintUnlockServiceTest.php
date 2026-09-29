<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HintUnlockRepository;
use ChassesAuTresor\Core\Progress\HintUnlockService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockService.php';

class HintUnlockRepositoryStub extends HintUnlockRepository
{
    public array $arguments = [];
    public bool $unlocked = true;

    public function __construct()
    {
    }

    public function exists(int $userId, int $hintId): bool
    {
        $this->arguments = [$userId, $hintId];

        return $this->unlocked;
    }
}

class HintUnlockServiceTest extends TestCase
{
    public function testUnlockLookupIsValidatedAndDelegated(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $service = new HintUnlockService($repository);

        $this->assertTrue($service->isUnlocked(7, 10));
        $this->assertSame([7, 10], $repository->arguments);
        $this->assertFalse($service->isUnlocked(0, 10));
        $this->assertFalse($service->isUnlocked(7, 0));

        $repository->unlocked = false;
        $this->assertFalse($service->isUnlocked(7, 10));
    }

    public function testRepositoryQueriesHintUnlockTable(): void
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
        $repository = new HintUnlockRepository($wpdb);

        $this->assertTrue($repository->exists(7, 10));
        $this->assertSame([7, 10], $wpdb->arguments);

        $wpdb->result = null;
        $this->assertFalse($repository->exists(7, 10));
    }
}
