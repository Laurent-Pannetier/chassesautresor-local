<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Apply a validated display order to riddles belonging to a hunt.
 */
class RiddleOrderingService {
    /**
     * @param int[] $submittedIds
     * @param int[] $huntRiddleIds
     */
    public function apply(array $submittedIds, array $huntRiddleIds, callable $persist): int {
        $orderedIds = (new RiddleManagementService())->getReorderUpdates($submittedIds, $huntRiddleIds);

        foreach ($orderedIds as $menuOrder => $riddleId) {
            $persist($riddleId, $menuOrder);
        }

        return count($orderedIds);
    }
}
