<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class StatisticsParticipantRequestService {
    /** @return array{page:int,limit:int,offset:int,order:string} */
    public function prepare(int $page, int $limit, string $order): array {
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'order' => strtoupper($order) === 'DESC' ? 'DESC' : 'ASC',
        ];
    }
}
