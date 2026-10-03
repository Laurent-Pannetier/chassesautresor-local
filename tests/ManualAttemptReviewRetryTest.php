<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\ManualAttemptReviewService;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleRetryConfiguration;
use ChassesAuTresor\Core\Progress\RiddleRetryPolicyService;
use ChassesAuTresor\Core\Progress\RiddleRetryRepository;
use PHPUnit\Framework\TestCase;

final class ManualReviewAttemptStub extends RiddleAttemptService
{
    public function __construct()
    {
    }

    public function processManualAttempt(
        string $uid,
        string $result,
        int $currentUserId,
        bool $isAdministrator,
        callable $getOrganizerUserIds
    ): ?object {
        return (object) ['user_id' => 7, 'enigme_id' => 10];
    }

    public function buildProcessingPlan(
        int $userId,
        int $riddleId,
        string $result,
        int $configuredCost,
        bool $createAttempt,
        bool $notifyFailure
    ): ?array {
        return ['charge' => 0, 'outcome' => ['user_status' => 'echouee', 'resolved' => false, 'notify' => true]];
    }
}

final class ManualReviewRetryDatabaseStub
{
    public string $prefix = 'wp_';
    public array $queries = [];

    public function prepare(string $query, ...$arguments): string
    {
        return $query;
    }

    public function query(string $query)
    {
        $this->queries[] = $query;

        return str_starts_with($query, 'INSERT INTO') ? false : true;
    }
}

final class ManualAttemptReviewRetryTest extends TestCase
{
    public function testRetryWriteFailureRollsBackManualRefusal(): void
    {
        $database = new ManualReviewRetryDatabaseStub();
        $service = new ManualAttemptReviewService(
            new ManualReviewAttemptStub(),
            $this->createMock(HuntProgressService::class),
            $this->createMock(AccountMessageService::class),
            $database,
            new RiddleRetryPolicyService(
                new RiddleRetryRepository($database),
                new RiddleRetryConfiguration(static fn (): int => 300),
                static fn (): DateTimeImmutable => new DateTimeImmutable('2026-10-03 12:00:00 UTC')
            )
        );

        self::assertFalse($service->process('manual-1', 'faux', 3, true));
        self::assertSame('START TRANSACTION', $database->queries[0]);
        self::assertSame('ROLLBACK', $database->queries[2]);
    }
}
