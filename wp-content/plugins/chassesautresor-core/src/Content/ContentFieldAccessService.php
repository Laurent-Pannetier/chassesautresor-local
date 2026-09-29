<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate access to the editable fields of a content panel.
 */
class ContentFieldAccessService
{
    public function canEdit(
        bool $canViewPanel,
        bool $isAdministrator,
        bool $canModifyContent,
        string $contentType,
        string $publicationStatus,
        string $validationStatus = '',
        string $businessStatus = '',
        string $systemStatus = '',
        bool $hasHunt = false,
        string $huntPublicationStatus = ''
    ): bool {
        if (!$canViewPanel) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        if ($contentType === 'organisateur') {
            return $canModifyContent;
        }

        if ($contentType === 'chasse') {
            return $publicationStatus === 'pending'
                && $businessStatus === 'revision'
                && in_array($validationStatus, ['creation', 'correction'], true);
        }

        if ($contentType === 'enigme') {
            return $hasHunt
                && $huntPublicationStatus === 'pending'
                && $businessStatus === 'revision'
                && in_array($validationStatus, ['creation', 'correction'], true)
                && $systemStatus === 'bloquee_chasse';
        }

        if ($contentType === 'indice') {
            return $publicationStatus === 'pending'
                && in_array($systemStatus, ['desactive', ''], true);
        }

        return false;
    }
}
