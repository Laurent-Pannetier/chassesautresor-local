<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerNavigationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerNavigationService.php';

class OrganizerNavigationServiceTest extends TestCase
{
    private OrganizerNavigationService $service;

    protected function setUp(): void
    {
        $this->service = new OrganizerNavigationService();
    }

    public function testOrganizerClassesReflectCompletionAndPublication(): void
    {
        $this->assertSame('dashboard-nav-link status-important', $this->service->getOrganizerClasses(false, 'publish'));
        $this->assertSame('dashboard-nav-link status-pending', $this->service->getOrganizerClasses(true, 'pending'));
        $this->assertSame('dashboard-nav-link status-published', $this->service->getOrganizerClasses(true, 'publish'));
    }

    public function testBannedPendingHuntIsExcluded(): void
    {
        $this->assertNull($this->service->getHuntPresentation(true, 'pending', 'banni', false));
    }

    public function testHuntPresentationIncludesPendingAndEligibilityStates(): void
    {
        $presentation = $this->service->getHuntPresentation(true, 'pending', 'en_attente', true);

        $this->assertSame('dashboard-nav-sublink status-pending status-eligible', $presentation['classes']);
        $this->assertTrue($presentation['pending_icon']);
    }

    public function testPublishedValidatedHuntIsPublished(): void
    {
        $presentation = $this->service->getHuntPresentation(true, 'publish', 'valide', false);

        $this->assertSame('dashboard-nav-sublink status-published', $presentation['classes']);
        $this->assertFalse($presentation['pending_icon']);
    }

    public function testRiddlePresentationHandlesInvalidAndImportantStates(): void
    {
        $this->assertNull($this->service->getRiddleClasses(true, 'pending', 'invalide', false));
        $this->assertSame(
            'dashboard-nav-subitem status-published status-important',
            $this->service->getRiddleClasses(true, 'publish', 'accessible', true)
        );
        $this->assertSame(
            'dashboard-nav-subitem status-published',
            $this->service->getRiddleClasses(true, 'publish', 'accessible', false)
        );
    }
}
