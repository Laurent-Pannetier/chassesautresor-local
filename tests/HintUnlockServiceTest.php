<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Hints\HintUnlockRepository;
use ChassesAuTresor\Core\Hints\HintUnlockService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Hints/HintUnlockRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Hints/HintUnlockService.php';

class HintUnlockRepositoryStub extends HintUnlockRepository
{
    public array $arguments = [];
    public bool $unlocked = true;
    public bool $inserted = true;
    public bool $engagementInserted = true;

    public function __construct()
    {
    }

    public function exists(int $userId, int $hintId): bool
    {
        $this->arguments = [$userId, $hintId];
        return $this->unlocked;
    }

    public function insert(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        int $pointsSpent,
        string $unlockedAt
    ): bool {
        $this->arguments = [$userId, $hintId, $huntId, $riddleId, $pointsSpent, $unlockedAt];
        return $this->inserted;
    }

    public function insertEngagement(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        string $engagedAt
    ): bool {
        $this->arguments = [$userId, $hintId, $huntId, $riddleId, $engagedAt];
        return $this->engagementInserted;
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

    public function testRepositoryQueriesHintUnlock(): void
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

    public function testUnlockRecordingIsValidatedIdempotentAndDelegated(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $service = new HintUnlockService($repository);

        $this->assertTrue($service->recordUnlock(7, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertSame([7, 10], $repository->arguments);

        $repository->unlocked = false;
        $this->assertTrue($service->recordUnlock(7, 10, 0, 0, 5, '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, null, null, '2026-09-29 12:00:00'], $repository->arguments);

        $this->assertFalse($service->recordUnlock(0, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertFalse($service->recordUnlock(7, 10, 20, 30, -1, '2026-09-29 12:00:00'));
        $this->assertFalse($service->recordUnlock(7, 10, 20, 30, 5, ''));
    }

    public function testUnlockRecordingStopsWhenPersistenceFails(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $repository->unlocked = false;
        $repository->inserted = false;
        $service = new HintUnlockService($repository);

        $this->assertFalse($service->recordUnlock(7, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 20, 30, 5, '2026-09-29 12:00:00'], $repository->arguments);
    }

    public function testRepositoryInsertsUnlockAndEngagement(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $inserts = [];

            public function insert(string $table, array $data, array $formats): int
            {
                $this->inserts[] = [$table, $data, $formats];
                return 1;
            }
        };
        $repository = new HintUnlockRepository($wpdb);

        $this->assertTrue($repository->insert(7, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertTrue($repository->insertEngagement(7, 10, 20, 30, '2026-09-29 12:00:00'));
        $this->assertSame('wp_indices_deblocages', $wpdb->inserts[0][0]);
        $this->assertSame('wp_engagements', $wpdb->inserts[1][0]);
        $this->assertSame(10, $wpdb->inserts[1][1]['indice_id']);
    }
}
