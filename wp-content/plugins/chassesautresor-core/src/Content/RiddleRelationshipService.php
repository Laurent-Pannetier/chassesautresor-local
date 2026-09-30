<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize and maintain relationships between riddles and hunts.
 */
class RiddleRelationshipService {
    public function resolveHuntId($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_object($value)) {
            return isset($value->ID) ? (int) $value->ID : 0;
        }

        return (int) $value;
    }

    /**
     * @param mixed[] $riddleIds
     * @return int[]
     */
    public function getSelectableRiddleIds(array $riddleIds, int $currentRiddleId): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $riddleIds))));
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
        $ids = array_values(array_unique(array_filter(array_map('intval', $riddleIds))));

        return array_values(array_filter(
            $ids,
            static fn (int $riddleId): bool => (bool) $exists($riddleId)
        ));
    }
}
