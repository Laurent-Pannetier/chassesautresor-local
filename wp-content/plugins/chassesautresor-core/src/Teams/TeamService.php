<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Team rules: one active team per player, a shared code, and a site-wide size cap. */
final class TeamService
{
    /** @var callable(int): string|null */
    private $names;

    public function __construct(
        private readonly TeamStore $store,
        private readonly TeamSettings $settings,
        private readonly TeamInviteCode $codes,
        ?callable $names = null
    ) {
        $this->names = $names;
    }

    public function create(TeamActor $actor, string $rawName, ?int $imageId): TeamResult
    {
        if ($actor->userId <= 0) {
            return TeamResult::error('forbidden');
        }

        $name = $this->normalizeName($rawName);
        if ($name === null) {
            return TeamResult::error('invalid_name');
        }

        $imageId = $this->imageId($imageId);

        return $this->store->transaction(function () use ($actor, $name, $imageId): TeamResult {
            $this->store->lockUser($actor->userId);
            if ($this->store->activeTeamId($actor->userId) > 0) {
                return TeamResult::error('already_member');
            }

            $key = $this->nameKey($name);
            if ($this->store->nameTaken($key, 0)) {
                return TeamResult::error('name_taken');
            }

            $teamId = $this->store->insertTeam($name, $key, $imageId, $this->freshCode(), $actor->userId);
            $this->store->insertActiveMember($teamId, $actor->userId);
            if (!$this->store->insertPlace($actor->userId, $teamId)) {
                return TeamResult::error('already_member');
            }

            return TeamResult::ok('created', $teamId);
        });
    }

    public function join(TeamActor $actor, string $rawCode): TeamResult
    {
        if ($actor->userId <= 0) {
            return TeamResult::error('forbidden');
        }

        $code = $this->codes->normalize($rawCode);
        if (!$this->codes->isValid($code)) {
            return TeamResult::error('invalid_code');
        }

        return $this->store->transaction(function () use ($actor, $code): TeamResult {
            $this->store->lockUser($actor->userId);
            if ($this->store->activeTeamId($actor->userId) > 0) {
                return TeamResult::error('already_member');
            }

            $team = $this->store->activeTeamByCode($code);
            if ($team === null) {
                return TeamResult::error('invalid_code');
            }

            $teamId = (int) $team['id'];
            $this->store->lockTeam($teamId);
            $membership = $this->store->membership($teamId, $actor->userId);
            if ($membership !== null && $membership['statut'] === 'exclu') {
                return TeamResult::error('blocked');
            }
            if ($this->store->activeCount($teamId) >= $this->settings->max()) {
                return TeamResult::error('full');
            }
            if ($membership !== null && $membership['statut'] === 'parti') {
                $this->store->reactivateMember((int) $membership['id']);
            } elseif ($membership === null) {
                $this->store->insertActiveMember($teamId, $actor->userId);
            } else {
                return TeamResult::error('already_member');
            }
            if (!$this->store->insertPlace($actor->userId, $teamId)) {
                return TeamResult::error('already_member');
            }

            return TeamResult::ok('joined', $teamId);
        });
    }

    public function leave(TeamActor $actor): TeamResult
    {
        if ($actor->userId <= 0) {
            return TeamResult::error('forbidden');
        }

        return $this->store->transaction(function () use ($actor): TeamResult {
            $this->store->lockUser($actor->userId);
            $teamId = $this->store->activeTeamId($actor->userId);
            if ($teamId <= 0) {
                return TeamResult::error('not_member');
            }

            $this->store->lockTeam($teamId);
            $membership = $this->store->membership($teamId, $actor->userId);
            if ($membership === null || $membership['statut'] !== 'actif') {
                return TeamResult::error('not_member');
            }

            $this->store->releasePlace($actor->userId);
            $this->store->markLeft((int) $membership['id']);
            $this->dissolveIfEmpty($teamId);

            return TeamResult::ok('left', $teamId);
        });
    }

    public function exclude(TeamActor $actor, int $teamId, int $targetId): TeamResult
    {
        if (!$actor->manager || $actor->userId <= 0 || $targetId <= 0 || $targetId === $actor->userId) {
            return TeamResult::error('forbidden');
        }

        return $this->store->transaction(function () use ($actor, $teamId, $targetId): TeamResult {
            $this->store->lockTeam($teamId);
            $team = $this->store->team($teamId);
            if ($team === null || $team['dissolved_at'] !== null) {
                return TeamResult::error('not_member');
            }

            $membership = $this->store->membership($teamId, $targetId);
            if ($membership === null || $membership['statut'] !== 'actif') {
                return TeamResult::error('not_member');
            }

            $this->store->releasePlace($targetId);
            $this->store->markExcluded((int) $membership['id'], $actor->userId);
            $this->dissolveIfEmpty($teamId);

            return TeamResult::ok('excluded', $teamId);
        });
    }

