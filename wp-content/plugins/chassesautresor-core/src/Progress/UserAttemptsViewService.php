<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Build the shared view model for a user's attempts table. */
final class UserAttemptsViewService {
    private UserAttemptStatisticsService $statistics;

    public function __construct(UserAttemptStatisticsService $statistics) {
        $this->statistics = $statistics;
    }

    /** @return array<string, mixed> */
    public function build(int $userId, int $page = 1, int $perPage = 10, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $search = trim($search);
        $summary = $this->statistics->summarize($userId);
        $pagination = $this->statistics->paginate($userId, $page, $perPage, $search);

        return [
            'pending' => $summary['pending'],
            'total' => $summary['total'],
            'success' => $summary['success'],
            'search_term' => $search,
            'page' => $pagination['page'],
            'pages' => $pagination['pages'],
            'per_page' => $perPage,
            'filtered_total' => $pagination['total'],
            'tentatives' => $pagination['items'],
            'no_results_message' => $search !== ''
                ? __('Aucune tentative ne correspond à votre recherche.', 'chassesautresor-com')
                : __('Vous n\'avez pas encore enregistré de tentative.', 'chassesautresor-com'),
        ];
    }
}
