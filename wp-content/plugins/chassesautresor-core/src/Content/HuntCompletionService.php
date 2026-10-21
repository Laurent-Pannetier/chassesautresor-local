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
        return $this->missingKeys(
            $hasValidTitle,
            $description,
            $imageId,
            $placeholderImageId,
            $endMode,
            $hasValidatableRiddle
        ) === [];
    }

    /**
     * Stable keys for the hunt fields that still block activation.
     *
     * @return string[]
     */
    public function missingKeys(
        bool $hasValidTitle,
        string $description,
        int $imageId,
        int $placeholderImageId,
        string $endMode,
        bool $hasValidatableRiddle
    ): array {
        $missing = [];
        if (!$hasValidTitle) {
            $missing[] = 'title';
        }
        if (trim($description) === '') {
            $missing[] = 'description';
        }
        if ($imageId <= 0 || $imageId === $placeholderImageId) {
            $missing[] = 'image';
        }
        if ($endMode === 'automatique' && !$hasValidatableRiddle) {
            $missing[] = 'validatable_riddle';
        }

        return $missing;
    }
}
