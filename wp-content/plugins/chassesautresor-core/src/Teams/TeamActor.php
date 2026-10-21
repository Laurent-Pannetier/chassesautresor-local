<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Person performing a team action. */
final class TeamActor
{
    public function __construct(
        public readonly int $userId,
        public readonly bool $manager
    ) {
    }
}
