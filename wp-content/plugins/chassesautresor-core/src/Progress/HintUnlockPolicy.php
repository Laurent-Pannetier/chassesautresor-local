<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class HintUnlockPolicy {
    public function validate(
        bool $loggedIn,
        bool $nonceValid,
        int $hintId,
        string $postType,
        int $cost,
        int $balance
    ): ?string {
        if (!$loggedIn) {
            return 'non_connecte';
        }
        if (!$nonceValid) {
            return 'invalid_nonce';
        }
        if ($hintId <= 0 || $postType !== 'indice') {
            return 'indice_invalide';
        }
        if (max(0, $cost) > $balance) {
            return 'points_insuffisants';
        }

        return null;
    }
}
