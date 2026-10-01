<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Coordinate validation, persistence and point charging for a hunt engagement. */
class HuntEngagementApplicationService {
    /**
     * @return array{status:string,charged:int}
     */
    public function engage(
        int $userId,
        int $huntId,
        string $postType,
        bool $nonceValid,
        bool $administrator,
        bool $organizer,
        int $cost,
        int $balance,
        callable $persist,
        callable $debit
    ): array {
        if ($userId <= 0 || $huntId <= 0 || $postType !== 'chasse') {
            return ['status' => 'invalid_request', 'charged' => 0];
        }
        if (!$nonceValid) {
            return ['status' => 'invalid_nonce', 'charged' => 0];
        }
        if ($administrator || $organizer) {
            return ['status' => 'engagement_failed', 'charged' => 0];
        }

        $cost = max(0, $cost);
        if ($cost > $balance) {
            return ['status' => 'points_insuffisants', 'charged' => 0];
        }
        if (!(bool) $persist($userId, $huntId)) {
            return ['status' => 'engagement_failed', 'charged' => 0];
        }
        if ($cost > 0) {
            $debit($userId, $huntId, $cost);
        }

        return ['status' => 'success', 'charged' => $cost];
    }
}
