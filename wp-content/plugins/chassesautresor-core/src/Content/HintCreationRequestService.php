<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate and normalize requests made to the public hint creation route.
 */
class HintCreationRequestService {
    /**
     * @return array{id:int,type:string}|null
     */
    public function resolveTarget(int $huntId, int $riddleId): ?array {
        if ($huntId > 0) {
            return ['id' => $huntId, 'type' => 'chasse'];
        }

        if ($riddleId > 0) {
            return ['id' => $riddleId, 'type' => 'enigme'];
        }

        return null;
    }

    /**
     * @param array{id:int,type:string}|null $target
     */
    public function getRequestError(bool $hasValidNonce, bool $isLoggedIn, ?array $target): ?string {
        if (!$hasValidNonce) {
            return 'invalid_nonce';
        }

        if (!$isLoggedIn) {
            return 'authentication_required';
        }

        if ($target === null) {
            return 'missing_target';
        }

        return null;
    }
}
