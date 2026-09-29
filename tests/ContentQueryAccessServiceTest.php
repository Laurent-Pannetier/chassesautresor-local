<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentQueryAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentQueryAccessService.php';

class ContentQueryAccessServiceTest extends TestCase
{
    private ContentQueryAccessService $service;

    protected function setUp(): void
    {
        $this->service = new ContentQueryAccessService();
    }

    public function testAdministratorCanQueryDraftAndPendingContent(): void
    {
        $this->assertSame(
            ['publish', 'pending', 'draft'],
            $this->service->getVisibleStatuses(true, true, false)
        );
    }

    public function testOrganizerCanQueryPendingButNotDraftContent(): void
    {
        $this->assertSame(
            ['publish', 'pending'],
            $this->service->getVisibleStatuses(true, false, true)
        );
    }

    public function testRegularAuthenticatedUserDoesNotOverrideQueryStatuses(): void
    {
        $this->assertSame([], $this->service->getVisibleStatuses(true, false, false));
    }

    public function testGuestDoesNotOverrideStatusesEvenWithRoleFlags(): void
    {
        $this->assertSame([], $this->service->getVisibleStatuses(false, true, true));
    }
}
