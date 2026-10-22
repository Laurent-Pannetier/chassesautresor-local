<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Seed and remove reversible statistics activity for one hunt.
 *
 * Fixture players are ordinary rows plus a user meta marker. Removal only
 * deletes those marked accounts and their activity.
 */
final class StatisticsFixtureService
{
    public const PLAYER_COUNT = 48;
    public const TEAM_COUNT = 8;
    public const LOGIN_PREFIX = 'fixture-stats-';
    public const META_KEY = '_cat_stats_fixture';
    public const HUNT_META = '_cat_stats_fixture_hunt';

    private object $database;
    /** @var callable():int[] */
    private $findUserIds;
    /** @var callable(int):int */
    private $createUser;
    /** @var callable(int):bool */
    private $deleteUser;
    /** @var callable():int[] */
    private $findHuntIds;
    /** @var callable():DateTimeInterface */
    private $now;
    /** @var callable(int,int[]):void */
    private $clearCache;
    /** @var array<string,bool> */
    private array $unlocked = [];
    private int $rows = 0;

    public function __construct(
        object $database,
        callable $findUserIds,
        callable $createUser,
        callable $deleteUser,
        callable $findHuntIds,
        callable $now,
        callable $clearCache
    ) {
        $this->database = $database;
        $this->findUserIds = $findUserIds;
        $this->createUser = $createUser;
        $this->deleteUser = $deleteUser;
        $this->findHuntIds = $findHuntIds;
        $this->now = $now;
        $this->clearCache = $clearCache;
    }

    public static function isFixtureUser(int $userId, ?callable $lookup = null): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $lookup = $lookup ?? static function (int $id): string {
            return (string) get_user_meta($id, self::META_KEY, true);
        };

        return $lookup($userId) === '1';
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array{users:int,rows:int,error:string}
     */
    public function generate(int $huntId, array $catalog): array
    {
        if ($huntId <= 0) {
            return $this->failed('missing_hunt');
        }

        $riddles = $this->riddles($catalog);
        if ($riddles === []) {
            return $this->failed('empty_catalog');
        }

        $purged = $this->purge();
        if ($purged['error'] !== '') {
            return $this->failed($purged['error']);
        }

        $userIds = [];
        for ($number = 1; $number <= self::PLAYER_COUNT; $number++) {
            $userId = (int) ($this->createUser)($number);
            if ($userId <= 0) {
                $this->purge();
                return $this->failed('user_create');
            }
            $userIds[] = $userId;
        }

        $this->rows = 0;
        $this->unlocked = [];
        $now = $this->clock();
        if (!$this->seedTeams($userIds, $now->format('Y-m-d H:i:s'))) {
            $this->purge();
            return $this->failed('insert');
        }
        $huntHints = $this->idList($catalog['hunt_hints'] ?? []);
        foreach ($userIds as $offset => $userId) {
            $profile = $offset % 8;
            $moment = $this->shiftMoment(
                $this->moment($now, intdiv($offset, 8) % 4),
                -3 * ($offset % 8)
            );
            $seeded = $this->seedPlayer($userId, $profile, $moment, $huntId, $riddles, $huntHints);
            if (!$seeded) {
                $this->purge();
                return $this->failed('insert');
            }
        }

        $riddleIds = array_map(static fn (array $riddle): int => $riddle['id'], $riddles);
        ($this->clearCache)($huntId, $riddleIds);

        return ['users' => count($userIds), 'rows' => $this->rows, 'error' => ''];
    }

    /** @return array{users:int,rows:int,error:string} */
    public function purge(): array
    {
        $userIds = $this->idList(($this->findUserIds)());
        $huntIds = $this->idList(($this->findHuntIds)());
        if ($userIds === []) {
            return ['users' => 0, 'rows' => 0, 'error' => ''];
        }

        $deleted = 0;
        foreach ([
            ['equipe_places', 'user_id'],
            ['equipe_membres', 'user_id'],
            ['equipes', 'created_by'],
        ] as [$table, $column]) {
            if (!$this->deleteByColumn($table, $column, $userIds)) {
                return ['users' => 0, 'rows' => $deleted, 'error' => 'delete'];
            }
            $deleted += (int) $this->database->rows_affected;
        }
        foreach ($this->activityTables() as $table) {
            if (!$this->deleteByUsers($table, $userIds)) {
                return ['users' => 0, 'rows' => $deleted, 'error' => 'delete'];
            }
            $deleted += (int) $this->database->rows_affected;
        }

        foreach ($userIds as $userId) {
            if (!($this->deleteUser)($userId)) {
                return ['users' => 0, 'rows' => $deleted, 'error' => 'user_delete'];
            }
        }

        foreach ($huntIds as $huntId) {
            ($this->clearCache)($huntId, []);
        }

        return ['users' => count($userIds), 'rows' => $deleted, 'error' => ''];
    }

