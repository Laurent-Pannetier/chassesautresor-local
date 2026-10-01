<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class RiddleAttemptAccessPolicy {
    public function canView(
        int $currentUserId,
        int $ownerId,
        bool $administrator,
        bool $organizer
    ): bool {
        return $currentUserId > 0
            && ($currentUserId === $ownerId || $administrator || $organizer);
    }
}
