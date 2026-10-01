<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

final class HuntModerationOrganizerService
{
    public function promote(
        int $organizerId,
        array $userIds,
        callable $getStatus,
        callable $publishOrganizer,
        callable $promoteUser
    ): void {
        if ($organizerId <= 0) {
            return;
        }
        if ($getStatus($organizerId) === 'pending') {
            $publishOrganizer($organizerId);
        }
        if ($userIds !== []) {
            $promoteUser((int) reset($userIds));
        }
    }
}
