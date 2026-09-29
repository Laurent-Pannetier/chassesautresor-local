<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can view a riddle and its protected media.
 */
class RiddleAccessService
{
    public function canView(
        bool $isAdministrator,
        bool $hasAssociatedHunt,
        bool $isHuntFinished,
        string $publicationStatus,
        string $systemStatus,
        string $validationStatus,
        bool $isAssociatedOrganizer,
        bool $isEngagedInHunt,
        bool $isSubscriber
    ): bool {
        if ($isAdministrator) {
            return true;
        }

        if (!$hasAssociatedHunt) {
            return false;
        }

        if ($isHuntFinished && $publicationStatus === 'publish') {
            return true;
        }

        $editableValidationStatuses = ['creation', 'correction', 'en_attente'];
        if ($isEngagedInHunt) {
            if ($isAssociatedOrganizer && in_array($validationStatus, $editableValidationStatuses, true)) {
                return in_array($publicationStatus, ['publish', 'pending'], true);
            }

            return $publicationStatus === 'publish' && $systemStatus === 'accessible';
        }

        if ($isSubscriber) {
            return $publicationStatus === 'publish' && $systemStatus === 'accessible';
        }

        if ($publicationStatus === 'draft' || !$isAssociatedOrganizer) {
            return false;
        }

        if (in_array($validationStatus, $editableValidationStatuses, true)) {
            return in_array($publicationStatus, ['publish', 'pending'], true);
        }

        return $publicationStatus === 'publish'
            && in_array(
                $systemStatus,
                ['accessible', 'bloquee_chasse', 'bloquee_pre_requis', 'bloquee_date'],
                true
            );
    }
}
