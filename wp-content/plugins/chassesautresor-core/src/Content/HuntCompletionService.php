<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a hunt configuration contains all required content.
 */
class HuntCompletionService
{
    /** @param string[] $validationModes */
    public function hasValidatableRiddle(array $validationModes): bool
    {
        foreach ($validationModes as $validationMode) {
            if ((string) $validationMode !== 'aucune') {
                return true;
            }
        }

        return false;
    }

    public function isComplete(
        bool $hasValidTitle,
        string $description,
        int $imageId,
        int $placeholderImageId,
        string $endMode,
        bool $hasValidatableRiddle
    ): bool {
        if ($endMode === 'automatique' && !$hasValidatableRiddle) {
            return false;
        }

        return $hasValidTitle
            && trim($description) !== ''
            && $imageId > 0
            && $imageId !== $placeholderImageId;
    }
}
