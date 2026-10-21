<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamInviteCode;
use ChassesAuTresor\Core\Teams\TeamResult;
use ChassesAuTresor\Core\Teams\TeamStore;

/** In-memory team store used to exercise the rules without MySQL. */
final class MemoryTeamStore implements TeamStore
{
    /** @var array<int, array<string, mixed>> */
    public array $teams = [];

    /** @var array<int, array<string, mixed>> */
    public array $members = [];

    /** @var array<int, int> */
    public array $places = [];

    private int $teamSequence = 0;

    private int $memberSequence = 0;

    public function transaction(callable $callback): mixed
    {
        $snapshot = [
            $this->teams,
            $this->members,
            $this->places,
            $this->teamSequence,
            $this->memberSequence,
        ];
        try {
            $result = $callback();
            if ($result instanceof TeamResult && !$result->success) {
                $this->restore($snapshot);
            }

            return $result;
        } catch (Throwable $error) {
            $this->restore($snapshot);
            throw $error;
        }
    }

    public function lockUser(int $userId): void
    {
    }

    public function lockTeam(int $teamId): void
    {
    }

    public function activeTeamId(int $userId): int
    {
        return $this->places[$userId] ?? 0;
    }

    public function team(int $teamId): ?array
    {
        return $this->teams[$teamId] ?? null;
    }

    public function activeTeamByCode(string $code): ?array
    {
        foreach ($this->teams as $team) {
            if ($team['join_code'] === $code && $team['dissolved_at'] === null) {
                return $team;
            }
        }

        return null;
    }

    public function nameTaken(string $nomKey, int $exceptTeamId): bool
    {
        foreach ($this->teams as $team) {
            if ((int) $team['id'] === $exceptTeamId || $team['dissolved_at'] !== null) {
                continue;
            }
            if ($team['nom_key'] === $nomKey) {
                return true;
            }
        }

        return false;
    }

    public function codeTaken(string $code, int $exceptTeamId): bool
    {
        foreach ($this->teams as $team) {
            if ((int) $team['id'] === $exceptTeamId) {
                continue;
            }
            if ($team['join_code'] === $code) {
                return true;
            }
        }

        return false;
    }

    public function activeCount(int $teamId): int
    {
        $count = 0;
        foreach ($this->places as $equipeId) {
            if ($equipeId === $teamId) {
                $count++;
            }
        }

        return $count;
    }

    public function membership(int $teamId, int $userId): ?array
    {
        foreach ($this->members as $member) {
            if ((int) $member['equipe_id'] === $teamId && (int) $member['user_id'] === $userId) {
                return $member;
            }
        }

        return null;
    }

    public function insertTeam(string $nom, string $nomKey, ?int $imageId, string $code, int $createdBy): int
    {
        $id = ++$this->teamSequence;
        $this->teams[$id] = [
            'id' => $id,
            'nom' => $nom,
            'nom_key' => $nomKey,
            'image_id' => $imageId,
            'join_code' => $code,
            'created_by' => $createdBy,
            'created_at' => '2026-01-01 00:00:00',
            'dissolved_at' => null,
        ];

        return $id;
    }

    public function insertActiveMember(int $teamId, int $userId): void
    {
        $id = ++$this->memberSequence;
        $this->members[$id] = [
            'id' => $id,
            'equipe_id' => $teamId,
            'user_id' => $userId,
            'statut' => 'actif',
            'excluded_by' => null,
        ];
    }

    public function reactivateMember(int $membershipId): void
    {
        $this->members[$membershipId]['statut'] = 'actif';
        $this->members[$membershipId]['excluded_by'] = null;
    }

    public function markLeft(int $membershipId): void
    {
        $this->members[$membershipId]['statut'] = 'parti';
    }

    public function markExcluded(int $membershipId, int $actorId): void
    {
        $this->members[$membershipId]['statut'] = 'exclu';
        $this->members[$membershipId]['excluded_by'] = $actorId;
    }

    public function insertPlace(int $userId, int $teamId): bool
    {
        if (isset($this->places[$userId])) {
            return false;
        }
        $this->places[$userId] = $teamId;

        return true;
    }

    public function releasePlace(int $userId): void
    {
        unset($this->places[$userId]);
    }

    public function updateProfile(int $teamId, string $nom, string $nomKey, ?int $imageId, bool $replaceImage): void
    {
        $this->teams[$teamId]['nom'] = $nom;
        $this->teams[$teamId]['nom_key'] = $nomKey;
        if ($replaceImage) {
            $this->teams[$teamId]['image_id'] = $imageId;
        }
    }

    public function updateCode(int $teamId, string $code): void
    {
        $this->teams[$teamId]['join_code'] = $code;
    }

    public function dissolve(int $teamId, string $freedKey): void
    {
        $this->teams[$teamId]['dissolved_at'] = '2026-01-02 00:00:00';
        $this->teams[$teamId]['nom_key'] = $freedKey;
    }

    public function activeTeams(): array
    {
        $teams = [];
        foreach ($this->teams as $team) {
            if ($team['dissolved_at'] === null) {
                $teams[] = $team;
            }
        }
        usort(
            $teams,
            static fn (array $left, array $right): int => strcmp((string) $left['nom'], (string) $right['nom'])
        );

        return $teams;
    }

    public function activeMembers(int $teamId): array
    {
        $members = [];
        foreach ($this->members as $member) {
            if ((int) $member['equipe_id'] === $teamId && $member['statut'] === 'actif') {
                $members[] = $member;
            }
        }

        return $members;
    }

    /** @param array{0: array, 1: array, 2: array, 3: int, 4: int} $snapshot */
    private function restore(array $snapshot): void
    {
        [
            $this->teams,
            $this->members,
            $this->places,
            $this->teamSequence,
            $this->memberSequence,
        ] = $snapshot;
    }
}

/** Invitation codes issued in a fixed order. */
final class QueueInviteCode extends TeamInviteCode
{
    /** @param array<int, string> $codes */
    public function __construct(private array $codes)
    {
    }

    public function generate(): string
    {
        $next = array_shift($this->codes);
        if (!is_string($next) || !$this->isValid($next)) {
            return parent::generate();
        }

        return $next;
    }
}
