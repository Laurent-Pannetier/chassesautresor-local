<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether an organizer can request validation of a hunt.
 */
class HuntValidationService
{
    /**
     * @param array<int, array{system_status:string,is_complete:bool}> $riddles
     */
    public function canRequestValidation(
        bool $isOrganizer,
        bool $isAssociatedOrganizer,
        bool $isOrganizerComplete,
        bool $isHuntComplete,
        string $publicationStatus,
        string $validationStatus,
        string $functionalStatus,
        array $riddles
    ): bool {
        if (!$isOrganizer || !$isAssociatedOrganizer || !$isOrganizerComplete || !$isHuntComplete) {
            return false;
        }

        if ($publicationStatus !== 'pending' || $functionalStatus !== 'revision') {
            return false;
        }

        if (!in_array($validationStatus, ['creation', 'correction'], true) || $riddles === []) {
            return false;
        }

        foreach ($riddles as $riddle) {
            if ($riddle['system_status'] !== 'bloquee_chasse' || !$riddle['is_complete']) {
                return false;
            }
        }

        return true;
    }
}
