<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Coordinate changes to the cached relationship between a hunt and its riddles.
 */
class RiddleRelationshipLifecycleService {
    public function attach(
        int $riddleId,
        $huntValue,
        callable $isHunt,
        callable $attach
    ): bool {
        $huntId = (new RiddleRelationshipService())->resolveHuntId($huntValue);
        if ($huntId === 0 || !$isHunt($huntId)) {
            return false;
        }

        return (bool) $attach($huntId, $riddleId);
    }

    public function detach(
        int $riddleId,
        $huntValue,
        callable $detach,
        callable $cleanup
    ): bool {
        $huntId = (new RiddleRelationshipService())->resolveHuntId($huntValue);
        if ($huntId === 0) {
            return false;
        }

        $detached = (bool) $detach($huntId, $riddleId);
        $cleanup();

        return $detached;
    }
}
