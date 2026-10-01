<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

class HuntNavigationAccessService {
    public function canView(int $userId, bool $administrator, bool $organizer, bool $engaged): bool {
        return $administrator || $organizer || ($userId > 0 && $engaged);
    }
}
