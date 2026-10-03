<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptRepository;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleStepProgressRepository;
use ChassesAuTresor\Core\Progress\RiddleStepProgressService;
use ChassesAuTresor\Core\Progress\RiddleStepSubmissionService;
use PHPUnit\Framework\TestCase;

final class RiddleStepSubmissionDatabaseStub {
    public array $queries = [];

    public function query(string $query): bool {
        $this->queries[] = $query;
        return true;
    }
}

final class RiddleStepSubmissionAttemptRepositoryStub extends RiddleAttemptRepository {
    public array $attempts = [];
    public bool $allowInsert = true;
    public bool $throwOnInsert = false;

    public function __construct() {
    }

    public function insert(array $attempt): bool {
        if ($this->throwOnInsert) {
            throw new RuntimeException('Database failure');
        }
        if (!$this->allowInsert) {
            return false;
        }

        $this->attempts[] = $attempt;
        return true;
    }
}

final class RiddleStepSubmissionProgressRepositoryStub extends RiddleStepProgressRepository {
    /** @var array<string,int[]> */
    public array $completedByPlayer = [];
    public bool $allowWrite = true;

    public function __construct() {
    }

    public function findCompletedStepIds(int $userId, int $riddleId): array {
        return $this->completedByPlayer[$this->key($userId, $riddleId)] ?? [];
    }

    public function markCompleted(
        int $userId,
        int $riddleId,
        int $stepId,
        string $completedAt,
        ?string $attemptUid
    ): bool {
        if (!$this->allowWrite) {
            return false;
        }

        $key = $this->key($userId, $riddleId);
        $this->completedByPlayer[$key][] = $stepId;
        return true;
    }

    private function key(int $userId, int $riddleId): string {
        return $userId . ':' . $riddleId;
    }
}

final class RiddleStepSubmissionServiceTest extends TestCase {
    private RiddleStepSubmissionDatabaseStub $database;
    private RiddleStepSubmissionAttemptRepositoryStub $attemptRepository;
    private RiddleStepSubmissionProgressRepositoryStub $progressRepository;
    private RiddleStepSubmissionService $service;

    protected function setUp(): void {
        $this->database = new RiddleStepSubmissionDatabaseStub();
        $this->attemptRepository = new RiddleStepSubmissionAttemptRepositoryStub();
        $this->progressRepository = new RiddleStepSubmissionProgressRepositoryStub();
        $this->service = new RiddleStepSubmissionService(
            $this->database,
            new RiddleAttemptService($this->attemptRepository),
            new RiddleStepProgressService($this->progressRepository)
        );
    }

    public function testSuccessfulAnswerStoresAttemptAndRevealsNextStep(): void {
        $submission = $this->submit(4, 11, 'N,E', 'bon', 'uid-1');

        self::assertSame('success', $submission['status']);
        self::assertSame(12, $submission['state']['current_step_id']);
        self::assertFalse($submission['state']['final_answer_unlocked']);
        self::assertSame(11, $this->attemptRepository->attempts[0]['etape_id']);
        self::assertSame('N,E', $this->attemptRepository->attempts[0]['reponse_saisie']);
        self::assertSame(['START TRANSACTION', 'COMMIT'], $this->database->queries);
    }

    public function testSuccessfulLastStepUnlocksFinalAnswer(): void {
        $this->progressRepository->completedByPlayer['4:9'] = [11];

        $submission = $this->submit(4, 12, 'click', 'bon', 'uid-2');

        self::assertSame('success', $submission['status']);
        self::assertNull($submission['state']['current_step_id']);
        self::assertTrue($submission['state']['final_answer_unlocked']);
    }

    /** @dataProvider nonSuccessfulResultProvider */
    public function testFailureAndVariantDoNotAdvanceProgress(string $result): void {
        $submission = $this->submit(4, 11, 'essai', $result, 'uid-3');

        self::assertSame('success', $submission['status']);
        self::assertSame(11, $submission['state']['current_step_id']);
        self::assertSame([], $this->progressRepository->completedByPlayer);
        self::assertSame($result, $this->attemptRepository->attempts[0]['resultat']);
        self::assertSame(['START TRANSACTION', 'COMMIT'], $this->database->queries);
    }

    public function nonSuccessfulResultProvider(): array {
        return [['faux'], ['variante']];
    }

    public function testCompletedOrFutureStepIsRejectedBeforeTransaction(): void {
        $submission = $this->submit(4, 12, 'click', 'bon', 'uid-4');

        self::assertSame('unavailable', $submission['status']);
        self::assertSame([], $this->attemptRepository->attempts);
        self::assertSame([], $this->database->queries);
    }

    public function testSecondSubmissionOfCompletedStepIsRejected(): void {
        self::assertSame('success', $this->submit(4, 11, 'click', 'bon', 'uid-5')['status']);
        self::assertSame('unavailable', $this->submit(4, 11, 'click', 'bon', 'uid-6')['status']);

        self::assertCount(1, $this->attemptRepository->attempts);
        self::assertSame(['START TRANSACTION', 'COMMIT'], $this->database->queries);
    }

    public function testProgressIsSeparatedBetweenPlayers(): void {
        self::assertSame('success', $this->submit(4, 11, 'click', 'bon', 'uid-7')['status']);
        self::assertSame('success', $this->submit(5, 11, 'click', 'bon', 'uid-8')['status']);

        self::assertSame([11], $this->progressRepository->completedByPlayer['4:9']);
        self::assertSame([11], $this->progressRepository->completedByPlayer['5:9']);
        self::assertCount(2, $this->attemptRepository->attempts);
    }

    public function testFailedAttemptInsertionRollsBack(): void {
        $this->attemptRepository->allowInsert = false;

        $submission = $this->submit(4, 11, 'click', 'bon', 'uid-9');

        self::assertSame('attempt_failed', $submission['status']);
        self::assertSame([], $this->progressRepository->completedByPlayer);
        self::assertSame(['START TRANSACTION', 'ROLLBACK'], $this->database->queries);
    }

    public function testFailedProgressInsertionRollsBack(): void {
        $this->progressRepository->allowWrite = false;

        $submission = $this->submit(4, 11, 'click', 'bon', 'uid-10');

        self::assertSame('unavailable', $submission['status']);
        self::assertSame(['START TRANSACTION', 'ROLLBACK'], $this->database->queries);
    }

    public function testUnexpectedDatabaseFailureRollsBackAndIsRethrown(): void {
        $this->attemptRepository->throwOnInsert = true;

        try {
            $this->submit(4, 11, 'click', 'bon', 'uid-11');
            self::fail('The database exception should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Database failure', $exception->getMessage());
        }

        self::assertSame(['START TRANSACTION', 'ROLLBACK'], $this->database->queries);
    }

    private function submit(int $userId, int $stepId, string $interaction, string $result, string $uid): array {
        return $this->service->submit(
            $userId,
            9,
            $stepId,
            [11, 12],
            $interaction,
            $result,
            '2026-10-03 12:00:00',
            $uid,
            '127.0.0.1',
            'PHPUnit'
        );
    }
}
