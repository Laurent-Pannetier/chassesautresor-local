<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Aggregate organizer totals from hunt ids already resolved by the caller. */
final class OrganizerStatisticsService
{
    /**
     * @param int[] $huntIds
     * @param int[] $excludedUserIds
     */
    public function countUniquePlayers(array $huntIds, array $excludedUserIds, callable $count): int
    {
        $huntIds = array_values(array_filter(array_map('intval', $huntIds)));
        if ($huntIds === []) {
            return 0;
        }

        return (int) $count($huntIds, array_values(array_filter(array_map('intval', $excludedUserIds))));
    }

    /**
     * @param int[] $huntIds
     */
    public function sumCollectedPoints(array $huntIds, callable $pointsForHunt): int
    {
        $total = 0;
        foreach (array_map('intval', $huntIds) as $huntId) {
            if ($huntId <= 0) {
                continue;
            }
            $total += (int) $pointsForHunt($huntId);
        }

        return $total;
    }
}
