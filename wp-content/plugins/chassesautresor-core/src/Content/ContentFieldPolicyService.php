<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a specific content field can be edited.
 */
class ContentFieldPolicyService
{
    public function canEdit(
        bool $hasValidContext,
        bool $isAdministrator,
        bool $canModifyContent,
        string $contentType,
        string $fieldName,
        bool $canEditAdvancedFields,
        string $publicationStatus,
        bool $hasOrganizerCreationRole,
        int $huntCount,
        int $creationHuntCount
    ): bool {
        if (!$hasValidContext) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        if (!$canModifyContent) {
            return false;
        }

        if ($contentType === 'chasse' && in_array($fieldName, $this->restrictedHuntFields(), true)) {
            return $canEditAdvancedFields;
        }

        if ($contentType === 'indice') {
            return $canEditAdvancedFields;
        }

        if ($contentType === 'organisateur' && $fieldName === 'post_title') {
            return $hasOrganizerCreationRole
                && $publicationStatus === 'pending'
                && ($huntCount === 0 || ($huntCount === 1 && $creationHuntCount === 1));
        }

        if ($contentType === 'enigme' && $fieldName === 'post_title') {
            return $canEditAdvancedFields;
        }

        return true;
    }

    /** @return string[] */
    private function restrictedHuntFields(): array
    {
        return ['post_title', 'caracteristiques.chasse_infos_cout_points'];
    }
}
