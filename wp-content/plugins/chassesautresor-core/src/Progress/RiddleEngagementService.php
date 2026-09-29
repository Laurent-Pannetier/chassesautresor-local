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

    /** @return array{success:bool,created:bool} */
    public function ensureEngaged(int $userId, int $riddleId, string $engagedAt): array
    {
        if ($userId <= 0 || $riddleId <= 0 || trim($engagedAt) === '') {
            return ['success' => false, 'created' => false];
        }

        if ($this->repository->exists($userId, $riddleId)) {
            return ['success' => true, 'created' => false];
        }

        $created = $this->repository->insert($userId, $riddleId, $engagedAt);

        return ['success' => $created, 'created' => $created];
    }
}
