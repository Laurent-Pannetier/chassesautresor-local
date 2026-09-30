<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate the common security context for riddle mutations.
 */
class RiddleActionPolicyService {
    public function getError(bool $isAuthenticated, bool $hasValidTarget, bool $isAuthorized): ?string {
        if (!$isAuthenticated) {
            return 'authentication_required';
        }

        if (!$hasValidTarget) {
            return 'invalid_target';
        }

        if (!$isAuthorized) {
            return 'forbidden';
        }

        return null;
    }
}
