<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

/** Decide who may create or remove statistics fixture players. */
final class StatisticsFixtureAccess
{
    public function canManage(bool $demoMode, bool $administrator, bool $organizerOfHunt, int $huntId): bool
    {
        return $demoMode && $huntId > 0 && ($administrator || $organizerOfHunt);
    }
}
