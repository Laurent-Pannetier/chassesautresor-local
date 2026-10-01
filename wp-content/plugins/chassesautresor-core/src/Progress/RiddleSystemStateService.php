<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate the global accessibility state of a riddle.
 */
class RiddleSystemStateService {
    public function calculate(
        bool $hasValidHunt,
        string $huntStatus,
        string $accessCondition,
        ?int $scheduledTimestamp,
        string $validationMode,
        bool $hasAnswers,
        ?int $currentTimestamp = null
    ): string {
        if (!$hasValidHunt || !in_array($huntStatus, ['en_cours', 'payante', 'termine'], true)) {
            return 'bloquee_chasse';
        }
        if ($accessCondition === 'date_programmee') {
            $currentTimestamp = $currentTimestamp ?? time();
            if ($scheduledTimestamp === null || $scheduledTimestamp > $currentTimestamp) {
                return 'bloquee_date';
            }
        } elseif ($accessCondition === 'pre_requis') {
            return 'bloquee_pre_requis';
        }
        if ($validationMode === 'automatique' && !$hasAnswers) {
            return 'invalide';
        }

        return 'accessible';
    }
}
