<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Validate the complete request context before a step submission acquires its lock. */
final class RiddleStepSubmissionRequestPolicy {
    public const ACCESS_DENIED = 'access_denied';
    public const INVALID_STEP = 'invalid_step';

    /** @param string[] $allowedWidgets */
    public function validate(
        int $userId,
        int $riddleId,
        int $stepId,
        string $answer,
        bool $answerRequired,
        string $riddlePostType,
        bool $riddleAccessFunctionAvailable,
        bool $canViewRiddle,
        bool $canModifyRiddle,
        string $stepPostType,
        int $parentRiddleId,
        string $widgetType,
        array $allowedWidgets
    ): ?string {
        if (
            $userId <= 0
            || $riddleId <= 0
            || $riddlePostType !== 'enigme'
            || !$riddleAccessFunctionAvailable
            || !$canViewRiddle
            || $canModifyRiddle
        ) {
            return self::ACCESS_DENIED;
        }

        if (
            $stepId <= 0
            || $stepPostType !== 'enigme_etape'
            || $parentRiddleId !== $riddleId
            || !in_array($widgetType, $allowedWidgets, true)
            || ($answerRequired && trim($answer) === '')
        ) {
            return self::INVALID_STEP;
        }

        return null;
    }

    public function hasReachedLimit(int $maximumFailures, int $usedFailures): bool {
        return $maximumFailures > 0 && $usedFailures >= $maximumFailures;
    }
}
