<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Manage riddle-level player engagements.
 */
class RiddleEngagementService
{
    private RiddleEngagementRepository $repository;

    public function __construct(RiddleEngagementRepository $repository)
    {
        $this->repository = $repository;
    }

    public function isEngaged(int $userId, int $riddleId): bool
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return $this->repository->exists($userId, $riddleId);
    }
}
