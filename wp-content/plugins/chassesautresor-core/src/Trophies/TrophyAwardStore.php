<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Persistence contract for unique user and team trophy awards. */
interface TrophyAwardStore
{
    public function transaction(callable $callback): mixed;

    /** @param array<string, mixed> $context */
    public function insert(
        int $trophyId,
        string $recipientType,
        int $recipientId,
        int $actorUserId,
        string $eventKey,
        array $context
    ): int;

    /** @return array<int, array<string, mixed>> */
    public function listForRecipients(int $userId, int $teamId = 0): array;

    public function countForRecipients(int $userId, int $teamId = 0): int;
}
