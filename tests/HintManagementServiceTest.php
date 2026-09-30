<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintManagementService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintManagementService.php';

final class HintManagementServiceTest extends TestCase {
    private HintManagementService $service;

    protected function setUp(): void {
        $this->service = new HintManagementService();
    }

    public function testPaginationClampsPageAndNormalizesLimits(): void {
        $this->assertSame(['page' => 1, 'pages' => 0], $this->service->paginate(0, 0, 0));
        $this->assertSame(['page' => 3, 'pages' => 3], $this->service->paginate(9, 12, 5));
        $this->assertSame(['page' => 2, 'pages' => 3], $this->service->paginate(2, 12, 5));
    }

    public function testCountsOnlyKnownHintTargetTypes(): void {
        $types = [10 => 'chasse', 20 => 'enigme', 30 => 'unknown', 40 => 'enigme'];

        $this->assertSame(
            ['total' => 3, 'hunt' => 1, 'riddle' => 2],
            $this->service->countByTargetType(
                [10, 20, 30, 40],
                static fn (int $hintId): string => $types[$hintId]
            )
        );
    }

    public function testBuildsNormalizedRiddleOptionsAndExcludesSolutions(): void {
        $riddles = [
            (object) ['ID' => 5, 'title' => 'Première &amp; énigme'],
            (object) ['ID' => 9, 'title' => 'Avec solution'],
        ];

        $options = $this->service->buildRiddleOptions(
            $riddles,
            0,
            static fn (object $riddle): bool => $riddle->ID === 9,
            static fn (object $riddle): string => $riddle->title
        );

        $this->assertSame([
            [
                'id' => 5,
                'title' => 'Première & énigme',
                'indice_rang' => 1,
            ],
        ], $options);
    }
}
