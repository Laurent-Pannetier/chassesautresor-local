<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Decide whether the ordered structure of a riddle step path is immutable. */
final class RiddleStepStructureLockService {
    private const LOCKED_HUNT_STATUSES = ['en_cours', 'payante', 'termine'];

    public function isLocked(int $riddleId, callable $getField, callable $hasProgress): bool {
        if ($riddleId <= 0) {
            return true;
        }

        if ($hasProgress($riddleId)) {
            return true;
        }

        $hunt = $getField('enigme_chasse_associee', $riddleId);
        $huntId = is_array($hunt) ? (int) reset($hunt) : (int) $hunt;
        if ($huntId <= 0) {
            return false;
        }

        $huntStatus = (string) $getField('chasse_cache_statut', $huntId);
        return in_array($huntStatus, self::LOCKED_HUNT_STATUSES, true);
    }
}
