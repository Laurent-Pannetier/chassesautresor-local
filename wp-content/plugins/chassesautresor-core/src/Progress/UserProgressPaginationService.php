<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class UserProgressPaginationService {
    /** @return array{allowed:bool,page:int,per_page:int} */
    public function prepare(bool $loggedIn, int $userId, int $page, int $perPage): array {
        return [
            'allowed' => $loggedIn && $userId > 0,
            'page' => max(1, $page),
            'per_page' => max(1, $perPage),
        ];
    }
}
