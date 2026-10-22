<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate the functional status of a hunt from its business data.
 */
class HuntStatusService
{
    private const VALID_STATUSES = ['revision', 'a_venir', 'en_cours', 'payante', 'termine'];

    /**
     * Determine whether a persisted status no longer matches the hunt data.
     */
    public function isStale(
        string $currentStatus,
        string $validationStatus,
        ?int $startTimestamp,
        ?int $endTimestamp,
        ?int $discoveryTimestamp,
        int $pointCost,
        bool $isUnlimited,
        int $currentTimestamp,
        bool $discoveryClosesHunt = true
    ): bool {
        return !in_array($currentStatus, self::VALID_STATUSES, true)
            || $currentStatus !== $this->calculate(
                $validationStatus,
                $startTimestamp,
                $endTimestamp,
                $discoveryTimestamp,
                $pointCost,
                $isUnlimited,
                $currentTimestamp,
                $currentStatus,
                $discoveryClosesHunt
            );
    }

    public function calculate(
        string $validationStatus,
        ?int $startTimestamp,
        ?int $endTimestamp,
        ?int $discoveryTimestamp,
        int $pointCost,
        bool $isUnlimited,
        int $currentTimestamp,
        string $currentStatus = 'revision',
        bool $discoveryClosesHunt = true
    ): string {
        if ($validationStatus !== 'valide') {
            return 'revision';
        }

        // Automatic mode closes the hunt when a winner is recorded.
        // Manual mode keeps it open: the discovery date is only an announcement.
        if ($discoveryClosesHunt && $discoveryTimestamp !== null) {
            return 'termine';
        }

        if (!$isUnlimited && $endTimestamp !== null && $endTimestamp < $currentTimestamp) {
            return 'termine';
        }

        if ($startTimestamp !== null && $startTimestamp <= $currentTimestamp) {
            return $pointCost > 0 ? 'payante' : 'en_cours';
        }

        if ($startTimestamp !== null) {
            return 'a_venir';
        }

        return in_array($currentStatus, self::VALID_STATUSES, true) ? $currentStatus : 'revision';
    }
}
