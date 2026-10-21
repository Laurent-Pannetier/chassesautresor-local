<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/**
 * Persistence for teams, memberships and the single active seat of a player.
 *
 * A dissolved team keeps its row. Future team trophies can still point at its id.
 */
interface TeamStore
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    public function lockUser(int $userId): void;

    public function lockTeam(int $teamId): void;

    public function activeTeamId(int $userId): int;

    /** @return array<string, mixed>|null */
    public function team(int $teamId): ?array;

    /** @return array<string, mixed>|null */
    public function activeTeamByCode(string $code): ?array;

    public function nameTaken(string $nomKey, int $exceptTeamId): bool;

    public function codeTaken(string $code, int $exceptTeamId): bool;

    public function activeCount(int $teamId): int;

    /** @return array<string, mixed>|null */
    public function membership(int $teamId, int $userId): ?array;

    public function insertTeam(string $nom, string $nomKey, ?int $imageId, string $code, int $createdBy): int;

    public function insertActiveMember(int $teamId, int $userId): void;

    public function reactivateMember(int $membershipId): void;

    public function markLeft(int $membershipId): void;

    public function markExcluded(int $membershipId, int $actorId): void;

    public function insertPlace(int $userId, int $teamId): bool;

    public function releasePlace(int $userId): void;

    public function updateProfile(int $teamId, string $nom, string $nomKey, ?int $imageId, bool $replaceImage): void;

    public function updateCode(int $teamId, string $code): void;

    public function dissolve(int $teamId, string $freedKey): void;

    /** @return array<int, array<string, mixed>> */
    public function activeTeams(): array;

    /** @return array<int, array<string, mixed>> */
    public function activeMembers(int $teamId): array;
}
