<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class RiddleAttemptListRequestService {
    /** @return array{error:?string,page:int,per_page:int,offset:int} */
    public function prepare(
        bool $loggedIn,
        int $riddleId,
        string $postType,
        bool $canModify,
        int $requestedPage,
        int $perPage = 20
    ): array {
        $error = null;
        if (!$loggedIn) {
            $error = 'non_connecte';
        } elseif ($riddleId <= 0 || $postType !== 'enigme') {
            $error = 'post_invalide';
        } elseif (!$canModify) {
            $error = 'acces_refuse';
        }

        $page = max(1, $requestedPage);
        $perPage = max(1, $perPage);

        return [
            'error' => $error,
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }
}
