<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

use ChassesAuTresor\Core\Progress\UserAttemptsViewService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Build account dashboard data without depending on theme renderers. */
final class AccountDashboardDataService {
    private object $database;

    public function __construct(object $database) {
        $this->database = $database;
    }

    /** @return array{allowed:bool,pagination:array{ids:int[],page:int,total_pages:int,total_items:int}} */
    public function engagedHunts(object $user, int $requestedPage, int $perPage): array {
        $userId = (int) ($user->ID ?? 0);
        $roles = (array) ($user->roles ?? []);
        $allowed = $userId > 0
            && !empty(array_intersect(['subscriber', 'customer'], $roles))
            && !in_array('administrator', $roles, true);
        if (!$allowed) {
            return [
                'allowed' => false,
                'pagination' => ['ids' => [], 'page' => 1, 'total_pages' => 0, 'total_items' => 0],
            ];
        }

        $ids = ca_get_user_engaged_hunt_ids($userId);

        return [
            'allowed' => true,
            'pagination' => ca_prepare_engaged_hunts_pagination(
                $ids,
                max(1, $requestedPage),
                max(1, $perPage)
            ),
        ];
    }

    /** @return array<string,mixed> */
    public function attempts(int $userId, int $page = 1, int $perPage = 10, string $search = ''): array {
        $statistics = CoreServiceFactory::userAttemptStatistics($this->database);

        return (new UserAttemptsViewService($statistics))->build($userId, $page, $perPage, $search);
    }
}
