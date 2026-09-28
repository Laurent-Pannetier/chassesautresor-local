<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/**
 * Apply business rules to user points operations.
 */
class PointsService
{
    private PointsRepository $repository;

    public function __construct(PointsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBalance(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->getBalance($userId);
    }

    public function changeBalance(
        int $userId,
        int $points,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->addPoints($userId, $points, $reason, $originType, $originId);
    }

    public function hasEnough(int $userId, int $amount): bool
    {
        if ($userId <= 0 || $amount < 0) {
            return false;
        }

        return $this->getBalance($userId) >= $amount;
    }

    public function deduct(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        if ($userId <= 0 || $amount <= 0) {
            return;
        }

        $this->changeBalance($userId, -$amount, $reason, $originType, $originId);
    }

    public function add(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        if ($userId <= 0 || $amount <= 0) {
            return;
        }

        $this->changeBalance($userId, $amount, $reason, $originType, $originId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(int $userId, int $page = 1, int $perPage = 20): array
    {
        if ($userId <= 0) {
            return [];
        }

        $offset = ($page - 1) * $perPage;

        return $this->repository->getHistory($userId, $perPage, $offset);
    }

    public function countHistory(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countHistory($userId);
    }
}
