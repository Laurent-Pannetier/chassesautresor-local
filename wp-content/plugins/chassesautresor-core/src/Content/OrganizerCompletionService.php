<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether an organizer profile contains all required content.
 */
class OrganizerCompletionService
{
    public function isComplete(bool $hasValidTitle, bool $hasLogo, string $description): bool
    {
        return $this->missingKeys($hasValidTitle, $hasLogo, $description) === [];
    }

    /**
     * Stable keys for the organizer fields that still block activation.
     *
     * @return string[]
     */
    public function missingKeys(bool $hasValidTitle, bool $hasLogo, string $description): array
    {
        $missing = [];
        if (!$hasValidTitle) {
            $missing[] = 'title';
        }
        if (!$hasLogo) {
            $missing[] = 'logo';
        }
        if (trim($description) === '') {
            $missing[] = 'description';
        }

        return $missing;
    }
}
