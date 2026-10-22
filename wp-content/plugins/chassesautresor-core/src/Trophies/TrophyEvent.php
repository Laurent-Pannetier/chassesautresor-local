<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Normalized business event evaluated by trophy trigger providers. */
final class TrophyEvent
{
    /** @param array<string, mixed> $context */
    public function __construct(
        public readonly string $type,
        public readonly int $userId,
        public readonly string $key,
        public readonly array $context = []
    ) {
    }
}
