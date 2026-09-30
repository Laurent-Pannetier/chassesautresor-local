<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Remove missing riddles from cached hunt relationships.
 */
class RiddleRelationshipCleanupService {
    /**
     * @param object[] $rows Rows containing post_id and meta_value properties.
     */
    public function clean(
        array $rows,
        callable $unserialize,
        callable $riddleExists,
        callable $persist
    ): int {
        $updatedHunts = 0;
        $relationshipService = new RiddleRelationshipService();

        foreach ($rows as $row) {
            if (!isset($row->post_id, $row->meta_value)) {
                continue;
            }

            $relations = $unserialize($row->meta_value);
            if (!is_array($relations)) {
                continue;
            }

            $cleanRelations = $relationshipService->filterExistingRiddleIds($relations, $riddleExists);
            if ($cleanRelations === array_values($relations)) {
                continue;
            }

            $persist((int) $row->post_id, $cleanRelations);
            $updatedHunts++;
        }

        return $updatedHunts;
    }
}
