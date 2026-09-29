<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Provide attempt totals for a user account.
 */
class UserAttemptStatisticsService
{
    private UserAttemptStatisticsRepository $repository;

    public function __construct(UserAttemptStatisticsRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return array{pending: int, total: int, success: int} */
    public function summarize(int $userId): array
    {
        if ($userId <= 0) {
            return ['pending' => 0, 'total' => 0, 'success' => 0];
        }

        return $this->repository->summarize($userId);
    }
}
