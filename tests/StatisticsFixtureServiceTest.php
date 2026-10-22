<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\StatisticsFixtureAccess;
use ChassesAuTresor\Core\Admin\StatisticsFixtureHookHandler;
use ChassesAuTresor\Core\Admin\StatisticsFixtureService;
use ChassesAuTresor\Core\Progress\StatisticsPeriodService;
use PHPUnit\Framework\TestCase;

final class StatisticsFixtureServiceTest extends TestCase
{
    public function testItFillsEachRiddleStepHintAndPeriod(): void
    {
        $database = $this->database();
        $users = [];
        $deleted = [];
        $cleared = [];
        $record = static function (int $huntId, array $riddleIds) use (&$cleared): void {
            $cleared[] = [$huntId, $riddleIds];
        };
        $service = $this->service($database, $users, $deleted, $record);
        $result = $service->generate(7, $this->catalog());

        self::assertSame('', $result['error']);
        self::assertSame(StatisticsFixtureService::PLAYER_COUNT, $result['users']);
        self::assertGreaterThan(0, $result['rows']);
        self::assertSame([[7, [10, 11, 12]]], $cleared);
        self::assertCount(48, $this->inserted($database, 'engagements', static function (array $row): bool {
            return (int) ($row['chasse_id'] ?? 0) === 7 && ($row['enigme_id'] ?? null) === null;
        }));
        self::assertCount(42, $this->inserted($database, 'engagements', static function (array $row): bool {
            return (int) ($row['enigme_id'] ?? 0) === 10;
        }));

        $finals = $this->inserted($database, 'enigme_tentatives', static function (array $row): bool {
            return (int) $row['enigme_id'] === 10 && $row['etape_id'] === null;
        });
        $results = array_count_values(array_column($finals, 'resultat'));
        self::assertSame(12, $results['bon']);
        self::assertSame(6, $results['attente']);
        self::assertSame(6, $results['variante']);
        self::assertSame(30, $results['faux']);
        $pending = array_filter($finals, static function (array $row): bool {
            return $row['resultat'] === 'attente';
        });
        self::assertSame([0], array_values(array_unique(array_column($pending, 'traitee'))));
        self::assertSame([2], array_values(array_unique(array_column($finals, 'points_utilises'))));
        self::assertSame([], $this->inserted($database, 'enigme_tentatives', static function (array $row): bool {
            return (int) $row['enigme_id'] === 12;
        }));

        $manual = $this->inserted($database, 'enigme_statuts_utilisateur', static function (array $row): bool {
            return (int) $row['enigme_id'] === 11;
        });
        $manualStatuses = array_count_values(array_column($manual, 'statut'));
        self::assertSame(6, $manualStatuses['soumis']);
        self::assertSame(12, $manualStatuses['resolue']);

        $visit = $this->inserted($database, 'enigme_statuts_utilisateur', static function (array $row): bool {
            return (int) $row['enigme_id'] === 12 && $row['statut'] === 'terminee';
        });
        self::assertCount(12, $visit);

        $step101 = $this->inserted($database, 'enigme_etapes_progression', static function (array $row): bool {
            return (int) $row['etape_id'] === 101;
        });
        $step103 = $this->inserted($database, 'enigme_etapes_progression', static function (array $row): bool {
            return (int) $row['etape_id'] === 103;
        });
        self::assertGreaterThan(count($step103), count($step101));
        self::assertCount(6, $this->inserted($database, 'chasse_winners'));
        self::assertCount(18, $this->inserted($database, 'indices_deblocages', static function (array $row): bool {
            return (int) $row['indice_id'] === 50;
        }));
        self::assertCount(24, $this->inserted($database, 'indices_deblocages', static function (array $row): bool {
            return (int) $row['indice_id'] === 51;
        }));
        self::assertCount(6, $this->inserted($database, 'indices_deblocages', static function (array $row): bool {
            return (int) $row['indice_id'] === 52;
        }));
        $this->assertPeriodsCovered(array_column(
            $this->inserted($database, 'engagements', static function (array $row): bool {
                return ($row['enigme_id'] ?? null) === null;
            }),
            'date_engagement'
        ));
    }

