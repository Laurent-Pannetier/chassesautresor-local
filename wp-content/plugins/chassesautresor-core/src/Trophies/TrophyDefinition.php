<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Immutable configuration loaded from one trophy post. */
final class TrophyDefinition
{
    /** @param array<string, mixed> $triggerConfig */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly int $imageId,
        public readonly string $scope,
        public readonly string $triggerType,
        public readonly array $triggerConfig,
        public readonly bool $active,
        public readonly bool $notificationEnabled,
        public readonly string $notificationTitle,
        public readonly string $notificationBody,
        public readonly int $notificationPriority,
        public readonly string $notificationChannel
    ) {
    }
}
