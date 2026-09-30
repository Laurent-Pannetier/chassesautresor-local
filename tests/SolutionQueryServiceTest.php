<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionQueryService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionQueryService.php';

class SolutionQueryServiceTest extends TestCase
{
    private SolutionQueryService $service;

    protected function setUp(): void
    {
        $this->service = new SolutionQueryService();
    }

    public function testExistingQueryTargetsSingleObject(): void
    {
        $args = $this->service->getExistingSolutionIdsQueryArgs(12, 'enigme');
        $this->assertSame('solution_enigme_linked', $args['meta_query'][1][0]['key']);
        $this->assertSame(12, $args['meta_query'][1][0]['value']);
        $this->assertSame('LIKE', $args['meta_query'][1][1]['compare']);
        $this->assertSame(1, $args['posts_per_page']);
    }

    public function testActiveQueryIncludesVisibleStatesAndLegacyRelationshipFormat(): void
    {
        $args = $this->service->getActiveSolutionQueryArgs(12, 'chasse');

        $this->assertSame('IN', $args['meta_query'][1]['compare']);
        $this->assertContains('EN_COURS', $args['meta_query'][1]['value']);
        $this->assertSame('solution_chasse_linked', $args['meta_query'][2][0]['key']);
        $this->assertSame('LIKE', $args['meta_query'][2][1]['compare']);
    }

    public function testHuntManagementQueryIncludesRiddleSolutions(): void
    {
        $args = $this->service->getManagementQueryArgs(10, 'chasse', [21, 22, 21], 2, 5);
        $this->assertSame('OR', $args['meta_query']['relation']);
        $this->assertSame([21, 22], $args['meta_query'][1][1]['value']);
        $this->assertSame(2, $args['paged']);
    }

    public function testIdsQueryIsUnpaginated(): void
    {
        $args = $this->service->getManagementQueryArgs(10, 'chasse', [], 1, 5, true);
        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['nopaging']);
    }

    public function testInvalidTargetReturnsNoQuery(): void
    {
        $this->assertSame([], $this->service->getExistingSolutionIdsQueryArgs(0, 'chasse'));
        $this->assertSame([], $this->service->getActiveSolutionQueryArgs(10, 'indice'));
        $this->assertSame([], $this->service->getManagementQueryArgs(10, 'indice'));
    }
}