    public function testGeneratingAgainReplacesOnlyFixturePlayers(): void
    {
        $database = $this->database();
        $users = [];
        $deleted = [];
        $service = $this->service($database, $users, $deleted);
        $service->generate(7, $this->catalog());
        $deleted = [];

        $result = $service->generate(7, $this->catalog());

        self::assertSame('', $result['error']);
        self::assertSame(range(1, 48), $deleted);
        self::assertSame(range(1, 48), $users);
    }

    public function testAnEmptyCatalogDoesNotCreatePlayers(): void
    {
        $database = $this->database();
        $users = [];
        $deleted = [];
        $service = $this->service($database, $users, $deleted);

        $result = $service->generate(7, ['riddles' => []]);

        self::assertSame(['users' => 0, 'rows' => 0, 'error' => 'empty_catalog'], $result);
        self::assertSame([], $users);
        self::assertSame([], $database->queries);
    }

    public function testAFailedInsertRemovesThePlayersJustCreated(): void
    {
        $database = $this->database();
        $database->failAttempts = true;
        $users = [];
        $deleted = [];
        $service = $this->service($database, $users, $deleted);

        $result = $service->generate(7, $this->catalog());

        self::assertSame('insert', $result['error']);
        self::assertSame([], $users);
        self::assertSame(range(1, 48), $deleted);
    }

    public function testPurgeDeletesOnlyMarkedPlayers(): void
    {
        $database = $this->database();
        $users = [4, 9];
        $deleted = [];
        $cleared = [];
        $record = static function (int $huntId, array $riddleIds) use (&$cleared): void {
            $cleared[] = [$huntId, $riddleIds];
        };
        $service = $this->service($database, $users, $deleted, $record);

        $result = $service->purge();

        self::assertSame(['users' => 2, 'rows' => 8, 'error' => ''], $result);
        self::assertSame([4, 9], $deleted);
        self::assertSame([[7, []]], $cleared);
        self::assertStringContainsString('4,9', $database->queries[0]);
        self::assertStringNotContainsString('999', $database->queries[0]);
    }

    public function testPurgeWithNobodyIsANoOp(): void
    {
        $database = $this->database();
        $users = [];
        $deleted = [];
        $service = $this->service($database, $users, $deleted);

        self::assertSame(['users' => 0, 'rows' => 0, 'error' => ''], $service->purge());
        self::assertSame([], $database->queries);
    }

    public function testItRecognizesFixtureUsersAndDemoAccess(): void
    {
        $lookup = static function (int $userId): string {
            return $userId === 5 ? '1' : '';
        };

        self::assertTrue(StatisticsFixtureService::isFixtureUser(5, $lookup));
        self::assertFalse(StatisticsFixtureService::isFixtureUser(4, $lookup));
        self::assertFalse(StatisticsFixtureService::isFixtureUser(0, $lookup));

        $access = new StatisticsFixtureAccess();
        self::assertTrue($access->canManage(true, true, false, 7));
        self::assertTrue($access->canManage(true, false, true, 7));
        self::assertFalse($access->canManage(false, true, true, 7));
        self::assertFalse($access->canManage(true, false, false, 7));
        self::assertFalse($access->canManage(true, true, true, 0));
    }

    public function testLeavingDemoModePurgesFixtures(): void
    {
        $calls = 0;
        $purge = static function () use (&$calls): void {
            $calls++;
        };

        StatisticsFixtureHookHandler::purgeIfDemoEnded(['mode' => 'demo'], ['mode' => 'single_hunt'], $purge);
        StatisticsFixtureHookHandler::purgeIfDemoEnded(['mode' => 'demo'], ['mode' => 'demo'], $purge);
        StatisticsFixtureHookHandler::purgeIfDemoEnded(['mode' => 'platform'], ['mode' => 'single_hunt'], $purge);

        self::assertSame(1, $calls);
    }

    /** @param string[] $dates */
    private function assertPeriodsCovered(array $dates): void
    {
        $dates = array_values(array_unique($dates));
        $now = new DateTimeImmutable('2026-10-21 15:00:00', new DateTimeZone('Europe/Paris'));
        $periods = new StatisticsPeriodService();
        [$dayStart, $dayEnd] = $periods->range('jour', $now);
        [$weekStart, $weekEnd] = $periods->range('semaine', $now);
        [$monthStart, $monthEnd] = $periods->range('mois', $now);

        self::assertCount(4, $dates);
        self::assertTrue($this->hasDateIn($dates, $dayStart, $dayEnd));
        self::assertTrue($this->hasDateOutside($dates, $dayStart, $dayEnd, $weekStart, $weekEnd));
        self::assertTrue($this->hasDateOutside($dates, $weekStart, $weekEnd, $monthStart, $monthEnd));
        self::assertTrue($this->hasDateBefore($dates, $monthStart));
    }

