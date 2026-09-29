<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerHuntQueryService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/OrganizerHuntQueryService.php';

class OrganizerHuntQueryServiceTest extends TestCase
{
    private OrganizerHuntQueryService $service;

    protected function setUp(): void
    {
        $this->service = new OrganizerHuntQueryService();
    }

    public function testInvalidOrganizerHasNoQueryArguments(): void
    {
        $this->assertSame([], $this->service->getExistingHuntQueryArgs(0));
        $this->assertSame([], $this->service->getHuntIdsQueryArgs(-1));
        $this->assertSame([], $this->service->getPublishedHuntCountQueryArgs(0));
    }

    public function testExistingHuntsExcludeBannedContent(): void
    {
        $args = $this->service->getExistingHuntQueryArgs(42);

        $this->assertSame(['publish', 'pending'], $args['post_status']);
        $this->assertSame('"42"', $args['meta_query'][0]['value']);
        $this->assertSame('banni', $args['meta_query'][1]['value']);
        $this->assertSame('!=', $args['meta_query'][1]['compare']);
    }

    public function testPendingHuntQueryOnlyIncludesPendingContent(): void
    {
        $args = $this->service->getExistingHuntQueryArgs(42, true);

        $this->assertSame('pending', $args['post_status']);
    }

    public function testHuntIdQueryIsOptimized(): void
    {
        $args = $this->service->getHuntIdsQueryArgs(42);

        $this->assertSame('ids', $args['fields']);
        $this->assertTrue($args['no_found_rows']);
        $this->assertFalse($args['update_post_meta_cache']);
        $this->assertFalse($args['update_post_term_cache']);
    }

    public function testPublishedCountQueryRequestsFoundRows(): void
    {
        $args = $this->service->getPublishedHuntCountQueryArgs(42);

        $this->assertSame('publish', $args['post_status']);
        $this->assertFalse($args['no_found_rows']);
        $this->assertSame('"42"', $args['meta_query'][0]['value']);
    }
}
