<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintQueryService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintQueryService.php';

class HintQueryServiceTest extends TestCase
{
    private HintQueryService $service;

    protected function setUp(): void
    {
        $this->service = new HintQueryService();
    }

    public function testInvalidTargetHasNoQueryArguments(): void
    {
        $this->assertSame([], $this->service->getRankedHintIdsQueryArgs(0, 'chasse'));
        $this->assertSame([], $this->service->getRankedHintIdsQueryArgs(12, 'solution'));
    }

    public function testManagementTableRejectsInvalidTarget(): void
    {
        $this->assertSame([], $this->service->getManagementTableQueryArgs(0, 'chasse'));
        $this->assertSame([], $this->service->getManagementTableQueryArgs(12, 'solution'));
    }

    public function testHuntManagementTableIncludesDirectAndRiddleHints(): void
    {
        $args = $this->service->getManagementTableQueryArgs(12, 'chasse', [24, '25', 24, 0], 2, 5);

        $this->assertSame('OR', $args['meta_query']['relation']);
        $this->assertSame('indice_chasse_linked', $args['meta_query'][0][1]['key']);
        $this->assertSame(12, $args['meta_query'][0][1]['value']);
        $this->assertSame([24, 25], $args['meta_query'][1][1]['value']);
        $this->assertSame('IN', $args['meta_query'][1][1]['compare']);
        $this->assertSame(2, $args['paged']);
        $this->assertSame(5, $args['posts_per_page']);
    }

    public function testRiddleManagementTableOnlyIncludesItsHints(): void
    {
        $args = $this->service->getManagementTableQueryArgs(24, 'enigme', [99], 0, 0);

        $this->assertSame('indice_cible_type', $args['meta_query'][0]['key']);
        $this->assertSame('enigme', $args['meta_query'][0]['value']);
        $this->assertSame('indice_enigme_linked', $args['meta_query'][1]['key']);
        $this->assertSame(24, $args['meta_query'][1]['value']);
        $this->assertSame(1, $args['paged']);
        $this->assertSame(1, $args['posts_per_page']);
    }

    public function testManagementTableIdsQueryIsUnpaginated(): void
    {
        $args = $this->service->getManagementTableQueryArgs(12, 'chasse', [], 3, 5, true);

        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['nopaging']);
        $this->assertArrayNotHasKey('paged', $args);
        $this->assertArrayNotHasKey('posts_per_page', $args);
    }

    public function testHuntQueryIncludesAllRankedStates(): void
    {
        $args = $this->service->getRankedHintIdsQueryArgs(12, 'chasse');

        $this->assertSame('indice_chasse_linked', $args['meta_query'][0]['key']);
        $this->assertSame(12, $args['meta_query'][0]['value']);
        $this->assertSame(
            ['programme', 'accessible', 'desactive'],
            $args['meta_query'][1]['value']
        );
        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['no_found_rows']);
    }

    public function testRiddleQueryRequiresRiddleTargetType(): void
    {
        $args = $this->service->getRankedHintIdsQueryArgs(24, 'enigme');

        $this->assertSame('indice_cible_type', $args['meta_query'][0]['key']);
        $this->assertSame('enigme', $args['meta_query'][0]['value']);
        $this->assertSame('indice_enigme_linked', $args['meta_query'][1]['key']);
        $this->assertSame(24, $args['meta_query'][1]['value']);
    }

    public function testOrderedQueryUsesOldestHintFirst(): void
    {
        $args = $this->service->getRankedHintIdsQueryArgs(12, 'chasse', true);

        $this->assertSame('date', $args['orderby']);
        $this->assertSame('ASC', $args['order']);
    }

    public function testDueProgrammedHintsUseCurrentDate(): void
    {
        $args = $this->service->getDueProgrammedHintIdsQueryArgs('2026-09-29 12:00:00');

        $this->assertSame('programme', $args['meta_query'][0]['value']);
        $this->assertSame('indice_date_disponibilite', $args['meta_query'][1]['key']);
        $this->assertSame('2026-09-29 12:00:00', $args['meta_query'][1]['value']);
        $this->assertSame('<=', $args['meta_query'][1]['compare']);
        $this->assertSame('DATETIME', $args['meta_query'][1]['type']);
        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['no_found_rows']);
    }

    public function testDueProgrammedHintsRejectEmptyDate(): void
    {
        $this->assertSame([], $this->service->getDueProgrammedHintIdsQueryArgs(''));
    }
}
