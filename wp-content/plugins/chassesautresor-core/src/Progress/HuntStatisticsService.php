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

    public function countEngagements(int $huntId, array $excludedUserIds = []): int
    {
        return $huntId > 0 ? $this->repository->countEngagements($huntId, $excludedUserIds) : 0;
    }

    /** @param int[] $riddleIds */
    public function calculateEngagementRate(
        int $participants,
        array $riddleIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): float {
        if ($participants <= 0 || $riddleIds === []) {
            return 0.0;
        }

        $engagements = $this->repository->sumEngagedPlayersByRiddle(
            $riddleIds,
            $startAt,
            $endAt,
            $excludedUserIds
        );

        return (100 * $engagements) / ($participants * count($riddleIds));
    }

    /** @param int[] $riddleIds */
    public function calculateResolutionRate(
        array $riddleIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): float {
        if ($riddleIds === []) {
            return 0.0;
        }
        $engaged = $this->repository->sumEngagedPlayersByRiddle(
            $riddleIds,
            $startAt,
            $endAt,
            $excludedUserIds
        );
        if ($engaged === 0) {
            return 0.0;
        }
        $solved = $this->repository->sumSolvedPlayersByRiddle(
            $riddleIds,
            $startAt,
            $endAt,
            $excludedUserIds
        );
        return (100 * $solved) / $engaged;
    }
}
