<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Coordinate the status and persistence sides of a riddle engagement. */
class RiddleEngagementApplicationService {
    public function engage(
        int $userId,
        int $riddleId,
        callable $updateStatus,
        callable $persist
    ): bool {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return (bool) $updateStatus($riddleId, $userId, 'en_cours', true)
            && (bool) $persist($userId, $riddleId);
    }
}
