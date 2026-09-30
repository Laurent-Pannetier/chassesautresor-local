<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Determine whether a hunt or riddle solution has reached its publication time.
 */
class SolutionAvailabilityService
{
    /**
     * @return array{state:string,target_timestamp:?int}
     */
    public function getPublicationPlan(
        string $huntStatus,
        string $availabilityMode,
        int $delayDays,
        string $publicationTime,
        int $currentTimestamp
    ): array {
        if ($huntStatus !== 'termine') {
            return [
                'state' => $availabilityMode === 'differee' ? 'FIN_CHASSE_DIFFERE' : 'FIN_CHASSE',
                'target_timestamp' => null,
            ];
        }

        $targetTimestamp = $currentTimestamp;
        if ($availabilityMode === 'differee') {
            $calculatedTimestamp = strtotime(
                sprintf('+%d days %s', max(0, $delayDays), $publicationTime),
                $currentTimestamp
            );
            $targetTimestamp = $calculatedTimestamp === false ? $currentTimestamp : $calculatedTimestamp;
        }

        if ($targetTimestamp <= $currentTimestamp) {
            return ['state' => 'EN_COURS', 'target_timestamp' => null];
        }

        return ['state' => 'A_VENIR', 'target_timestamp' => $targetTimestamp];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDueSolutionIdsQueryArgs(string $currentDate): array
    {
        if ($currentDate === '') {
            return [];
        }

        return [
            'post_type' => 'solution',
            'post_status' => ['publish', 'pending', 'draft', 'private', 'future'],
            'fields' => 'ids',
            'no_found_rows' => true,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'solution_cache_etat_systeme',
                    'value' => 'A_VENIR',
                ],
                [
                    'key' => 'solution_date_disponibilite',
                    'value' => $currentDate,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
            ],
        ];
    }

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
