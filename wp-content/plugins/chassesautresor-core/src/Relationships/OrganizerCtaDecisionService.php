<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

final class OrganizerCtaDecisionService
{
    public function decide(
        bool $administrator,
        bool $hasRequestToken,
        bool $creationRole,
        bool $organizerRole,
        int $organizerId,
        bool $hasPendingHunt
    ): string {
        if ($administrator) {
            return 'administrator';
        }
        if ($hasRequestToken) {
            return 'resend_confirmation';
        }
        if ($creationRole && $hasPendingHunt) {
            return 'resend_pending_hunt';
        }
        if ($organizerId > 0 && ($creationRole || $organizerRole) && !$hasPendingHunt) {
            return 'profile';
        }
        if (!$creationRole && !$organizerRole && $organizerId <= 0) {
            return 'apply';
        }

        return 'create';
    }
}
