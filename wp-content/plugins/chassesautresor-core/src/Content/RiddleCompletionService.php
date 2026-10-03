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
        $hasValidImage = $imageId > 0 && $imageId !== $placeholderImageId;
        $hasValidAnswerConfiguration = $validationMode !== 'automatique' || $hasAnswers;
        $hasValidAccessConfiguration = $accessCondition !== 'pre_requis' || $hasPrerequisites;

        return $hasValidTitle
            && $hasValidImage
            && $hasValidAnswerConfiguration
            && $hasValidAccessConfiguration
            && $hasCompleteSteps;
    }
}
