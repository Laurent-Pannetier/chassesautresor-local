<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate actions on content attached to a hunt or riddle, such as hints and solutions.
 */
class RelatedContentActionService
{
    public function canPerform(
        bool $isAuthenticated,
        string $action,
        string $objectType,
        bool $isValidObject,
        bool $isAdministrator,
        bool $isAssociatedOrganizer,
        string $publicationStatus = '',
        string $validationStatus = '',
        bool $hasHunt = false,
        bool $isHuntActionAllowed = false
    ): bool {
        if (!$isAuthenticated || !$isValidObject) {
            return false;
        }

        if (!in_array($action, ['create', 'edit', 'delete'], true)) {
            return false;
        }

        $canManage = $isAdministrator || $isAssociatedOrganizer;
        if (!$canManage) {
            return false;
        }

        if ($objectType === 'chasse') {
            if ($action === 'delete') {
                return true;
            }

            if (!in_array($publicationStatus, ['publish', 'pending'], true)) {
                return false;
            }

            return $action === 'edit'
                || in_array($validationStatus, ['valide', 'correction', 'creation'], true);
        }

        if ($objectType === 'enigme') {
            if (!$hasHunt) {
                return false;
            }

            if ($action === 'delete') {
                return true;
            }

            return in_array($publicationStatus, ['publish', 'pending'], true)
                && $isHuntActionAllowed;
        }

        return false;
    }
}
