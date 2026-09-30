<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate creation and edition requests submitted by solution modals.
 */
class SolutionModalPolicyService {
    public function getCreationError(
        bool $isAuthenticated,
        bool $hasValidTarget,
        bool $hasConsistentTarget,
        bool $isAuthorized,
        bool $hasRequiredContent
    ): ?string {
        if (!$isAuthenticated) {
            return 'non_connecte';
        }
        if (!$hasValidTarget || !$hasConsistentTarget) {
            return 'post_invalide';
        }
        if (!$isAuthorized) {
            return 'acces_refuse';
        }
        if (!$hasRequiredContent) {
            return 'contenu_manquant';
        }

        return null;
    }

    public function getEditionError(
        bool $isAuthenticated,
        bool $hasValidSolution,
        bool $hasValidTarget,
        bool $isAuthorized,
        bool $hasRequiredContent
    ): ?string {
        if (!$isAuthenticated) {
            return 'non_connecte';
        }
        if (!$hasValidSolution) {
            return 'solution_invalide';
        }
        if (!$hasValidTarget) {
            return 'post_invalide';
        }
        if (!$isAuthorized) {
            return 'acces_refuse';
        }
        if (!$hasRequiredContent) {
            return 'contenu_manquant';
        }

        return null;
    }
}
