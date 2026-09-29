<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Hints;

/**
 * Manage user hint unlocks.
 */
class HintUnlockService
{
    private HintUnlockRepository $repository;

    public function __construct(HintUnlockRepository $repository)
    {
        $this->repository = $repository;
    }

    public function isUnlocked(int $userId, int $hintId): bool
    {
        if ($userId <= 0 || $hintId <= 0) {
            return false;
        }

        return $this->repository->exists($userId, $hintId);
    }

    public function recordUnlock(
        int $userId,
        int $hintId,
        int $huntId,
        int $riddleId,
        int $pointsSpent,
        string $unlockedAt
    ): bool {
        if ($userId <= 0 || $hintId <= 0 || $pointsSpent < 0 || trim($unlockedAt) === '') {
            return false;
        }

        if ($this->repository->exists($userId, $hintId)) {
            return true;
        }

        $huntId = $huntId > 0 ? $huntId : null;
        $riddleId = $riddleId > 0 ? $riddleId : null;

        if (!$this->repository->insert($userId, $hintId, $huntId, $riddleId, $pointsSpent, $unlockedAt)) {
            return false;
        }

        return $this->repository->insertEngagement($userId, $hintId, $huntId, $riddleId, $unlockedAt);
    }
}
