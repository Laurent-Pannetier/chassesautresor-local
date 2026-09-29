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
}