    /** @return array{users:int,rows:int,error:string} */
    private function failed(string $error): array
    {
        return ['users' => 0, 'rows' => 0, 'error' => $error];
    }

    /** @param int[] $userIds */
    private function seedTeams(array $userIds, string $moment): bool
    {
        $membersPerTeam = 4;
        for ($teamNumber = 1; $teamNumber <= self::TEAM_COUNT; $teamNumber++) {
            $first = ($teamNumber - 1) * $membersPerTeam;
            $teamUsers = array_slice($userIds, $first, $membersPerTeam);
            if (count($teamUsers) !== $membersPerTeam) {
                return false;
            }

            $teamId = $this->insertWithId('equipes', [
                'nom' => sprintf('Équipe test %d', $teamNumber),
                'nom_key' => sprintf('équipe test %d', $teamNumber),
                'image_id' => null,
                'join_code' => sprintf('TEST%04d', $teamNumber),
                'created_by' => $teamUsers[0],
                'created_at' => $moment,
                'dissolved_at' => null,
            ]);
            if ($teamId <= 0) {
                return false;
            }

            foreach ($teamUsers as $userId) {
                if (!$this->insert('equipe_membres', [
                    'equipe_id' => $teamId,
                    'user_id' => $userId,
                    'statut' => 'actif',
                    'joined_at' => $moment,
                    'left_at' => null,
                    'excluded_by' => null,
                ]) || !$this->insert('equipe_places', [
                    'user_id' => $userId,
                    'equipe_id' => $teamId,
                ])) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @return string[] */
    private function activityTables(): array
    {
        return [
            'chasse_winners',
            'engagements',
            'enigme_statuts_utilisateur',
            'enigme_tentatives',
            'user_points',
            'indices_deblocages',
            'enigme_etapes_progression',
            'enigme_delais_soumission',
        ];
    }

    /**
     * @param array<int,array{id:int,validation:string,steps:int[],hints:int[]}> $riddles
     * @param int[] $huntHints
     */
    private function seedPlayer(
        int $userId,
        int $profile,
        string $moment,
        int $huntId,
        array $riddles,
        array $huntHints
    ): bool {
        if (!$this->insert('engagements', [
            'user_id' => $userId,
            'enigme_id' => null,
            'chasse_id' => $huntId,
            'date_engagement' => $moment,
        ])) {
            return false;
        }

        $firstRiddleId = (int) ($riddles[0]['id'] ?? 0);
        if ($profile >= 5) {
            foreach ($huntHints as $hintId) {
                if (!$this->unlockHint($userId, $hintId, $huntId, $firstRiddleId, $moment)) {
                    return false;
                }
            }
        }

        $sequence = 0;
        foreach (array_values($riddles) as $riddleIndex => $riddle) {
            if (!$this->seedRiddle(
                $userId,
                $profile,
                $moment,
                $huntId,
                $riddle,
                $riddleIndex,
                $sequence
            )) {
                return false;
            }
        }

        if ($profile !== 7) {
            return true;
        }

        return $this->insert('chasse_winners', [
            'user_id' => $userId,
            'chasse_id' => $huntId,
            'date_win' => $moment,
        ]);
    }

    /**
     * @param array{id:int,validation:string,steps:int[],hints:int[]} $riddle
     */
    private function seedRiddle(
        int $userId,
        int $profile,
        string $moment,
        int $huntId,
        array $riddle,
        int $riddleIndex,
        int &$sequence
    ): bool {
        if ($profile < 1) {
            return true;
        }

        $riddleId = $riddle['id'];
        $engagedAt = $this->shiftMoment($moment, -(45 + ($profile * 7) + ($riddleIndex * 5)));
        if (!$this->insert('engagements', [
            'user_id' => $userId,
            'enigme_id' => $riddleId,
            'date_engagement' => $engagedAt,
        ])) {
            return false;
        }

        $steps = $riddle['steps'];
        $reached = $this->reachedStepCount($profile, count($steps));
        for ($index = 0; $index < $reached; $index++) {
            $stepId = $steps[$index];
            $stepMoment = $this->shiftMoment($engagedAt, 5 * ($index + 1));
            $progressUid = $this->uid($userId, $riddleId, $stepId, $sequence);
            $sequence++;
            if (!$this->insert('enigme_etapes_progression', [
                'user_id' => $userId,
                'enigme_id' => $riddleId,
                'etape_id' => $stepId,
                'statut' => 'trouvee',
                'date_decouverte' => $stepMoment,
                'tentative_uid' => $progressUid,
                'created_at' => $stepMoment,
                'updated_at' => $stepMoment,
            ])) {
                return false;
            }

            $results = $profile >= 4 && $index === 0 ? ['faux', 'bon'] : ['bon'];
            foreach (array_values($results) as $resultIndex => $result) {
                $attemptMoment = $this->shiftMoment($stepMoment, $resultIndex);
                $recorded = $this->attempt(
                    $userId,
                    $riddleId,
                    $stepId,
                    $result,
                    0,
                    $attemptMoment,
                    $sequence
                );
                $sequence++;
                if (!$recorded) {
                    return false;
                }
            }
        }

        $finalAttempts = $this->finalAttempts($profile, $riddle['validation']);
        foreach (array_values($finalAttempts) as $attemptIndex => $result) {
            $attemptMoment = $this->shiftMoment($moment, -2 * (count($finalAttempts) - $attemptIndex - 1));
            $recorded = $this->attempt($userId, $riddleId, null, $result, 2, $attemptMoment, $sequence);
            $sequence++;
            if (!$recorded) {
                return false;
            }
        }

        $status = $this->status($profile, $riddle['validation'], $reached, count($steps));
        if ($status !== null && !$this->insert('enigme_statuts_utilisateur', [
            'user_id' => $userId,
            'enigme_id' => $riddleId,
            'statut' => $status,
            'date_mise_a_jour' => $moment,
        ])) {
            return false;
        }

        $hints = [];
        if ($profile >= 4 && isset($riddle['hints'][0])) {
            $hints[] = $riddle['hints'][0];
        }
        if ($profile === 7) {
            $hints = array_merge($hints, $riddle['hints']);
        }
        foreach (array_values(array_unique($hints)) as $hintId) {
            if (!$this->unlockHint($userId, (int) $hintId, $huntId, $riddleId, $moment)) {
                return false;
            }
        }

        return true;
    }

    private function attempt(
        int $userId,
        int $riddleId,
        ?int $stepId,
        string $result,
        int $points,
        string $moment,
        int $sequence
    ): bool {
        return $this->insert('enigme_tentatives', [
            'tentative_uid' => $this->uid($userId, $riddleId, (int) $stepId, $sequence),
            'user_id' => $userId,
            'enigme_id' => $riddleId,
            'etape_id' => $stepId,
            'reponse_saisie' => sprintf('Proposition test %d-%d-%d', $userId, $riddleId, $sequence),
            'resultat' => $result,
            'points_utilises' => $points,
            'date_tentative' => $moment,
            'traitee' => $result === 'attente' ? 0 : 1,
        ]);
    }

    private function unlockHint(int $userId, int $hintId, int $huntId, int $riddleId, string $moment): bool
    {
        if ($hintId <= 0) {
            return true;
        }

        $key = $userId . ':' . $hintId;
        if (isset($this->unlocked[$key])) {
            return true;
        }
        $this->unlocked[$key] = true;

        return $this->insert('indices_deblocages', [
            'user_id' => $userId,
            'indice_id' => $hintId,
            'chasse_id' => $huntId,
            'enigme_id' => $riddleId > 0 ? $riddleId : null,
            'points_depenses' => 5,
            'date_deblocage' => $moment,
        ]);
    }

    private function reachedStepCount(int $profile, int $stepCount): int
    {
        if ($stepCount <= 0 || $profile < 2) {
            return 0;
        }
        if ($profile === 2) {
            return 1;
        }
        if ($profile === 3) {
            return $stepCount > 1 ? $stepCount - 1 : 1;
        }

        return $stepCount;
    }

    /** @return string[] */
    private function finalAttempts(int $profile, string $validation): array
    {
        if ($validation === 'aucune' || $profile < 4) {
            return [];
        }
        if ($profile === 4) {
            return ['faux', 'faux'];
        }
        if ($profile === 5) {
            return ['attente'];
        }
        if ($profile === 6) {
            return ['bon'];
        }

        return ['faux', 'faux', 'faux', 'variante', 'bon'];
    }

    private function status(int $profile, string $validation, int $reached, int $stepCount): ?string
    {
        if ($profile < 1) {
            return null;
        }

        $final = $this->finalAttempts($profile, $validation);
        if (in_array('bon', $final, true)) {
            return 'resolue';
        }
        if (in_array('attente', $final, true)) {
            return 'soumis';
        }
        $finishedVisit = $validation === 'aucune' && $profile >= 6;
        $stepsDone = $stepCount === 0 || $reached === $stepCount;
        if ($finishedVisit && $stepsDone) {
            return 'terminee';
        }

        return 'en_cours';
    }

    private function moment(DateTimeImmutable $now, int $bucket): string
    {
        if ($bucket === 0) {
            return $now->format('Y-m-d H:i:s');
        }
        if ($bucket === 1) {
            $monday = $now->modify('monday this week')->setTime(8, 0, 0);
            if ($monday->format('Y-m-d') === $now->format('Y-m-d')) {
                return $now->format('Y-m-d H:i:s');
            }

            return $monday->format('Y-m-d H:i:s');
        }
        if ($bucket === 2) {
            $first = $now->modify('first day of this month')->setTime(8, 0, 0);
            $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
            if ($first < $weekStart) {
                return $first->format('Y-m-d H:i:s');
            }

            return $now->modify('first day of last month')->setTime(8, 0, 0)->format('Y-m-d H:i:s');
        }

        return $now->modify('first day of this month')->modify('-15 days')->setTime(8, 0, 0)
            ->format('Y-m-d H:i:s');
    }

    private function uid(int $userId, int $riddleId, int $stepId, int $sequence): string
    {
        return 'fixture-' . $userId . '-' . $riddleId . '-' . $stepId . '-' . $sequence;
    }

    /** @param array<string,mixed> $data */
    private function insert(string $table, array $data): bool
    {
        $formats = [];
        foreach ($data as $value) {
            $formats[] = is_int($value) ? '%d' : '%s';
        }

        $inserted = $this->database->insert($this->database->prefix . $table, $data, $formats);
        if ($inserted === false || !empty($this->database->last_error)) {
            return false;
        }

        $this->rows++;
        return true;
    }

    /** @param array<string,mixed> $data */
    private function insertWithId(string $table, array $data): int
    {
        if (!$this->insert($table, $data)) {
            return 0;
        }

        return max(0, (int) ($this->database->insert_id ?? 0));
    }

    /** @param int[] $userIds */
    private function deleteByUsers(string $table, array $userIds): bool
    {
        return $this->deleteByColumn($table, 'user_id', $userIds);
    }

    /** @param int[] $userIds */
    private function deleteByColumn(string $table, string $column, array $userIds): bool
    {
        $placeholders = implode(',', array_fill(0, count($userIds), '%d'));
        $sql = $this->database->prepare(
            "DELETE FROM {$this->database->prefix}{$table} WHERE {$column} IN ({$placeholders})",
            ...$userIds
        );
        $this->database->query($sql);

        return empty($this->database->last_error);
    }

    private function shiftMoment(string $moment, int $minutes): string
    {
        $date = new DateTimeImmutable($moment, new DateTimeZone('Europe/Paris'));

        return $date->modify(($minutes >= 0 ? '+' : '') . $minutes . ' minutes')->format('Y-m-d H:i:s');
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<int,array{id:int,validation:string,steps:int[],hints:int[]}>
     */
    private function riddles(array $catalog): array
    {
        $riddles = [];
        foreach ((array) ($catalog['riddles'] ?? []) as $riddle) {
            if (!is_array($riddle)) {
                continue;
            }
            $id = (int) ($riddle['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $validation = (string) ($riddle['validation'] ?? 'automatique');
            if (!in_array($validation, ['automatique', 'manuelle', 'aucune'], true)) {
                $validation = 'automatique';
            }
            $riddles[] = [
                'id' => $id,
                'validation' => $validation,
                'steps' => $this->stepIds($riddle['steps'] ?? []),
                'hints' => $this->idList($riddle['hints'] ?? []),
            ];
        }

        return $riddles;
    }

    /** @return int[] */
    private function stepIds($steps): array
    {
        $ids = [];
        foreach ((array) $steps as $step) {
            $id = is_array($step) ? (int) ($step['id'] ?? 0) : (int) $step;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @return int[] */
    private function idList($ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', (array) $ids),
            static fn (int $id): bool => $id > 0
        )));
    }

    private function clock(): DateTimeImmutable
    {
        $current = ($this->now)();
        if (!$current instanceof DateTimeInterface) {
            return new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        }
        if ($current instanceof DateTimeImmutable) {
            return $current;
        }

        return new DateTimeImmutable($current->format('Y-m-d H:i:s'), $current->getTimezone());
    }
}
