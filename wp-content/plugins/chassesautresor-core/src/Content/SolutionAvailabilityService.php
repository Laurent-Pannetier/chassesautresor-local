<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Determine whether a hunt or riddle solution has reached its publication time.
 */
class SolutionAvailabilityService
{
    public function isAvailable(
        string $huntStatus,
        string $availabilityMode,
        ?int $baseTimestamp,
        int $delayDays,
        string $publicationTime,
        int $currentTimestamp
    ): bool {
        if ($huntStatus !== 'termine') {
            return false;
        }

        if ($availabilityMode !== 'differee') {
            return true;
        }

        $baseTimestamp = $baseTimestamp ?? $currentTimestamp;
        $targetTimestamp = strtotime("+{$delayDays} days {$publicationTime}", $baseTimestamp);

        return $targetTimestamp === false || $currentTimestamp >= $targetTimestamp;
    }
}
