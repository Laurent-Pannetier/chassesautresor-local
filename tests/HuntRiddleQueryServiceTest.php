<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleQueryService.php';

class HuntRiddleQueryServiceTest extends TestCase
{
    private HuntRiddleQueryService $service;

    protected function setUp(): void
    {
        $this->service = new HuntRiddleQueryService();
    }

    public function testInvalidHuntHasNoQueryArguments(): void
    {
        $this->assertSame([], $this->service->getVisibleRiddlesQueryArgs(0));
        $this->assertSame([], $this->service->getRiddleIdsQueryArgs(-1));
    }

    public function testVisibleRiddlesExcludeDraftAndBannedContent(): void
    {
        $args = $this->service->getVisibleRiddlesQueryArgs(42);

        $this->assertSame(['publish', 'pending'], $args['post_status']);
        $this->assertSame('menu_order', $args['orderby']);
        $this->assertSame(42, $args['meta_query'][0]['value']);
        $this->assertSame('OR', $args['meta_query'][1]['relation']);
        $this->assertSame('NOT EXISTS', $args['meta_query'][1][0]['compare']);
        $this->assertSame('banni', $args['meta_query'][1][1]['value']);
        $this->assertSame('!=', $args['meta_query'][1][1]['compare']);
    }

    public function testIdQueryIncludesDraftRiddles(): void
    {
        $args = $this->service->getRiddleIdsQueryArgs(42);

        $this->assertSame('ids', $args['fields']);
        $this->assertSame(['publish', 'pending', 'draft'], $args['post_status']);
        $this->assertSame(42, $args['meta_query'][0]['value']);
    }

    public function testSynchronizationQueryUsesCanonicalOrderAndAllEditableStatuses(): void
    {
        $args = $this->service->getSynchronizedRiddleIdsQueryArgs(42);

        $this->assertSame(['draft', 'pending', 'publish'], $args['post_status']);
        $this->assertSame('ids', $args['fields']);
        $this->assertSame('menu_order', $args['orderby']);
        $this->assertSame('ASC', $args['order']);
        $this->assertSame(42, $args['meta_query'][0]['value']);
        $this->assertSame('LIKE', $args['meta_query'][0]['compare']);
    }

    public function testSynchronizationQueryRejectsInvalidHunt(): void
    {
        $this->assertSame([], $this->service->getSynchronizedRiddleIdsQueryArgs(0));
    }
}
