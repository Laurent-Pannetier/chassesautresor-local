<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate the functional status of a hunt from its business data.
 */
class HuntStatusService
{
    public function calculate(
        string $validationStatus,
        ?int $startTimestamp,
        ?int $endTimestamp,
        ?int $discoveryTimestamp,
        int $pointCost,
        bool $isUnlimited,
        int $currentTimestamp,
        string $currentStatus = 'revision'
    ): string {
        if ($validationStatus !== 'valide') {
            return 'revision';
        }

        if ($discoveryTimestamp !== null) {
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

        return $currentStatus;
    }
}
