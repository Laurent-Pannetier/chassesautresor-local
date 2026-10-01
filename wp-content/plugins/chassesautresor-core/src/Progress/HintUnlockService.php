<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Points\PointsService;

/**
 * Apply business rules when checking player hint unlocks.
 */
class HintUnlockService
{
    private HintUnlockRepository $repository;
    private PointsService $pointsService;

    public function __construct(HintUnlockRepository $repository, PointsService $pointsService)
    {
        $this->repository = $repository;
        $this->pointsService = $pointsService;
    }

    public function isUnlocked(int $userId, int $hintId): bool
    {
        if ($userId <= 0 || $hintId <= 0) {
            return false;
        }

        return $this->repository->exists($userId, $hintId);
    }

    /**
     * @param int[] $hintIds
     * @return int[]
     */
    public function unlockedHintIds(int $userId, array $hintIds): array
    {
        return $this->repository->findUnlockedHintIds($userId, $hintIds);
    }

    public function recordUnlock(
        int $userId,
        int $hintId,
        ?int $huntId,
        ?int $riddleId,
        int $pointsSpent,
        string $unlockedAt,
        string $pointsReason = ''
    ): bool {
        if ($userId <= 0 || $hintId <= 0 || $pointsSpent < 0 || trim($unlockedAt) === '') {
            return false;
        }

        $huntId = $huntId !== null && $huntId > 0 ? $huntId : null;
        $riddleId = $riddleId !== null && $riddleId > 0 ? $riddleId : null;

        $this->repository->beginTransaction();
        if (!$this->repository->insertUnlock(
            $userId,
            $hintId,
            $huntId,
            $riddleId,
            $pointsSpent,
            $unlockedAt
        )) {
            $this->repository->rollBack();
            return false;
        }

        if (!$this->repository->insertEngagement(
            $userId,
            $hintId,
            $huntId,
            $riddleId,
            $unlockedAt
        )) {
            $this->repository->rollBack();
            return false;
        }

        if ($pointsSpent > 0) {
            $this->pointsService->deduct($userId, $pointsSpent, $pointsReason, 'indice', $hintId);
        }
        $this->repository->commit();

        return true;
    }
}
