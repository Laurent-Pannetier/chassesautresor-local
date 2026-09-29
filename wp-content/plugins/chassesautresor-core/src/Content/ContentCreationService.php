<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can create a supported content type.
 */
class ContentCreationService
{
    public function canCreate(
        bool $isAuthenticated,
        bool $isAdministrator,
        string $contentType,
        bool $hasOrganizer = false,
        bool $hasOrganizerRole = false,
        bool $hasExistingHunt = false,
        bool $hasValidHunt = false,
        bool $hasSameOrganizer = false,
        string $huntValidationStatus = ''
    ): bool {
        if (!$isAuthenticated) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        if ($contentType === 'organisateur') {
            return !$hasOrganizer;
        }

        if ($contentType === 'chasse') {
            return $hasOrganizer && ($hasOrganizerRole || !$hasExistingHunt);
        }

        if ($contentType === 'enigme') {
            return $hasValidHunt
                && $hasSameOrganizer
                && trim($huntValidationStatus) === 'creation';
        }

        return false;
    }
}
