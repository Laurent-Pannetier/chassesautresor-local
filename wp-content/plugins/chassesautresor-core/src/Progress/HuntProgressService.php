<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate hunt progress from validatable and engagement-only riddles.
 */
class HuntProgressService
{
    private HuntProgressRepository $repository;

    public function __construct(HuntProgressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return array{completed:int,total:int,is_complete:bool}
     */
    public function calculate(int $userId, array $validatable, array $engagementOnly): array
    {
        if ($userId <= 0) {
            return ['completed' => 0, 'total' => 0, 'is_complete' => false];
        }

        $solved = $this->repository->countSolved($userId, $validatable);
        $engaged = $this->repository->countEngaged($userId, $engagementOnly);
        $total = count($validatable) + count($engagementOnly);
        $completed = $solved + $engaged;

        return [
            'completed' => $completed,
            'total' => $total,
            'is_complete' => $total > 0 && $completed === $total,
        ];
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return object[]
     */
    public function getCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return $this->repository->findCompletedUsers($validatable, $engagementOnly);
    }

    /** @param int[] $riddleIds */
    public function countSolvedRiddles(int $userId, array $riddleIds): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countSolved($userId, $riddleIds);
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, int[]> User IDs indexed by riddle ID.
     */
    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        return $this->repository->completeRiddles($riddleIds, $completedAt);
    }
}
