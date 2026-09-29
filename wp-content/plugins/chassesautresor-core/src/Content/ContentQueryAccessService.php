<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Select publication statuses visible in singular front-end queries.
 */
class ContentQueryAccessService
{
    /** @return string[] */
    public function getVisibleStatuses(
        bool $isAuthenticated,
        bool $isAdministrator,
        bool $isOrganizer
    ): array {
        if (!$isAuthenticated) {
            return [];
        }

        if ($isAdministrator) {
            return ['publish', 'pending', 'draft'];
        }

        if ($isOrganizer) {
            return ['publish', 'pending'];
        }

        return [];
    }
}
