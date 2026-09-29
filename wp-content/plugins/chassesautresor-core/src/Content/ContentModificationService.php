<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can modify a content item they own or manage.
 */
class ContentModificationService
{
    public function canModify(
        bool $hasValidContext,
        bool $isAdministrator,
        string $contentType,
        bool $isAssociatedUser = false,
        bool $isAuthor = false,
        bool $hasOwner = false,
        bool $canModifyOwner = false
    ): bool {
        if (!$hasValidContext) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        if ($contentType === 'organisateur') {
            return $isAssociatedUser || $isAuthor;
        }

        if (in_array($contentType, ['chasse', 'enigme', 'indice'], true)) {
            return $hasOwner && $canModifyOwner;
        }

        return false;
    }
}
