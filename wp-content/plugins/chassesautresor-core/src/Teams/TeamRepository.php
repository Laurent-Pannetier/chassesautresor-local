<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** MySQL storage. A named lock serializes joins so the cap and the single seat hold. */
final class TeamRepository implements TeamStore
{
    public function __construct(private readonly \wpdb $wpdb)
    {
    }

    public function transaction(callable $callback): mixed
    {
        $lock = $this->wpdb->prefix . 'cat_equipe';
        $got = $this->wpdb->get_var($this->wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock));
        if ((string) $got !== '1') {
            return TeamResult::error('busy');
        }

        $this->wpdb->query('START TRANSACTION');
        try {
            $result = $callback();
            $failed = $result instanceof TeamResult && !$result->success;
            $this->wpdb->query($failed ? 'ROLLBACK' : 'COMMIT');

            return $result;
        } catch (\Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        } finally {
            $this->wpdb->query($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    public function lockUser(int $userId): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "SELECT user_id FROM {$this->places()} WHERE user_id = %d FOR UPDATE",
            $userId
        ));
    }

    public function lockTeam(int $teamId): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "SELECT id FROM {$this->teams()} WHERE id = %d FOR UPDATE",
            $teamId
        ));
    }

    public function activeTeamId(int $userId): int
    {
        $id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT equipe_id FROM {$this->places()} WHERE user_id = %d",
            $userId
        ));

        return is_numeric($id) ? (int) $id : 0;
    }

    public function team(int $teamId): ?array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id, nom, nom_key, image_id, join_code, created_by, created_at, dissolved_at
            FROM {$this->teams()} WHERE id = %d",
            $teamId
        ), ARRAY_A);

        return $this->teamRow(is_array($row) ? $row : null);
    }

    public function activeTeamByCode(string $code): ?array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id, nom, nom_key, image_id, join_code, created_by, created_at, dissolved_at
            FROM {$this->teams()} WHERE join_code = %s AND dissolved_at IS NULL LIMIT 1 FOR UPDATE",
            $code
        ), ARRAY_A);

        return $this->teamRow(is_array($row) ? $row : null);
    }

    public function nameTaken(string $nomKey, int $exceptTeamId): bool
    {
        $id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->teams()}
            WHERE nom_key = %s AND dissolved_at IS NULL AND id <> %d LIMIT 1",
            $nomKey,
            $exceptTeamId
        ));

        return is_numeric($id);
    }

    public function codeTaken(string $code, int $exceptTeamId): bool
    {
        $id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->teams()} WHERE join_code = %s AND id <> %d LIMIT 1",
            $code,
            $exceptTeamId
        ));

        return is_numeric($id);
    }

    public function activeCount(int $teamId): int
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->places()} WHERE equipe_id = %d",
            $teamId
        ));

        return is_numeric($count) ? (int) $count : 0;
    }

    public function membership(int $teamId, int $userId): ?array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id, equipe_id, user_id, statut, excluded_by
            FROM {$this->members()} WHERE equipe_id = %d AND user_id = %d",
            $teamId,
            $userId
        ), ARRAY_A);

        return $this->memberRow(is_array($row) ? $row : null);
    }

    public function insertTeam(string $nom, string $nomKey, ?int $imageId, string $code, int $createdBy): int
    {
        $image = $imageId === null ? 'NULL' : (string) $imageId;
        $inserted = $this->wpdb->query($this->wpdb->prepare(
            "INSERT INTO {$this->teams()}
            (nom, nom_key, image_id, join_code, created_by, created_at)
            VALUES (%s, %s, {$image}, %s, %d, %s)",
            $nom,
            $nomKey,
            $code,
            $createdBy,
            $this->now()
        ));
        $id = (int) $this->wpdb->insert_id;
        if ($inserted === false || $id <= 0) {
            throw new \RuntimeException('team insert failed');
        }

        return $id;
    }

    public function insertActiveMember(int $teamId, int $userId): void
    {
        $inserted = $this->wpdb->insert(
            $this->members(),
            [
                'equipe_id' => $teamId,
                'user_id' => $userId,
                'statut' => 'actif',
                'joined_at' => $this->now(),
            ],
            ['%d', '%d', '%s', '%s']
        );
        if ($inserted === false) {
            throw new \RuntimeException('member insert failed');
        }
    }

    public function reactivateMember(int $membershipId): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->members()}
            SET statut = 'actif', joined_at = %s, left_at = NULL, excluded_by = NULL
            WHERE id = %d",
            $this->now(),
            $membershipId
        ));
    }

    public function markLeft(int $membershipId): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->members()} SET statut = 'parti', left_at = %s WHERE id = %d",
            $this->now(),
            $membershipId
        ));
    }

    public function markExcluded(int $membershipId, int $actorId): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->members()}
            SET statut = 'exclu', left_at = %s, excluded_by = %d
            WHERE id = %d",
            $this->now(),
            $actorId,
            $membershipId
        ));
    }

    public function insertPlace(int $userId, int $teamId): bool
    {
        $this->wpdb->suppress_errors(true);
        $inserted = $this->wpdb->query($this->wpdb->prepare(
            "INSERT INTO {$this->places()} (user_id, equipe_id) VALUES (%d, %d)",
            $userId,
            $teamId
        ));
        $this->wpdb->suppress_errors(false);

        return $inserted !== false;
    }

    public function releasePlace(int $userId): void
    {
        $this->wpdb->delete($this->places(), ['user_id' => $userId], ['%d']);
    }

    public function updateProfile(
        int $teamId,
        string $nom,
        string $nomKey,
        ?int $imageId,
        bool $replaceImage
    ): void {
        if ($replaceImage) {
            $image = $imageId === null ? 'NULL' : (string) $imageId;
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->teams()} SET nom = %s, nom_key = %s, image_id = {$image} WHERE id = %d",
                $nom,
                $nomKey,
                $teamId
            ));

            return;
        }

        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->teams()} SET nom = %s, nom_key = %s WHERE id = %d",
            $nom,
            $nomKey,
            $teamId
        ));
    }

    public function updateCode(int $teamId, string $code): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->teams()} SET join_code = %s WHERE id = %d",
            $code,
            $teamId
        ));
    }

    public function dissolve(int $teamId, string $freedKey): void
    {
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->teams()} SET dissolved_at = %s, nom_key = %s WHERE id = %d",
            $this->now(),
            $freedKey,
            $teamId
        ));
    }

    public function activeTeams(): array
    {
        $rows = $this->wpdb->get_results(
            "SELECT id, nom, nom_key, image_id, join_code, created_by, created_at, dissolved_at
            FROM {$this->teams()} WHERE dissolved_at IS NULL ORDER BY nom ASC",
            ARRAY_A
        );
        if (!is_array($rows)) {
            return [];
        }

        $teams = [];
        foreach ($rows as $row) {
            $team = $this->teamRow(is_array($row) ? $row : null);
            if ($team !== null) {
                $teams[] = $team;
            }
        }

        return $teams;
    }

    public function activeMembers(int $teamId): array
    {
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT id, equipe_id, user_id, statut, excluded_by
            FROM {$this->members()}
            WHERE equipe_id = %d AND statut = 'actif'
            ORDER BY joined_at ASC, id ASC",
            $teamId
        ), ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        $members = [];
        foreach ($rows as $row) {
            $member = $this->memberRow(is_array($row) ? $row : null);
            if ($member !== null) {
                $members[] = $member;
            }
        }

        return $members;
    }

    /** @param array<string, mixed>|null $row */
    private function teamRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $image = $row['image_id'] ?? null;
        $dissolved = $row['dissolved_at'] ?? null;

        return [
            'id' => (int) $row['id'],
            'nom' => (string) $row['nom'],
            'nom_key' => (string) $row['nom_key'],
            'image_id' => is_numeric($image) && (int) $image > 0 ? (int) $image : null,
            'join_code' => (string) $row['join_code'],
            'created_by' => (int) $row['created_by'],
            'created_at' => (string) $row['created_at'],
            'dissolved_at' => is_string($dissolved) && $dissolved !== '' ? $dissolved : null,
        ];
    }

    /** @param array<string, mixed>|null $row */
    private function memberRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $excludedBy = $row['excluded_by'] ?? null;

        return [
            'id' => (int) $row['id'],
            'equipe_id' => (int) $row['equipe_id'],
            'user_id' => (int) $row['user_id'],
            'statut' => (string) $row['statut'],
            'excluded_by' => is_numeric($excludedBy) && (int) $excludedBy > 0 ? (int) $excludedBy : null,
        ];
    }

    private function teams(): string
    {
        return $this->wpdb->prefix . 'equipes';
    }

    private function members(): string
    {
        return $this->wpdb->prefix . 'equipe_membres';
    }

    private function places(): string
    {
        return $this->wpdb->prefix . 'equipe_places';
    }

    private function now(): string
    {
        if (function_exists('current_time')) {
            return (string) current_time('mysql');
        }

        return gmdate('Y-m-d H:i:s');
    }
}
