<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Apply business rules when checking player hint unlocks.
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
}
