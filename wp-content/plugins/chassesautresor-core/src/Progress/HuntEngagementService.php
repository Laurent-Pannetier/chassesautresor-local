<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Manage hunt-level player engagements.
 */
class HuntEngagementService
{
    private HuntEngagementRepository $repository;

    public function __construct(HuntEngagementRepository $repository)
    {
        $this->repository = $repository;
    }

    public function isEngaged(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }

        return $this->repository->exists($userId, $huntId);
    }

    public function countPlayers(int $huntId): int
    {
        return $huntId > 0 ? $this->repository->countByHunt($huntId) : 0;
    }

    public function countParticipants(int $huntId, ?string $startAt = null, ?string $endAt = null): int
    {
        if ($huntId <= 0) {
            return 0;
        }

        return $this->repository->countParticipants($huntId, $startAt, $endAt);
    }

    public function engage(int $userId, int $huntId, string $engagedAt): bool
    {
        if ($userId <= 0 || $huntId <= 0 || $this->repository->exists($userId, $huntId)) {
            return false;
        }

        return $this->repository->insert($userId, $huntId, $engagedAt);
    }
}
