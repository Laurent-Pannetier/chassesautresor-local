<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatisticsApplicationService;
use ChassesAuTresor\Core\Progress\RiddleStatisticsService;
use PHPUnit\Framework\TestCase;

final class RiddleStatisticsOverviewServiceStub extends RiddleStatisticsService
{
    public function __construct()
    {
    }

    public function countEngagedPlayers(
        int $id,
        ?string $start = null,
        ?string $end = null,
        array $excludedUserIds = []
    ): int {
        return $id === 40 ? 4 : 0;
    }

    public function countAttempts(int $id, ?string $start = null, ?string $end = null): int
    {
        return $id === 40 ? 11 : 0;
    }

    public function countCorrectSolutions(int $id, ?string $start = null, ?string $end = null): int
    {
        return $id === 40 ? 2 : 0;
    }

    public function sumSpentPoints(int $id, ?string $start = null, ?string $end = null): int
    {
        return 0;
    }

    public function countSolvedPlayers(int $id): int
    {
        return $id === 40 ? 2 : 0;
    }

    public function listParticipantHints(int $id, int $userId): array
    {
        if ($id !== 40 || $userId !== 7) {
            return [];
        }

        return [
            ['indice_id' => 5, 'date_deblocage' => '2026-10-01 10:00:00'],
            ['indice_id' => 6, 'date_deblocage' => '2026-10-01 11:00:00'],
        ];
    }

    public function listSolvers(int $id, array $excludedUserIds = []): array
    {
        if ($id !== 40) {
            return [];
        }

        return [
            ['user_id' => 7, 'username' => 'alice', 'date' => '2026-10-01 10:00:00', 'tentatives' => 2],
            ['user_id' => 8, 'username' => 'bob', 'date' => '2026-10-01 11:00:00', 'tentatives' => 4],
        ];
    }
}

final class RiddleStatisticsOverviewTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBuildsCompactHuntOverviewRows(): void
    {
        function get_field($key, $id = null)
        {
            if ($key === 'enigme_mode_validation') {
                return 'automatique';
            }
            if ($key === 'enigme_tentative_cout_points') {
                return 0;
            }
            if ($key === 'enigme_chasse_associee') {
                return 12;
            }
            if ($key === 'chasse_cache_organisateur') {
                return 0;
            }

            return null;
        }
        function get_users($args = [])
        {
            return [];
        }
        function get_the_title($id)
        {
            return (int) $id === 40 ? 'Énigme Alpha' : '';
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsRepository.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsPeriodService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsApplicationService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';

        $service = new RiddleStatisticsApplicationService(new RiddleStatisticsOverviewServiceStub());
        $rows = $service->overviewForHunt(
            12,
            'total',
            static fn (int $huntId): array => $huntId === 12 ? [40] : [],
            static fn (int $riddleId): array => $riddleId === 40 ? [101, 102] : [],
            static fn (int $riddleId): int => $riddleId === 40 ? 1 : 0
        );

        self::assertCount(1, $rows);
        self::assertSame(40, $rows[0]['id']);
        self::assertSame('Énigme Alpha', $rows[0]['title']);
        self::assertSame(4, $rows[0]['participants']);
        self::assertSame(11, $rows[0]['tentatives']);
        self::assertSame(2, $rows[0]['trouves']);
        self::assertSame(2, $rows[0]['steps']);
        self::assertSame(1, $rows[0]['step_players']);
        self::assertArrayNotHasKey('ranking', $rows[0]);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testParticipantHintTitlesUseThePlayerRankLabel(): void
    {
        function get_post($id)
        {
            $post = new stdClass();
            $post->post_title = (int) $id === 6 ? 'Titre personnalisé' : 'clue-antre-de-feu';

            return $post;
        }
        function get_post_meta($id, $key = '', $single = false)
        {
            return (int) $id === 5 ? 1 : 2;
        }
        function __($text)
        {
            return $text;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTitleService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsRepository.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsPeriodService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsApplicationService.php';

        $hints = (new RiddleStatisticsApplicationService(new RiddleStatisticsOverviewServiceStub()))
            ->participantHints(40, 7);

        self::assertSame('Indice #1', $hints[0]['title']);
        self::assertSame('Titre personnalisé', $hints[1]['title']);
    }
}