    public function updateProfile(
        TeamActor $actor,
        int $teamId,
        string $rawName,
        ?int $imageId,
        bool $replaceImage
    ): TeamResult {
        $name = $this->normalizeName($rawName);
        if ($name === null) {
            return TeamResult::error('invalid_name');
        }

        $imageId = $this->imageId($imageId);

        return $this->store->transaction(function () use ($actor, $teamId, $name, $imageId, $replaceImage): TeamResult {
            $this->store->lockTeam($teamId);
            $team = $this->store->team($teamId);
            if ($team === null || $team['dissolved_at'] !== null || !$this->canEdit($actor, $team)) {
                return TeamResult::error('forbidden');
            }

            $key = $this->nameKey($name);
            if ($this->store->nameTaken($key, $teamId)) {
                return TeamResult::error('name_taken');
            }

            $this->store->updateProfile($teamId, $name, $key, $imageId, $replaceImage);

            return TeamResult::ok('updated', $teamId);
        });
    }

    public function regenerate(TeamActor $actor, int $teamId): TeamResult
    {
        return $this->store->transaction(function () use ($actor, $teamId): TeamResult {
            $this->store->lockTeam($teamId);
            $team = $this->store->team($teamId);
            if ($team === null || $team['dissolved_at'] !== null || !$this->canEdit($actor, $team)) {
                return TeamResult::error('forbidden');
            }

            $this->store->updateCode($teamId, $this->freshCode());

            return TeamResult::ok('regenerated', $teamId);
        });
    }

    /** @return array<string, mixed> */
    public function describe(TeamActor $actor): array
    {
        $ownId = $actor->userId > 0 ? $this->store->activeTeamId($actor->userId) : 0;
        $own = $ownId > 0 ? $this->present($ownId, $actor) : null;
        $directory = [];
        if ($actor->manager) {
            foreach ($this->store->activeTeams() as $team) {
                $presented = $this->present((int) $team['id'], $actor);
                if ($presented !== null) {
                    $directory[] = $presented;
                }
            }
        }

        return [
            'max' => $this->settings->max(),
            'manager' => $actor->manager,
            'team' => $own,
            'directory' => $directory,
        ];
    }

    /** @param array<string, mixed> $team */
    private function canEdit(TeamActor $actor, array $team): bool
    {
        if ($actor->manager) {
            return true;
        }
        if ((int) $team['created_by'] !== $actor->userId) {
            return false;
        }

        $membership = $this->store->membership((int) $team['id'], $actor->userId);

        return $membership !== null && $membership['statut'] === 'actif';
    }

    /** @return array<string, mixed>|null */
    private function present(int $teamId, TeamActor $actor): ?array
    {
        $team = $this->store->team($teamId);
        if ($team === null || $team['dissolved_at'] !== null) {
            return null;
        }

        $members = [];
        foreach ($this->store->activeMembers($teamId) as $member) {
            $userId = (int) $member['user_id'];
            $members[] = [
                'id' => $userId,
                'name' => $this->nameOf($userId),
                'creator' => $userId === (int) $team['created_by'],
                'canExclude' => $actor->manager && $userId !== $actor->userId,
            ];
        }

        return [
            'id' => (int) $team['id'],
            'name' => (string) $team['nom'],
            'imageId' => $team['image_id'],
            'code' => (string) $team['join_code'],
            'creatorId' => (int) $team['created_by'],
            'canEdit' => $this->canEdit($actor, $team),
            'count' => $this->store->activeCount($teamId),
            'max' => $this->settings->max(),
            'members' => $members,
        ];
    }

    private function dissolveIfEmpty(int $teamId): void
    {
        if ($this->store->activeCount($teamId) > 0) {
            return;
        }

        $this->store->dissolve($teamId, '~' . $teamId);
    }

    private function freshCode(): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->codes->generate();
            if (!$this->store->codeTaken($code, 0)) {
                return $code;
            }
        }

        throw new \RuntimeException('invite code exhausted');
    }

    private function normalizeName(string $raw): ?string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($raw)) ?? '';
        $length = mb_strlen($collapsed);
        if ($length < 2 || $length > 40) {
            return null;
        }
        if (preg_match('/^[\p{L}\p{N}][\p{L}\p{N} \'’.\-]*$/u', $collapsed) !== 1) {
            return null;
        }

        return $collapsed;
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower($name, 'UTF-8');
    }

    private function imageId(?int $imageId): ?int
    {
        return $imageId !== null && $imageId > 0 ? $imageId : null;
    }

    private function nameOf(int $userId): string
    {
        if ($this->names !== null) {
            return trim((string) ($this->names)($userId));
        }
        if (function_exists('get_userdata')) {
            $user = get_userdata($userId);
            if ($user instanceof \WP_User) {
                $label = (string) ($user->display_name !== '' ? $user->display_name : $user->user_login);
                if ($label !== '') {
                    return $label;
                }
            }
        }

        return '';
    }
}
