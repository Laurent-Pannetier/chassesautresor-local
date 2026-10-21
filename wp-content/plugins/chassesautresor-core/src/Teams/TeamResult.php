<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Outcome of a team command. The code is a stable machine key, not a sentence. */
final class TeamResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $code,
        public readonly int $teamId = 0
    ) {
    }

    public static function ok(string $code, int $teamId = 0): self
    {
        return new self(true, $code, $teamId);
    }

    public static function error(string $code): self
    {
        return new self(false, $code, 0);
    }
}
