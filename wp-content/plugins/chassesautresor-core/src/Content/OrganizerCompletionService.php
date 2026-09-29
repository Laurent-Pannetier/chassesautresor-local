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
        return $hasValidTitle && $hasLogo && trim($description) !== '';
    }
}
