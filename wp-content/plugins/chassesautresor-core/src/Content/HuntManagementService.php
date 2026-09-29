<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether an organizer can create another hunt.
 */
class HuntManagementService
{
    public function canCreate(
        bool $isAuthenticated,
        bool $isAdministrator,
        bool $canManageOrganizer,
        bool $hasOrganizerRole,
        bool $hasOrganizerCreationRole,
        bool $isOrganizerPublished,
        bool $hasPendingHunt,
        bool $hasExistingHunt
    ): bool {
        if (!$isAuthenticated || $isAdministrator || !$canManageOrganizer) {
            return false;
        }

        if ($hasOrganizerRole) {
            return !$isOrganizerPublished || !$hasPendingHunt;
        }

        if ($hasOrganizerCreationRole) {
            return !$hasExistingHunt;
        }

        return false;
    }
}
