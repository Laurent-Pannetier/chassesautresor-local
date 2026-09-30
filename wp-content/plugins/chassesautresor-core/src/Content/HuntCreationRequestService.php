<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Apply the access rules of the public hunt creation route.
 */
class HuntCreationRequestService {
    public function getError(
        int $organizerId,
        bool $isAdministrator,
        bool $isOrganizer,
        bool $hasCreationRole,
        bool $organizerHasHunts,
        bool $canManageOptions,
        bool $organizerIsPublished,
        bool $organizerHasPendingHunt
    ): ?string {
        if ($organizerId <= 0) {
            return 'missing_organizer';
        }

        if (!$isAdministrator && !$isOrganizer) {
            if (!$hasCreationRole) {
                return 'access_denied';
            }

            if ($organizerHasHunts) {
                return 'hunt_limit_reached';
            }
        }

        if (!$canManageOptions && $organizerIsPublished && $organizerHasPendingHunt) {
            return 'pending_hunt_exists';
        }

        return null;
    }
}
