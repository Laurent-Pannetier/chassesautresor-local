<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HintUnlockRepository;
use ChassesAuTresor\Core\Progress\HintUnlockService;
use ChassesAuTresor\Core\Points\PointsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockService.php';

class HintUnlockRepositoryStub extends HintUnlockRepository
{
    public array $arguments = [];
    public bool $unlocked = true;
    public bool $unlockInserted = true;
    public bool $engagementInserted = true;
    public array $unlockArguments = [];
    public array $engagementArguments = [];
    public array $transactionEvents = [];
    public array $unlockedHintIds = [];

    public function __construct()
    {
    }

    public function beginTransaction(): void
    {
        $this->transactionEvents[] = 'begin';
    }

    public function commit(): void
    {
        $this->transactionEvents[] = 'commit';
    }

    public function rollBack(): void
    {
        $this->transactionEvents[] = 'rollback';
    }

    public function exists(int $userId, int $hintId): bool
    {
        $this->arguments = [$userId, $hintId];

        return $this->unlocked;
    }

    public function findUnlockedHintIds(int $userId, array $hintIds): array {
        $this->arguments = [$userId, $hintIds];

        return $this->unlockedHintIds;
    }

    public function insertUnlock(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        int $pointsSpent,
        string $unlockedAt
    ): bool {
        $this->unlockArguments = [$userId, $hintId, $huntId, $riddleId, $pointsSpent, $unlockedAt];

        return $this->unlockInserted;
    }

    public function insertEngagement(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        string $engagedAt
    ): bool {
        $this->engagementArguments = [$userId, $hintId, $huntId, $riddleId, $engagedAt];

        return $this->engagementInserted;
    }
}

class HintUnlockPointsServiceStub extends PointsService
{
    public array $deductArguments = [];

    public function __construct()
    {
    }

    public function deduct(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        $this->deductArguments = [$userId, $amount, $reason, $originType, $originId];
    }
}

class HintUnlockServiceTest extends TestCase
{
    public function testUnlockLookupIsValidatedAndDelegated(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $service = new HintUnlockService($repository, new HintUnlockPointsServiceStub());

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

    public function testUnlocksAreLoadedInOneBatch(): void {
        $repository = new HintUnlockRepositoryStub();
        $repository->unlockedHintIds = [10, 12];
        $service = new HintUnlockService($repository, new HintUnlockPointsServiceStub());

        $this->assertSame([10, 12], $service->unlockedHintIds(7, [10, 11, 12]));
        $this->assertSame([7, [10, 11, 12]], $repository->arguments);
    }

    public function testRepositoryQueriesUnlocksInOneBatch(): void {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $arguments = [];
            public int $queryCount = 0;

            public function prepare(string $query, ...$arguments): string
            {
                $this->arguments = $arguments;

                return $query;
            }

            public function get_col(string $query): array
            {
                $this->queryCount++;

                return ['10', '12'];
            }
        };
        $repository = new HintUnlockRepository($wpdb);

        $this->assertSame([10, 12], $repository->findUnlockedHintIds(7, [10, 11, 12, 12, 0, -4]));
        $this->assertSame([7, 10, 11, 12], $wpdb->arguments);
        $this->assertSame(1, $wpdb->queryCount);
        $this->assertSame([], $repository->findUnlockedHintIds(0, [10]));
        $this->assertSame([], $repository->findUnlockedHintIds(7, []));
        $this->assertSame(1, $wpdb->queryCount);
    }

    public function testRecordUnlockValidatesAndPersistsBothRecords(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $service = new HintUnlockService($repository, new HintUnlockPointsServiceStub());

        $this->assertTrue($service->recordUnlock(7, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 20, 30, 5, '2026-09-29 12:00:00'], $repository->unlockArguments);
        $this->assertSame([7, 10, 20, 30, '2026-09-29 12:00:00'], $repository->engagementArguments);
        $this->assertSame(['begin', 'commit'], $repository->transactionEvents);

        $this->assertFalse($service->recordUnlock(0, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertFalse($service->recordUnlock(7, 10, 20, 30, -1, '2026-09-29 12:00:00'));

        $repository->unlockInserted = false;
        $repository->engagementArguments = [];
        $this->assertFalse($service->recordUnlock(7, 10, 0, 0, 5, '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, null, null, 5, '2026-09-29 12:00:00'], $repository->unlockArguments);
        $this->assertSame([], $repository->engagementArguments);
        $this->assertSame('rollback', end($repository->transactionEvents));
    }

    public function testRecordUnlockRollsBackWithoutChargingWhenEngagementFails(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $repository->engagementInserted = false;
        $pointsService = new HintUnlockPointsServiceStub();
        $service = new HintUnlockService($repository, $pointsService);

        $this->assertFalse($service->recordUnlock(7, 10, 20, 30, 5, '2026-09-29 12:00:00'));
        $this->assertSame(['begin', 'rollback'], $repository->transactionEvents);
        $this->assertSame([], $pointsService->deductArguments);
    }

    public function testRecordUnlockDelegatesHintPointDeduction(): void
    {
        $repository = new HintUnlockRepositoryStub();
        $pointsService = new HintUnlockPointsServiceStub();
        $service = new HintUnlockService($repository, $pointsService);

        $this->assertTrue(
            $service->recordUnlock(7, 10, 20, 30, 5, '2026-09-29 12:00:00', 'Hint unlock')
        );
        $this->assertSame([7, 5, 'Hint unlock', 'indice', 10], $pointsService->deductArguments);

        $pointsService->deductArguments = [];
        $this->assertTrue($service->recordUnlock(7, 10, 20, 30, 0, '2026-09-29 12:00:00'));
        $this->assertSame([], $pointsService->deductArguments);
    }

    public function testRepositoryInsertsUnlockAndEngagement(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $insertCalls = [];

            public function insert(string $table, array $data, array $format)
            {
                $this->insertCalls[] = [$table, $data, $format];

                return 1;
            }
        };
        $repository = new HintUnlockRepository($wpdb);

        $this->assertTrue($repository->insertUnlock(7, 10, 20, null, 5, '2026-09-29 12:00:00'));
        $this->assertTrue($repository->insertEngagement(7, 10, 20, null, '2026-09-29 12:00:00'));
        $this->assertSame('wp_indices_deblocages', $wpdb->insertCalls[0][0]);
        $this->assertSame('wp_engagements', $wpdb->insertCalls[1][0]);
        $this->assertSame(10, $wpdb->insertCalls[0][1]['indice_id']);
        $this->assertSame(10, $wpdb->insertCalls[1][1]['indice_id']);
    }
}
