<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class RiddleSidebarRequestPolicy {
    public function winners(bool $nonceValid, int $riddleId, string $postType, string $mode): ?string {
        if (!$nonceValid) {
            return 'invalid_nonce';
        }
        if ($riddleId <= 0 || $postType !== 'enigme') {
            return 'missing_enigme';
        }

        return $mode === 'aucune' ? 'disabled' : null;
    }

    public function progression(
        bool $loggedIn,
        bool $nonceValid,
        int $huntId,
        string $huntType,
        int $riddleId,
        string $riddleType,
        int $relatedHuntId
    ): ?string {
        if (!$loggedIn) {
            return 'non_connecte';
        }
        if (!$nonceValid) {
            return 'invalid_nonce';
        }
        if ($huntId <= 0 || $riddleId <= 0 || $huntType !== 'chasse' || $riddleType !== 'enigme') {
            return 'missing_chasse';
        }

        return $huntId === $relatedHuntId ? null : 'missing_chasse';
    }
}
