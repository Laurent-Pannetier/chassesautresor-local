<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate the business context required to create a riddle.
 */
class RiddleCreationService {
    public function getCreationError(bool $hasValidHunt, bool $hasValidUser, bool $hasOrganizer): ?string {
        if (!$hasValidHunt) {
            return 'invalid_hunt';
        }

        if (!$hasValidUser) {
            return 'invalid_user';
        }

        if (!$hasOrganizer) {
            return 'missing_organizer';
        }

        return null;
    }
}
