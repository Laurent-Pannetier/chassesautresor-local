<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class RiddleStatisticsService
{
    private RiddleStatisticsRepository $repository;

    public function __construct(RiddleStatisticsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function countAttempts(int $id, ?string $start = null, ?string $end = null): int
    {
        return $id > 0 ? $this->repository->aggregateAttempts($id, 'COUNT(*)', null, $start, $end) : 0;
    }

    public function sumSpentPoints(int $id, ?string $start = null, ?string $end = null): int
    {
        return $id > 0 ? $this->repository->aggregateAttempts($id, 'SUM(points_utilises)', null, $start, $end) : 0;
    }

    public function countCorrectSolutions(int $id, ?string $start = null, ?string $end = null): int
    {
        return $id > 0 ? $this->repository->aggregateAttempts($id, 'COUNT(*)', 'bon', $start, $end) : 0;
    }
}
