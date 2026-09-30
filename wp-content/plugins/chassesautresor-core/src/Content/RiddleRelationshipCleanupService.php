<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Remove missing riddles from cached hunt relationships.
 */
class RiddleRelationshipCleanupService {
    /**
     * @param object[] $rows Rows containing post_id and meta_value properties.
     * @param callable(array<int, int>): array<int, int> $findExistingRiddleIds
     */
    public function clean(
        array $rows,
        callable $unserialize,
        callable $findExistingRiddleIds,
        callable $persist
    ): int {
        $updatedHunts = 0;
        $relationshipService = new RiddleRelationshipService();
        $relationshipsByHunt = [];
        $referencedIds = [];

        foreach ($rows as $row) {
            if (!isset($row->post_id, $row->meta_value)) {
                continue;
            }

            $relations = $unserialize($row->meta_value);
            if (!is_array($relations)) {
                continue;
            }

            $relationshipsByHunt[(int) $row->post_id] = $relations;
            foreach ($relations as $riddleId) {
                $riddleId = (int) $riddleId;
                if ($riddleId > 0) {
                    $referencedIds[] = $riddleId;
                }
            }
        }

        $referencedIds = array_values(array_unique($referencedIds));
        $existingIds = $referencedIds === [] ? [] : $findExistingRiddleIds($referencedIds);
        $existingIds = array_fill_keys(array_map('intval', (array) $existingIds), true);

        foreach ($relationshipsByHunt as $huntId => $relations) {
            $cleanRelations = $relationshipService->filterExistingRiddleIds(
                $relations,
                static fn (int $riddleId): bool => isset($existingIds[$riddleId])
            );
            if ($cleanRelations === array_values($relations)) {
                continue;
            }

            $persist($huntId, $cleanRelations);
            $updatedHunts++;
        }

        return $updatedHunts;
    }
}
