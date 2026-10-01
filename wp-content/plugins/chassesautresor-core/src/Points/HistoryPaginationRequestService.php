<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

class HistoryPaginationRequestService {
    /** @return array{allowed:bool,page:int,per_page:int,offset:int} */
    public function prepare(bool $loggedIn, int $requestedPage, int $perPage): array {
        $page = max(1, $requestedPage);
        $perPage = max(1, $perPage);

        return [
            'allowed' => $loggedIn,
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }
}
