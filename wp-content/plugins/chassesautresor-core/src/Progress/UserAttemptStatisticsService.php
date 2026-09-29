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

    /** @return array{page: int, pages: int, total: int, items: object[]} */
    public function paginate(int $userId, int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        if ($userId <= 0) {
            return ['page' => 1, 'pages' => 0, 'total' => 0, 'items' => []];
        }

        $search = trim($search);
        $total = $this->repository->countForUser($userId, $search);
        $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $page = $pages > 0 ? min($page, $pages) : 1;
        $items = $this->repository->findForUser($userId, $search, $perPage, ($page - 1) * $perPage);

        return ['page' => $page, 'pages' => $pages, 'total' => $total, 'items' => $items];
    }
}
