<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a hunt configuration contains all required content.
 */
class HuntCompletionService
{
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
