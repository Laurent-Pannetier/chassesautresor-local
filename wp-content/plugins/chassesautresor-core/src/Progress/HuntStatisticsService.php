<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Provide aggregate statistics for a hunt.
 */
class HuntStatisticsService
{
    private HuntStatisticsRepository $repository;

    public function __construct(HuntStatisticsRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @param int[] $riddleIds */
    public function countAttempts(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        return $riddleIds === [] ? 0 : $this->repository->countAttempts($riddleIds, $startAt, $endAt);
    }

    /** @param int[] $riddleIds */
    public function sumCollectedPoints(array $riddleIds, ?string $startAt = null, ?string $endAt = null): int
    {
        return $riddleIds === [] ? 0 : $this->repository->sumCollectedPoints($riddleIds, $startAt, $endAt);
    }

    public function countEngagements(int $huntId): int
    {
        return $huntId > 0 ? $this->repository->countEngagements($huntId) : 0;
    }
}
