<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\PlayerProgressionRepository;
use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/PlayerProgressionRepository.php';

final class PlayerProgressionSqlWpdb
{
    public string $sql = '';

    public string $users = 'wp_users';

    public string $prefix = 'wp_';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function prepare(string $sql, ...$params): string
    {
        $this->sql = $sql;

        return $sql;
    }

    public function get_results(string $sql, $output = null): array
    {
        return $this->rows;
    }

    public function get_var(string $sql)
    {
        return 0;
    }
}

final class PlayerProgressionRepositoryTest extends TestCase
{
    public function testHuntRankingIgnoresPlayersWithoutASolvedRiddle(): void
    {
        $database = new PlayerProgressionSqlWpdb();
        $repository = new PlayerProgressionRepository($database);

        self::assertSame([], $repository->huntLeaderboard(3, [9], [], 10, 0));
        self::assertStringContainsString('COALESCE(sc.resolved, 0) > 0', $database->sql);

        self::assertSame(0, $repository->countHuntLeaderboardPlayers(3, [9], []));
        self::assertStringContainsString('COALESCE(sc.resolved, 0) > 0', $database->sql);
    }

    public function testRiddleRankingKeepsOnlyPlayersWhoFoundSomething(): void
    {
        $database = new PlayerProgressionSqlWpdb();
        $repository = new PlayerProgressionRepository($database);

        $database->rows = [[
            'user_id' => 4,
            'username' => 'Ada',
            'team_name' => '',
            'solved' => 1,
            'elapsed' => 12,
        ]];
        $rows = $repository->riddleRanking(9, [], 10, 0);
        self::assertSame('Ada', $rows[0]['username']);
        self::assertArrayNotHasKey('elapsed', $rows[0]);
        self::assertArrayNotHasKey('steps_done', $rows[0]);
        self::assertStringContainsString("resultat IN ('bon', 'variante', 'attente')", $database->sql);
        self::assertStringContainsString("statut = 'trouvee'", $database->sql);

        self::assertSame(0, $repository->riddleRank(4, 9, []));
    }
}
