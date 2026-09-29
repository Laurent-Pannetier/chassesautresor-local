<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptRepository;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptService.php';

class RiddleAttemptRepositoryStub extends RiddleAttemptRepository
{
    public array $attempt = [];
    public string $uid = '';
    public array $countArguments = [];

    public function __construct()
    {
    }

    public function insert(array $attempt): bool
    {
        $this->attempt = $attempt;
        return true;
    }

    public function findByUid(string $uid): ?object
    {
        $this->uid = $uid;
        return (object) ['tentative_uid' => $uid];
    }

    public function countForRiddle(int $riddleId): int
    {
        $this->countArguments = ['riddle' => $riddleId];
        return 12;
    }

    public function countPendingForRiddle(int $riddleId): int
    {
        $this->countArguments = ['pending' => $riddleId];
        return 3;
    }

    public function countForUserAndRiddleBetween(
        int $userId,
        int $riddleId,
        string $startAt,
        string $endAt
    ): int {
        $this->countArguments = [$userId, $riddleId, $startAt, $endAt];
        return 2;
    }
}

class RiddleAttemptServiceTest extends TestCase
{
    public function testAttemptCreationIsValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertTrue($service->create('uid-1', 7, 10, 'réponse', 'bon', -2, '127.0.0.1', 'Agent'));
        $this->assertSame(0, $repository->attempt['points_utilises']);
        $this->assertSame('réponse', $repository->attempt['reponse_saisie']);
        $this->assertFalse($service->create('', 7, 10, 'réponse', 'bon', 0, null, null));
    }

    public function testAttemptLookupTrimsUidAndRejectsEmptyValues(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertSame('uid-1', $service->findByUid(' uid-1 ')->tentative_uid);
        $this->assertSame('uid-1', $repository->uid);
        $this->assertNull($service->findByUid('  '));
    }

    public function testRiddleCountersAreValidatedAndDelegated(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);

        $this->assertSame(12, $service->countForRiddle(10));
        $this->assertSame(['riddle' => 10], $repository->countArguments);
        $this->assertSame(3, $service->countPendingForRiddle(10));
        $this->assertSame(['pending' => 10], $repository->countArguments);
        $this->assertSame(0, $service->countForRiddle(0));
        $this->assertSame(0, $service->countPendingForRiddle(-1));
    }

    public function testDailyCounterUsesParisCalendarDay(): void
    {
        $repository = new RiddleAttemptRepositoryStub();
        $service = new RiddleAttemptService($repository);
        $now = new DateTimeImmutable('2026-09-29 22:30:00', new DateTimeZone('UTC'));

        $this->assertSame(2, $service->countTodayForUser(7, 10, $now));
        $this->assertSame(
            [7, 10, '2026-09-30 00:00:00', '2026-09-30 23:59:59'],
            $repository->countArguments
        );
        $this->assertSame(0, $service->countTodayForUser(0, 10, $now));
        $this->assertSame(0, $service->countTodayForUser(7, 0, $now));
    }
}
