<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Slows repeated guesses of an invitation code. */
final class TeamJoinThrottle
{
    public const LIMIT = 8;

    /**
     * @param callable(int): int $read
     * @param callable(int, int): void $write
     */
    public function __construct(
        private readonly mixed $read,
        private readonly mixed $write
    ) {
    }

    public function allows(int $userId): bool
    {
        return $this->count($userId) < self::LIMIT;
    }

    public function recordFailure(int $userId): void
    {
        ($this->write)($userId, $this->count($userId) + 1);
    }

    public function reset(int $userId): void
    {
        ($this->write)($userId, 0);
    }

    private function count(int $userId): int
    {
        return max(0, (int) ($this->read)($userId));
    }
}
