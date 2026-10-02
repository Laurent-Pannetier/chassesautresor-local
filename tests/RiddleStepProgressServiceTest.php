<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepProgressRepository;
use ChassesAuTresor\Core\Progress\RiddleStepProgressService;
use PHPUnit\Framework\TestCase;

final class InMemoryRiddleStepProgressRepository extends RiddleStepProgressRepository {
    public array $completedIds = [];
    public bool $allowWrite = true;
    public bool $hasProgress = false;

    public function __construct() {
    }

    public function findCompletedStepIds(int $userId, int $riddleId): array {
        return $this->completedIds;
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

        $this->completedIds[] = $stepId;
        return true;
    }

    public function hasProgressForRiddle(int $riddleId): bool {
        return $this->hasProgress;
    }
}

final class RiddleStepProgressServiceTest extends TestCase {
    public function testOnlyTheFirstIncompleteStepIsExposed(): void {
        $repository = new InMemoryRiddleStepProgressRepository();
        $repository->completedIds = [11];
        $service = new RiddleStepProgressService($repository);

        $state = $service->getState(4, 9, [11, 12, 13]);

        self::assertSame([11], $state['completed_step_ids']);
        self::assertSame([11, 12], $state['visible_step_ids']);
        self::assertSame(12, $state['current_step_id']);
        self::assertFalse($state['final_answer_unlocked']);
    }

    public function testCompletingTheCurrentStepRevealsTheNextOne(): void {
        $repository = new InMemoryRiddleStepProgressRepository();
        $service = new RiddleStepProgressService($repository);

        $state = $service->completeCurrentStep(4, 9, 11, [11, 12], '2026-10-02 12:00:00');

        self::assertNotNull($state);
        self::assertSame([11, 12], $state['visible_step_ids']);
        self::assertSame(12, $state['current_step_id']);
        self::assertFalse($state['final_answer_unlocked']);
    }

    public function testCompletingTheLastStepUnlocksTheFinalAnswer(): void {
        $repository = new InMemoryRiddleStepProgressRepository();
        $repository->completedIds = [11];
        $service = new RiddleStepProgressService($repository);

        $state = $service->completeCurrentStep(4, 9, 12, [11, 12], '2026-10-02 12:00:00');

        self::assertNotNull($state);
        self::assertNull($state['current_step_id']);
        self::assertTrue($state['final_answer_unlocked']);
    }

    public function testAHiddenOrCompletedStepCannotBeCompletedAgain(): void {
        $repository = new InMemoryRiddleStepProgressRepository();
        $service = new RiddleStepProgressService($repository);

        self::assertNull($service->completeCurrentStep(4, 9, 12, [11, 12], '2026-10-02 12:00:00'));

        $repository->completedIds = [11];
        self::assertNull($service->completeCurrentStep(4, 9, 11, [11, 12], '2026-10-02 12:00:00'));
    }

    public function testAnEmptyPathLeavesTheFinalAnswerAvailable(): void {
        $service = new RiddleStepProgressService(new InMemoryRiddleStepProgressRepository());
        $state = $service->getState(4, 9, []);

        self::assertSame([], $state['visible_step_ids']);
        self::assertTrue($state['final_answer_unlocked']);
    }

    public function testProgressPresenceIsDelegatedToTheRepository(): void {
        $repository = new InMemoryRiddleStepProgressRepository();
        $repository->hasProgress = true;

        self::assertTrue((new RiddleStepProgressService($repository))->hasProgressForRiddle(9));
    }

    public function testCleanupIsDelegatedToTheRepository(): void {
        $repository = new class extends RiddleStepProgressRepository {
            public array $deleted = [];

            public function __construct() {
            }

            public function deleteForRiddle(int $riddleId): int {
                $this->deleted[] = ['riddle', $riddleId];
                return 3;
            }

            public function deleteForStep(int $stepId): int {
                $this->deleted[] = ['step', $stepId];
                return 1;
            }
        };
        $service = new RiddleStepProgressService($repository);

        self::assertSame(3, $service->deleteForRiddle(9));
        self::assertSame(1, $service->deleteForStep(11));
        self::assertSame([['riddle', 9], ['step', 11]], $repository->deleted);
    }
}
