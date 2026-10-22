<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\OrganizerProgressionApplicationService;
use ChassesAuTresor\Core\Progress\ProgressionOverviewRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsPeriodService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/ProgressionOverviewRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/OrganizerProgressionApplicationService.php';

final class OrganizerProgressionRepositoryStub extends ProgressionOverviewRepository
{
    public ?string $startAt = 'unset';
    public array $excluded = [];

    public ?array $hint = ['indice_id' => 9, 'unlocks' => 5];

    public function __construct()
    {
    }

    public function attemptBreakdown(
        int $riddleId,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): array {
        $this->startAt = $startAt;
        $this->excluded = $excludedUserIds;

        return [
            'bon' => 2,
            'variante' => 1,
            'faux' => 8,
            'attente' => 1,
            'pending' => 1,
            'total' => 12,
        ];
    }

    public function countPlayersWithStatus(int $riddleId, array $statuses, array $excludedUserIds = []): int
    {
        return 1;
    }

    public function topHint(int $riddleId, array $excludedUserIds = []): ?array
    {
        return $this->hint;
    }

    public function countPlayersByStep(array $stepIds, array $excludedUserIds = []): array
    {
        return [101 => 3];
    }

    public function countAttemptsByStep(
        array $stepIds,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): array {
        return [101 => 8];
    }

    public function finalAttemptBuckets(
        int $riddleId,
        string $grain,
        ?string $startAt = null,
        ?string $endAt = null,
        array $excludedUserIds = []
    ): array {
        return [
            ['bucket' => '2026-10-05', 'total' => 4, 'success' => 1],
            ['bucket' => '2026-10-12', 'total' => 8, 'success' => 1],
        ];
    }
}

final class OrganizerProgressionApplicationServiceTest extends TestCase
{
    public function testEnrichesEachRiddleWithFunnelYieldAndSteps(): void
    {
        $repository = new OrganizerProgressionRepositoryStub();
        $service = new OrganizerProgressionApplicationService(
            $repository,
            static fn (): array => [[
                'id' => 40,
                'title' => 'Alpha',
                'participants' => 4,
                'tentatives' => 11,
                'trouves' => 2,
                'steps' => 1,
                'step_players' => 3,
            ]],
            static fn (): array => [
                'participants' => 6,
                'engagement_rate' => 40,
                'resolution_rate' => 25,
                'tentatives' => 18,
                'winners' => 1,
                'winner_cap' => 3,
            ],
            static fn (): array => [2, 4, 9],
            static fn (): array => [['id' => 101, 'title' => 'La porte']],
            static fn (): array => [3, 8],
            static fn (int $hintId): string => 'Carte'
        );

        $now = new DateTimeImmutable('2026-10-22 15:00:00', new DateTimeZone('Europe/Paris'));
        $dashboard = $service->build(12, 'total', $now);
        $riddle = $dashboard['riddles'][0];

        self::assertNull($repository->startAt);
        self::assertSame([3, 8], $repository->excluded);
        self::assertSame(4, $riddle['funnel']['engaged']);
        self::assertSame(3, $riddle['funnel']['with_step']);
        self::assertSame(1, $riddle['funnel']['submitted']);
        self::assertSame(2, $riddle['funnel']['solved']);
        self::assertSame(['success' => 2, 'total' => 12], $riddle['yield']);
        self::assertSame(1, $riddle['pending']);
        self::assertSame(4, $riddle['median_attempts']);
        self::assertSame('Carte', $riddle['top_hint']['title']);
        self::assertSame(5, $riddle['top_hint']['unlocks']);
        self::assertSame('La porte', $riddle['steps_detail'][0]['title']);
        self::assertSame(3, $riddle['steps_detail'][0]['reached']);
        self::assertSame(8, $riddle['steps_detail'][0]['attempts']);
        self::assertSame(25, $dashboard['summary']['resolution_rate']);
        self::assertSame('week', $riddle['attempt_series_grain']);
        self::assertSame('05/10', $riddle['attempt_series'][0]['label']);
        self::assertSame(4, $riddle['attempt_series'][0]['total']);
        self::assertSame(1, $riddle['attempt_series'][0]['success']);
        self::assertSame('12/10', $riddle['attempt_series'][1]['label']);
        self::assertSame(8, $riddle['attempt_series'][1]['total']);
        self::assertSame('19/10', $riddle['attempt_series'][2]['label']);
        self::assertSame(0, $riddle['attempt_series'][2]['total']);
    }

    public function testASingleDayDoesNotBecomeACurve(): void
    {
        $service = new OrganizerProgressionApplicationService(new OrganizerProgressionRepositoryStub());

        self::assertNull($service->seriesGrain('2026-10-22 00:00:00', '2026-10-22 15:00:00'));
        self::assertSame('day', $service->seriesGrain('2026-10-19 00:00:00', '2026-10-22 15:00:00'));
        self::assertSame('week', $service->seriesGrain(null, null));
        self::assertSame([], $service->fillAttemptSeries(
            [['bucket' => '2026-10-22', 'total' => 3, 'success' => 1]],
            'day',
            '2026-10-22 00:00:00',
            '2026-10-22 15:00:00'
        ));
    }

    public function testMedianOfAnEvenCountAveragesTheMiddlePair(): void
    {
        $service = new OrganizerProgressionApplicationService(new OrganizerProgressionRepositoryStub());

        self::assertSame(3, $service->median([4, 2]));
        self::assertNull($service->median([]));
    }

    public function testARiddleWithoutStepsOmitsTheStepStage(): void
    {
        $repository = new OrganizerProgressionRepositoryStub();
        $repository->hint = null;
        $service = new OrganizerProgressionApplicationService(
            $repository,
            static fn (): array => [[
                'id' => 40,
                'participants' => 2,
                'trouves' => 0,
                'steps' => 0,
                'step_players' => 0,
            ]],
            static fn (): array => [],
            static fn (): array => [],
            static fn (): array => [],
            static fn (): array => [],
            static fn (): string => ''
        );

        $riddle = $service->build(12)['riddles'][0];

        self::assertNull($riddle['funnel']['with_step']);
        self::assertSame([], $riddle['steps_detail']);
    }
}
