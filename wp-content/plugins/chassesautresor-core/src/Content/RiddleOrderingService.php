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
        $submitted = (new RiddleManagementService())->getReorderUpdates($submittedIds, $huntRiddleIds);
        if ($submitted === []) {
            return 0;
        }

        foreach ($submitted as $menuOrder => $riddleId) {
            $persist($riddleId, $menuOrder);
        }

        return count($submitted);
    }

    /**
     * Full hunt order after a drag: submitted riddles first, then any riddle left out.
     *
     * @param int[] $submittedIds
     * @param int[] $huntRiddleIds
     * @return int[]
     */
    public function cacheOrder(array $submittedIds, array $huntRiddleIds): array {
        $orderedIds = array_values(
            (new RiddleManagementService())->getReorderUpdates($submittedIds, $huntRiddleIds)
        );
        $seen = array_fill_keys($orderedIds, true);
        foreach ($huntRiddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId > 0 && !isset($seen[$riddleId])) {
                $orderedIds[] = $riddleId;
                $seen[$riddleId] = true;
            }
        }

        return $orderedIds;
    }
}
