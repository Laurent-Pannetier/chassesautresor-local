<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintOrderingService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTitleService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintOrderingService.php';

class HintOrderingServiceTest extends TestCase
{
    private HintOrderingService $service;

    protected function setUp(): void
    {
        $this->service = new HintOrderingService();
    }

    public function testBuildsSequentialUpdatesAndRegeneratesGeneratedTitles(): void
    {
        $updates = $this->service->buildUpdatePlan(
            [
                ['id' => 10, 'title' => 'Indice', 'hunt_id' => null],
                ['id' => 20, 'title' => 'clue-hunt-123', 'hunt_id' => 45],
                ['id' => 30, 'title' => 'Un titre personnalisé', 'hunt_id' => 45],
            ],
            'chasse',
            45,
            'Indice',
            'clue-'
        );

        $this->assertSame([1, 2, 3], array_column($updates, 'rank'));
        $this->assertSame([45, 45, 45], array_column($updates, 'hunt_id'));
        $this->assertSame([true, true, false], array_column($updates, 'regenerate_title'));
    }

    public function testSkipsInvalidHintsWithoutCreatingRankGaps(): void
    {
        $updates = $this->service->buildUpdatePlan(
            [
                ['id' => 0, 'title' => 'Indice', 'hunt_id' => null],
                ['id' => 20, 'title' => 'Personnalisé', 'hunt_id' => null],
            ],
            'enigme',
            12,
            'Indice',
            'clue-'
        );

        $this->assertCount(1, $updates);
        $this->assertSame(1, $updates[0]['rank']);
        $this->assertNull($updates[0]['hunt_id']);
    }

    public function testRejectsInvalidOrderingTarget(): void
    {
        $hints = [['id' => 10, 'title' => 'Indice', 'hunt_id' => 45]];

        $this->assertSame([], $this->service->buildUpdatePlan($hints, 'solution', 12, 'Indice', 'clue-'));
        $this->assertSame([], $this->service->buildUpdatePlan($hints, 'chasse', 0, 'Indice', 'clue-'));
    }

    public function testAffectedTargetsIncludeRiddleAndParentHunt(): void
    {
        $this->assertSame(
            [
                ['type' => 'enigme', 'id' => 12],
                ['type' => 'chasse', 'id' => 45],
            ],
            $this->service->getAffectedTargets('enigme', 12, 45)
        );
    }

    public function testAffectedTargetsHandleHuntAndInvalidRelationships(): void
    {
        $this->assertSame(
            [['type' => 'chasse', 'id' => 45]],
            $this->service->getAffectedTargets('chasse', 45, null)
        );
        $this->assertSame(
            [['type' => 'chasse', 'id' => 45]],
            $this->service->getAffectedTargets('enigme', null, 45)
        );
        $this->assertSame([], $this->service->getAffectedTargets('solution', 12, 45));
    }
}
