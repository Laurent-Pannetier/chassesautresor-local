<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Validate a complete step ordering and build WordPress menu-order updates. */
final class RiddleStepOrderingService {
    /**
     * @param int[] $existingIds
     * @param mixed[] $submittedIds
     * @return array<int, array{id:int,menu_order:int}>
     */
    public function buildUpdatePlan(array $existingIds, array $submittedIds): array {
        $existingIds = $this->normalizeIds($existingIds);
        $submittedIds = $this->normalizeIds($submittedIds);
        $expectedIds = $existingIds;
        $receivedIds = $submittedIds;
        sort($expectedIds);
        sort($receivedIds);

        if ($expectedIds !== $receivedIds || count($submittedIds) !== count($existingIds)) {
            return [];
        }

        $updates = [];
        foreach ($submittedIds as $position => $stepId) {
            $updates[] = [
                'id' => $stepId,
                'menu_order' => $position,
            ];
        }

        return $updates;
    }

    /** @param mixed[] $values @return int[] */
    private function normalizeIds(array $values): array {
        return array_values(array_filter(array_map('intval', $values)));
    }
}
