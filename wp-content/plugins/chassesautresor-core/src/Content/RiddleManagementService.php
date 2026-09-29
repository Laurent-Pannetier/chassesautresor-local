<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can add, edit, or remove riddles from a hunt.
 */
class RiddleManagementService
{
    public const MAX_RIDDLES_PER_HUNT = 40;

    public function canAdd(
        bool $isHunt,
        bool $isAuthenticated,
        bool $isOrganizer,
        string $huntPublicationStatus,
        string $huntStatus,
        string $validationStatus,
        bool $isAssociatedOrganizer,
        int $riddleCount
    ): bool {
        return $isHunt
            && $isAuthenticated
            && $isOrganizer
            && $huntPublicationStatus !== 'publish'
            && $huntStatus === 'revision'
            && in_array($validationStatus, ['creation', 'correction'], true)
            && $isAssociatedOrganizer
            && $riddleCount < self::MAX_RIDDLES_PER_HUNT;
    }

    public function canDelete(
        bool $isRiddle,
        bool $isAuthenticated,
        bool $isOrganizer,
        bool $hasHunt,
        string $huntStatus,
        string $validationStatus,
        bool $isAssociatedOrganizer
    ): bool {
        return $isRiddle
            && $isAuthenticated
            && $isOrganizer
            && $hasHunt
            && $huntStatus === 'revision'
            && in_array($validationStatus, ['creation', 'correction'], true)
            && $isAssociatedOrganizer;
    }

    public function canEdit(
        bool $isRiddle,
        bool $isAdministrator,
        bool $hasHunt,
        bool $isAssociatedOrganizer
    ): bool {
        if (!$isRiddle) {
            return false;
        }

        return $isAdministrator || ($hasHunt && $isAssociatedOrganizer);
    }
}
