<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\PlayerProgressionApplicationService;
use ChassesAuTresor\Core\Progress\PlayerProgressionRepository;
use ChassesAuTresor\Core\Progress\ProgressionOverviewRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/ProgressionOverviewRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/PlayerProgressionApplicationService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/PlayerProgressionRepository.php';

final class PlayerProgressionDashboardStub extends ProgressionOverviewRepository
{
    public function __construct()
    {
    }

    public function summarizeUserAttempts(int $userId, array $riddleIds): array
    {
        return ['total' => 9, 'success' => 3, 'pending' => 1];
    }

    public function countUnlockedHints(int $userId, int $huntId, array $riddleIds): int
    {
        return 2;
    }

    public function userRiddleActivity(int $userId, array $riddleIds): array
    {
        return [40 => ['attempts' => 4, 'last_at' => '2026-10-01 08:00:00']];
    }

    public function userStatuses(int $userId, array $riddleIds): array
    {
        return [40 => ['status' => 'terminée', 'updated_at' => '2026-10-03 09:00:00']];
    }

    public function userCompletedSteps(int $userId, array $riddleIds): array
    {
        return [40 => ['count' => 1, 'last_at' => '2026-10-02 09:00:00']];
    }
}

final class PlayerProgressionDataStub extends PlayerProgressionRepository
{
    public function __construct()
    {
    }

    public int $eligible = 1;

    public function riddleRank(int $userId, int $riddleId, array $excludedUserIds): int
    {
        return $riddleId === 40 ? 2 : 0;
    }

    public function countEligibleRiddlePlayers(int $riddleId, array $excludedUserIds): int
    {
        return $this->eligible;
    }

    public function riddleRanking(int $riddleId, array $excludedUserIds, int $limit, int $offset): array
    {
        return [[
            'user_id' => 8,
            'username' => 'Alice',
            'team_name' => 'Corsaires',
            'solved' => 1,
        ]];
    }

    public function countHuntLeaderboardPlayers(int $huntId, array $riddleIds, array $excludedUserIds): int
    {
        return 1;
    }

    public function huntLeaderboard(
        int $huntId,
        array $riddleIds,
        array $excludedUserIds,
        int $limit,
        int $offset
    ): array {
        return [['user_id' => 7, 'username' => 'Bob', 'team_name' => '', 'resolved' => 1]];
    }

    public function huntPosition(int $userId, int $huntId, array $riddleIds, array $excludedUserIds): int
    {
        return 1;
    }
}

final class PlayerProgressionApplicationServiceTest extends TestCase
{
    public function testBuildsPersonalIndicatorsAndRiddleRows(): void
    {
        $service = new PlayerProgressionApplicationService(
            new PlayerProgressionDashboardStub(),
            new PlayerProgressionDataStub()
        );
        $overview = $service->build(
            7,
            12,
            static fn (int $huntId): array => [40, 41],
            static fn (int $huntId, int $userId): array => [
                'resolues' => 1,
                'resolvables' => 2,
                'engagees' => 1,
                'total' => 2,
            ],
            static fn (int $riddleId): array => [
                'title' => $riddleId === 40 ? 'Alpha' : 'Beta',
                'url' => '/enigme/' . $riddleId,
                'steps_total' => $riddleId === 40 ? 3 : 0,
            ]
        );

        self::assertSame(1, $overview['resolved']);
        self::assertSame(2, $overview['resolvable']);
        self::assertSame(3, $overview['attempts_success']);
        self::assertSame(9, $overview['attempts_total']);
        self::assertSame(1, $overview['attempts_pending']);
        self::assertSame(2, $overview['hints']);
        self::assertSame('terminee', $overview['riddles'][0]['status']);
        self::assertSame(1, $overview['riddles'][0]['steps_done']);
        self::assertSame(3, $overview['riddles'][0]['steps_total']);
        self::assertSame(4, $overview['riddles'][0]['attempts']);
        self::assertSame('2026-10-03 09:00:00', $overview['riddles'][0]['last_activity']);
        self::assertSame('non_commencee', $overview['riddles'][1]['status']);
        self::assertSame(0, $overview['riddles'][1]['attempts']);
        self::assertSame(2, $overview['riddles'][0]['rank']);
        self::assertSame(0, $overview['riddles'][1]['rank']);
        self::assertArrayNotHasKey('leaders', $overview['riddles'][0]);
        self::assertArrayNotHasKey('leaderboard', $overview);
    }

    public function testRiddleRankingRequiresTwoEligiblePlayers(): void
    {
        $data = new PlayerProgressionDataStub();
        $service = new PlayerProgressionApplicationService(
            new PlayerProgressionDashboardStub(),
            $data
        );
        $provider = static fn (int $huntId): array => [40];

        $hidden = $service->riddleRanking(7, 12, 40, 1, $provider);
        self::assertSame([], $hidden['rows']);
        self::assertSame(0, $hidden['total']);

        $data->eligible = 3;
        $shown = $service->riddleRanking(7, 12, 40, 1, $provider);
        self::assertSame('Alice', $shown['rows'][0]['username']);
        self::assertArrayNotHasKey('steps_done', $shown['rows'][0]);
        self::assertArrayNotHasKey('elapsed', $shown['rows'][0]);

        $page = $service->rankingPage(7, 12, $provider);
        self::assertSame('Bob', $page['leaderboard']['rows'][0]['username']);
        self::assertSame('Alice', $page['riddles'][0]['ranking']['rows'][0]['username']);
    }

    public function testMissingPlayerOrHuntStaysEmpty(): void
    {
        $service = new PlayerProgressionApplicationService(
            new PlayerProgressionDashboardStub(),
            new PlayerProgressionDataStub()
        );

        self::assertSame([], $service->build(0, 12)['riddles']);
        self::assertSame(0, $service->build(7, 0)['hints']);
    }
}
