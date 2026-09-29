<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate hunt visibility and statistics access.
 */
class HuntAccessService
{
    public function canView(
        string $publicationStatus,
        string $validationStatus,
        bool $isAdministrator,
        bool $isAssociatedOrganizer
    ): bool {
        if ($publicationStatus === 'publish' && $validationStatus === 'valide') {
            return true;
        }

        return $publicationStatus === 'pending'
            && ($isAdministrator || $isAssociatedOrganizer);
    }

    public function canViewStatistics(
        bool $hasValidHunt,
        bool $isAdministrator,
        bool $isAssociatedOrganizer
    ): bool {
        return $hasValidHunt && ($isAdministrator || $isAssociatedOrganizer);
    }
}
