<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintOrderingService;
use ChassesAuTresor\Core\Content\HintOrderingUpdater;
use ChassesAuTresor\Core\Content\HintTitleService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTitleService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintOrderingService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintOrderingUpdater.php';

final class HintOrderingUpdaterTest extends TestCase {
    private HintOrderingUpdater $updater;

    protected function setUp(): void {
        $orderingService = new HintOrderingService(new HintTitleService());
        $this->updater = new HintOrderingUpdater($orderingService, new RelationshipService());
    }

    public function testAppliesSequentialRanksAndRegeneratesOnlyGeneratedTitles(): void {
        $titles = [10 => 'Indice', 20 => 'Titre personnalisé', 30 => 'clue-old'];
        $hunts = [10 => null, 20 => ['ID' => 44], 30 => (object) ['ID' => 44]];
        $updatedPosts = [];
        $updatedRanks = [];

        $count = $this->updater->apply(
            [10, 20, 30],
            'chasse',
            44,
            'Indice',
            'clue-',
            static fn (int $hintId): string => $titles[$hintId],
            static fn (int $hintId) => $hunts[$hintId],
            static fn (int $huntId): string => 'clue-hunt-' . $huntId,
            static function (array $postData) use (&$updatedPosts): void {
                $updatedPosts[] = $postData;
            },
            static function (int $hintId, string $key, int $rank) use (&$updatedRanks): void {
                $updatedRanks[] = [$hintId, $key, $rank];
            }
        );

        $this->assertSame(3, $count);
        $this->assertSame([
            ['ID' => 10, 'post_title' => 'clue-hunt-44'],
            ['ID' => 30, 'post_title' => 'clue-hunt-44'],
        ], $updatedPosts);
        $this->assertSame([
            [10, 'indice_rank', 1],
            [20, 'indice_rank', 2],
            [30, 'indice_rank', 3],
        ], $updatedRanks);
    }

    public function testRejectsInvalidTargetWithoutWriting(): void {
        $writes = 0;

        $count = $this->updater->apply(
            [10],
            'solution',
            44,
            'Indice',
            'clue-',
            static fn (): string => 'Indice',
            static fn (): int => 44,
            static fn (): string => 'generated',
            static function () use (&$writes): void {
                ++$writes;
            },
            static function () use (&$writes): void {
                ++$writes;
            }
        );

        $this->assertSame(0, $count);
        $this->assertSame(0, $writes);
    }

    public function testSkipsInvalidHintIdsWithoutRankGap(): void {
        $ranks = [];

        $count = $this->updater->apply(
            [0, 20],
            'enigme',
            12,
            'Indice',
            'clue-',
            static fn (): string => 'Personnalisé',
            static fn () => null,
            static fn (): string => 'generated',
            static function (): void {
            },
            static function (int $hintId, string $key, int $rank) use (&$ranks): void {
                $ranks[] = [$hintId, $key, $rank];
            }
        );

        $this->assertSame(1, $count);
        $this->assertSame([[20, 'indice_rank', 1]], $ranks);
    }
}
