<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a riddle configuration contains all required content.
 */
class RiddleCompletionService
{
    public function isComplete(
        bool $hasValidTitle,
        int $imageId,
        int $placeholderImageId,
        string $validationMode,
        bool $hasAnswers,
        string $accessCondition,
        bool $hasPrerequisites
    ): bool {
        $hasValidImage = $imageId > 0 && $imageId !== $placeholderImageId;
        $hasValidAnswerConfiguration = $validationMode !== 'automatique' || $hasAnswers;
        $hasValidAccessConfiguration = $accessCondition !== 'pre_requis' || $hasPrerequisites;

        return $hasValidTitle
            && $hasValidImage
            && $hasValidAnswerConfiguration
            && $hasValidAccessConfiguration;
    }
}
