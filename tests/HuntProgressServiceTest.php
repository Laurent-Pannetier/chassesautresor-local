<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressRepository;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntProgressService.php';

class HuntProgressRepositoryStub extends HuntProgressRepository
{
    public array $completedRiddles = [];
    public array $statusArguments = [];
    public array $persistedStatus = [];
    public ?string $status = 'resolue';

    public function __construct()
    {
    }

    public function countSolved(int $userId, array $riddleIds): int
    {
        return 2;
    }

    public function findStatus(int $userId, int $riddleId): ?string
    {
        $this->statusArguments = [$userId, $riddleId];

        return $this->status;
    }

    public function persistStatus(
        int $userId,
        int $riddleId,
        string $status,
        string $updatedAt,
        bool $statusExists
    ): void {
        $this->persistedStatus = [$userId, $riddleId, $status, $updatedAt, $statusExists];
    }

    public function countEngaged(int $userId, array $riddleIds): int
    {
        return 1;
    }

    public function countValidatable(array $riddleIds): int
    {
        return 2;
    }

    public function findCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return [(object) ['user_id' => 7, 'first_finish' => '2026-09-29 10:00:00']];
    }

    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        $this->completedRiddles = [$riddleIds, $completedAt];

        return [10 => [7, 8]];
    }
}

class HuntProgressServiceTest extends TestCase
{
    public function testRepositoryCompletesRiddlesAndReturnsAffectedUsers(): void
    {
        $wpdb = new class {
            public string $prefix = 'wp_';
            public array $updates = [];

            public function update($table, $data, $where, $format, $whereFormat): void
            {
                $this->updates[] = compact('table', 'data', 'where', 'format', 'whereFormat');
            }

            public function prepare($query, ...$args): string
            {
                return $query . ':' . implode(',', $args);
            }

            public function get_col($query): array
            {
                return ['7', '8'];
            }
        };
        $repository = new HuntProgressRepository($wpdb);

        $this->assertSame(
            [10 => [7, 8]],
            $repository->completeRiddles([10], '2026-09-29 11:00:00')
        );
        $this->assertSame('wp_enigme_statuts_utilisateur', $wpdb->updates[0]['table']);
        $this->assertSame(['enigme_id' => 10], $wpdb->updates[0]['where']);
        $this->assertSame('terminee', $wpdb->updates[0]['data']['statut']);
    }

    public function testCalculateCombinesSolvedAndEngagedRiddles(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            ['completed' => 3, 'total' => 3, 'is_complete' => true],
            $service->calculate(7, [10, 11], [12])
        );
    }

    public function testCalculateRejectsInvalidUser(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(
            ['completed' => 0, 'total' => 0, 'is_complete' => false],
            $service->calculate(0, [10], [])
        );
    }

    public function testRiddleStatusIsDelegatedForValidIdentifiers(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $service = new HuntProgressService($repository);

        $this->assertSame('resolue', $service->getRiddleStatus(7, 10));
        $this->assertSame([7, 10], $repository->statusArguments);
        $this->assertNull($service->getRiddleStatus(0, 10));
        $this->assertNull($service->getRiddleStatus(7, 0));
    }

    public function testRiddleStatusCanOnlyAdvanceUnlessForced(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->status = 'en_cours';
        $service = new HuntProgressService($repository);

        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'resolue', '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 'resolue', '2026-09-29 12:00:00', true], $repository->persistedStatus);
        $this->assertFalse($service->advanceRiddleStatus(7, 10, 'soumis', '2026-09-29 12:00:00'));
        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'soumis', '2026-09-29 12:00:00', true));
    }

    public function testNewRiddleStatusIsInsertedAndInvalidInputIsRejected(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $repository->status = null;
        $service = new HuntProgressService($repository);

        $this->assertTrue($service->advanceRiddleStatus(7, 10, 'en_cours', '2026-09-29 12:00:00'));
        $this->assertSame([7, 10, 'en_cours', '2026-09-29 12:00:00', false], $repository->persistedStatus);
        $this->assertFalse($service->advanceRiddleStatus(0, 10, 'en_cours', '2026-09-29 12:00:00'));
        $this->assertFalse($service->advanceRiddleStatus(7, 10, 'inconnu', '2026-09-29 12:00:00'));
    }

    public function testCompletedUsersComeFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());
        $users = $service->getCompletedUsers([10, 11], [12]);

        $this->assertSame(7, $users[0]->user_id);
        $this->assertSame('2026-09-29 10:00:00', $users[0]->first_finish);
    }

    public function testSolvedRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(2, $service->countSolvedRiddles(7, [10, 11]));
        $this->assertSame(0, $service->countSolvedRiddles(0, [10, 11]));
    }

    public function testEngagedRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(1, $service->countEngagedRiddles(7, [10, 11]));
        $this->assertSame(0, $service->countEngagedRiddles(0, [10, 11]));
    }

    public function testValidatableRiddleCountComesFromProgressRepository(): void
    {
        $service = new HuntProgressService(new HuntProgressRepositoryStub());

        $this->assertSame(2, $service->countValidatableRiddles([10, 11, 12]));
    }

    public function testCompleteRiddlesDelegatesStorageToRepository(): void
    {
        $repository = new HuntProgressRepositoryStub();
        $service = new HuntProgressService($repository);
        $usersByRiddle = $service->completeRiddles([10], '2026-09-29 11:00:00');

        $this->assertSame([10 => [7, 8]], $usersByRiddle);
        $this->assertSame([[10], '2026-09-29 11:00:00'], $repository->completedRiddles);
    }
}
