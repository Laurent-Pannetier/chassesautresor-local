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
}
