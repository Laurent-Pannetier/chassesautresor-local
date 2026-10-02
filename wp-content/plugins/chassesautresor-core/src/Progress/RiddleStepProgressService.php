<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Resolve and advance a player's position in a linear intermediate-step path. */
final class RiddleStepProgressService {
    private RiddleStepProgressRepository $repository;

    public function __construct(RiddleStepProgressRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * @param int[] $orderedStepIds
     * @return array{completed_step_ids:int[],visible_step_ids:int[],current_step_id:?int,final_answer_unlocked:bool}
     */
    public function getState(int $userId, int $riddleId, array $orderedStepIds): array {
        $orderedStepIds = $this->normalizeIds($orderedStepIds);
        $completedStepIds = $this->repository->findCompletedStepIds($userId, $riddleId);
        $completedLookup = array_fill_keys($completedStepIds, true);
        $visibleStepIds = [];
        $currentStepId = null;

        foreach ($orderedStepIds as $stepId) {
            $visibleStepIds[] = $stepId;
            if (!isset($completedLookup[$stepId])) {
                $currentStepId = $stepId;
                break;
            }
        }

        return [
            'completed_step_ids' => array_values(
                array_intersect($orderedStepIds, $completedStepIds)
            ),
            'visible_step_ids' => $visibleStepIds,
            'current_step_id' => $currentStepId,
            'final_answer_unlocked' => $currentStepId === null,
        ];
    }

    /**
     * @param int[] $orderedStepIds
     * @return array<string, mixed>|null
     */
    public function completeCurrentStep(
        int $userId,
        int $riddleId,
        int $stepId,
        array $orderedStepIds,
        string $completedAt,
        ?string $attemptUid = null
    ): ?array {
        $state = $this->getState($userId, $riddleId, $orderedStepIds);
        if ($state['current_step_id'] !== $stepId) {
            return null;
        }

        if (!$this->repository->markCompleted($userId, $riddleId, $stepId, $completedAt, $attemptUid)) {
            return null;
        }

        return $this->getState($userId, $riddleId, $orderedStepIds);
    }

    public function deleteForRiddle(int $riddleId): int {
        return $this->repository->deleteForRiddle($riddleId);
    }

    public function hasProgressForRiddle(int $riddleId): bool {
        return $this->repository->hasProgressForRiddle($riddleId);
    }

    public function deleteForStep(int $stepId): int {
        return $this->repository->deleteForStep($stepId);
    }

    /** @param mixed[] $values @return int[] */
    private function normalizeIds(array $values): array {
        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }
}
