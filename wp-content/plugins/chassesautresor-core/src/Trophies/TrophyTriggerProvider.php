<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** One extensible trigger type exposed in the trophy administration form. */
interface TrophyTriggerProvider
{
    public function key(): string;

    public function label(): string;

    /** @return array<int, array<string, mixed>> */
    public function fields(): array;

    /** @param array<string, mixed> $configuration */
    public function matches(array $configuration, TrophyEvent $event): bool;
}
