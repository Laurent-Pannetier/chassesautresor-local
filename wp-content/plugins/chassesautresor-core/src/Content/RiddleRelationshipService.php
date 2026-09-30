<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Normalize and maintain relationships between riddles and hunts.
 */
class RiddleRelationshipService {
    public function resolveHuntId($value): int {
        return (new RelationshipService())->normalizeId($value) ?? 0;
    }

    /**
     * @param mixed[] $riddleIds
     * @return int[]
     */
    public function getSelectableRiddleIds(array $riddleIds, int $currentRiddleId): array {
        $ids = $this->normalizeUniqueIds($riddleIds);
        $ids = array_values(array_filter(
            $ids,
            static fn (int $riddleId): bool => $riddleId !== $currentRiddleId
        ));

        return $ids === [] ? [0] : $ids;
    }

    /**
     * @param mixed[] $riddleIds
     * @return int[]
     */
    public function filterExistingRiddleIds(array $riddleIds, callable $exists): array {
        $ids = $this->normalizeUniqueIds($riddleIds);

        return array_values(array_filter(
            $ids,
            static fn (int $riddleId): bool => (bool) $exists($riddleId)
        ));
    }

    /**
     * @param mixed[] $values
     * @return int[]
     */
    private function normalizeUniqueIds(array $values): array {
        $ids = (new RelationshipService())->normalizeIds($values);

        return array_values(array_unique($ids));
    }
}