    /** @param string[] $dates */
    private function hasDateIn(array $dates, ?string $start, ?string $end): bool
    {
        foreach ($dates as $date) {
            if ($start !== null && $end !== null && $date >= $start && $date <= $end) {
                return true;
            }
        }

        return false;
    }

    /** @param string[] $dates */
    private function hasDateOutside(
        array $dates,
        ?string $excludedStart,
        ?string $excludedEnd,
        ?string $includedStart,
        ?string $includedEnd
    ): bool {
        foreach ($dates as $date) {
            $excluded = $excludedStart !== null && $date >= $excludedStart && $date <= $excludedEnd;
            $included = $includedStart !== null && $date >= $includedStart && $date <= $includedEnd;
            if (!$excluded && $included) {
                return true;
            }
        }

        return false;
    }

    /** @param string[] $dates */
    private function hasDateBefore(array $dates, ?string $start): bool
    {
        foreach ($dates as $date) {
            if ($start !== null && $date < $start) {
                return true;
            }
        }

        return false;
    }

    /** @return array{hunt_hints:int[],riddles:array<int,array<string,mixed>>} */
    private function catalog(): array
    {
        return [
            'hunt_hints' => [50],
            'riddles' => [
                [
                    'id' => 10,
                    'validation' => 'automatique',
                    'steps' => [['id' => 101], ['id' => 102], ['id' => 103]],
                    'hints' => [51, 52],
                ],
                [
                    'id' => 11,
                    'validation' => 'manuelle',
                    'steps' => [111],
                    'hints' => [],
                ],
                [
                    'id' => 12,
                    'validation' => 'aucune',
                    'steps' => [],
                    'hints' => [53],
                ],
            ],
        ];
    }

    /**
     * @param array<int,int> $users
     * @param int[] $deleted
     */
    private function service(
        object $database,
        array &$users,
        array &$deleted,
        ?callable $clear = null
    ): StatisticsFixtureService {
        return new StatisticsFixtureService(
            $database,
            static function () use (&$users): array {
                return $users;
            },
            static function (int $number) use (&$users): int {
                $users[] = $number;
                return $number;
            },
            static function (int $userId) use (&$users, &$deleted): bool {
                $deleted[] = $userId;
                $users = array_values(array_filter(
                    $users,
                    static function (int $id) use ($userId): bool {
                        return $id !== $userId;
                    }
                ));
                return true;
            },
            static function () use (&$users): array {
                return $users === [] ? [] : [7];
            },
            static function (): DateTimeImmutable {
                return new DateTimeImmutable('2026-10-21 15:00:00', new DateTimeZone('Europe/Paris'));
            },
            $clear ?? static function (int $huntId, array $riddleIds): void {
            }
        );
    }

    private function database(): object
    {
        return new class {
            public string $prefix = 'wp_';
            public string $last_error = '';
            public int $rows_affected = 1;
            public array $inserts = [];
            public array $queries = [];
            public bool $failAttempts = false;

            public function insert(string $table, array $data, $format = null)
            {
                if ($this->failAttempts && substr($table, -17) === 'enigme_tentatives') {
                    $this->last_error = 'insert failed';
                    return false;
                }

                $this->last_error = '';
                $this->inserts[] = [$table, $data];
                return 1;
            }

            public function prepare(string $query, ...$args): string
            {
                return $query . ' [' . implode(',', $args) . ']';
            }

            public function query(string $sql)
            {
                $this->queries[] = $sql;
                $this->last_error = '';
                return 1;
            }
        };
    }

    /** @return array<int,array<string,mixed>> */
    private function inserted(object $database, string $table, ?callable $filter = null): array
    {
        $rows = [];
        foreach ($database->inserts as [$insertedTable, $data]) {
            if ($insertedTable !== 'wp_' . $table) {
                continue;
            }
            if ($filter === null || $filter($data)) {
                $rows[] = $data;
            }
        }

        return $rows;
    }
}
