<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Evaluate the lifecycle of an organizer creation request.
 */
class OrganizerRequestService
{
    /**
     * @return array{token:?string,expired:bool,expires_at?:int,clear:bool}
     */
    public function getStatus(
        string $token,
        ?int $requestedAt,
        int $currentTimestamp,
        int $lifetime
    ): array {
        if ($token === '') {
            return [
                'token' => null,
                'expired' => false,
                'clear' => false,
            ];
        }

        if ($requestedAt === null || $requestedAt <= 0) {
            return [
                'token' => null,
                'expired' => false,
                'clear' => true,
            ];
        }

        $expiresAt = $requestedAt + max(0, $lifetime);
        if ($currentTimestamp > $expiresAt) {
            return [
                'token' => null,
                'expired' => true,
                'clear' => true,
            ];
        }

        return [
            'token' => $token,
            'expired' => false,
            'expires_at' => $expiresAt,
            'clear' => false,
        ];
    }
}
