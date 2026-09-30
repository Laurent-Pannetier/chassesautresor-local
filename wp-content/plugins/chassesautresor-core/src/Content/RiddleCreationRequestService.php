<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate requests made to the public riddle creation route.
 */
class RiddleCreationRequestService {
    public function getRequestError(bool $hasValidNonce, bool $isLoggedIn, bool $hasValidHunt): ?string {
        if (!$hasValidNonce) {
            return 'invalid_nonce';
        }

        if (!$isLoggedIn) {
            return 'authentication_required';
        }

        if (!$hasValidHunt) {
            return 'invalid_hunt';
        }

        return null;
    }
}
