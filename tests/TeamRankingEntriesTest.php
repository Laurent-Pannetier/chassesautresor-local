<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamPerformanceRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Teams/TeamPerformanceRepository.php';

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

final class TeamRankingEntriesWpdb
{
    public string $users = 'wp_users';

    public string $prefix = 'wp_';

    /** @var string[] */
    public array $sqls = [];

    public function prepare(string $sql, ...$params): string
    {
        $this->sqls[] = $sql;

        return $sql;
    }

    public function get_results(string $sql, $output = null): array
    {
        if (str_contains($sql, 'e.nom AS team_name')) {
            return [[
                'team_id' => 2,
                'user_id' => 0,
                'team_name' => 'Alpha',
                'image_id' => 4,
                'members' => 3,
                'resolved' => 3,
                'reached_at' => '2026-10-02 10:00:00',
            ]];
        }
        if (str_contains($sql, '0 AS team_id')) {
            return [
                [
                    'team_id' => 0,
                    'user_id' => 5,
                    'team_name' => 'Zoe',
                    'image_id' => 0,
                    'members' => 1,
                    'resolved' => 3,
                    'reached_at' => '2026-10-01 10:00:00',
                ],
                [
                    'team_id' => 0,
                    'user_id' => 9,
                    'team_name' => 'Nemo',
                    'image_id' => 0,
                    'members' => 1,
                    'resolved' => 0,
                    'reached_at' => '2026-10-01 09:00:00',
                ],
            ];
        }

        return [];
    }

    public function get_col(string $sql)
    {
        return [];
    }

    public function get_var(string $sql)
    {
        return 0;
    }
}

final class TeamRankingEntriesTest extends TestCase
{
    public function testSolosShareTheBoardAndOutrankASlowerTeam(): void
    {
        $database = new TeamRankingEntriesWpdb();
        $rows = (new TeamPerformanceRepository($database))->rankedEntries(3, [9], [1]);

        self::assertSame(['Zoe', 'Alpha'], array_column($rows, 'team_name'));
        self::assertArrayNotHasKey('reached_at', $rows[0]);
        self::assertSame(0, (int) $rows[0]['team_id']);
        self::assertSame(1, (int) $rows[0]['members']);
        $soloSql = implode("\n", $database->sqls);
        self::assertStringContainsString('NOT EXISTS', $soloSql);
        self::assertStringContainsString('u.ID NOT IN', $soloSql);
    }
}
