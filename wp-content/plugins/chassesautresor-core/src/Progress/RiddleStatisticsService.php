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

    public function countEngagedPlayers(
        int $id,
        ?string $start = null,
        ?string $end = null,
        array $excludedUserIds = []
    ): int {
        return $id > 0
            ? $this->repository->countEngagedPlayers($id, $start, $end, $excludedUserIds)
            : 0;
    }

    public function listSolvers(int $id, array $excludedUserIds = []): array
    {
        if ($id <= 0) {
            return [];
        }
        return array_map(
            static fn (array $row): array => [
                'user_id' => (int) $row['user_id'],
                'username' => $row['username'],
                'date' => $row['resolution_date'],
                'tentatives' => (int) $row['tentatives'],
            ],
            $this->repository->listSolvers($id, $excludedUserIds)
        );
    }

    public function listParticipants(
        int $id,
        array $excludedUserIds,
        int $limit,
        int $offset,
        string $orderBy,
        string $order
    ): array {
        return $id > 0
            ? $this->repository->listParticipants($id, $excludedUserIds, $limit, $offset, $orderBy, $order)
            : [];
    }
}
