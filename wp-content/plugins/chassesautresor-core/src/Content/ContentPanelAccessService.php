<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate access to a content edition panel.
 */
class ContentPanelAccessService
{
    public function canView(
        bool $isAuthenticated,
        bool $isAdministrator,
        bool $isOrganizer,
        bool $canModifyContent,
        string $contentType,
        string $publicationStatus,
        string $validationStatus = '',
        string $systemStatus = ''
    ): bool {
        if (!$isAuthenticated) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        if (!$isOrganizer || !$canModifyContent) {
            return false;
        }

        if (!in_array($publicationStatus, ['publish', 'pending'], true)) {
            return false;
        }

        if ($contentType === 'organisateur' || $contentType === 'indice') {
            return true;
        }

        if ($contentType === 'chasse') {
            return $validationStatus !== 'banni';
        }

        if ($contentType === 'enigme') {
            return $systemStatus !== 'cache_invalide';
        }

        return false;
    }
}
