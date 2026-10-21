<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a riddle configuration contains all required content.
 */
class RiddleCompletionService
{
    /**
     * @param int[] $riddleIds
     * @return array{has_incomplete:bool,can_add:bool}
     */
    public function evaluateManagementStatus(
        array $riddleIds,
        bool $canAdd,
        callable $refresh,
        callable $isComplete
    ): array {
        $completionFlags = [];

        foreach ($riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0) {
                continue;
            }

            $refresh($riddleId);
            $complete = (bool) $isComplete($riddleId);
            $completionFlags[] = $complete;
            if (!$complete) {
                break;
            }
        }

        return $this->getManagementStatus($completionFlags, $canAdd);
    }

    /**
     * @param bool[] $completionFlags
     * @return array{has_incomplete:bool,can_add:bool}
     */
    public function getManagementStatus(array $completionFlags, bool $canAdd): array
    {
        return [
            'has_incomplete' => in_array(false, $completionFlags, true),
            'can_add' => $canAdd,
        ];
    }

    public function isComplete(
        bool $hasValidTitle,
        int $imageId,
        int $placeholderImageId,
        string $validationMode,
        bool $hasAnswers,
        string $accessCondition,
        bool $hasPrerequisites,
        bool $hasCompleteSteps = true
    ): bool {
        return $this->missingKeys(
            $hasValidTitle,
            $imageId,
            $placeholderImageId,
            $validationMode,
            $hasAnswers,
            $accessCondition,
            $hasPrerequisites,
            $hasCompleteSteps
        ) === [];
    }

    /**
     * Stable keys for the fields that still block publication of a riddle.
     *
     * @return string[]
     */
    public function missingKeys(
        bool $hasValidTitle,
        int $imageId,
        int $placeholderImageId,
        string $validationMode,
        bool $hasAnswers,
        string $accessCondition,
        bool $hasPrerequisites,
        bool $hasCompleteSteps = true
    ): array {
        $missing = [];
        if (!$hasValidTitle) {
            $missing[] = 'title';
        }
        if ($imageId <= 0 || $imageId === $placeholderImageId) {
            $missing[] = 'illustrations';
        }
        if ($validationMode === 'automatique' && !$hasAnswers) {
            $missing[] = 'final_answer';
        }
        if ($accessCondition === 'pre_requis' && !$hasPrerequisites) {
            $missing[] = 'prerequisites';
        }
        if (!$hasCompleteSteps) {
            $missing[] = 'steps';
        }

        return $missing;
    }
}
