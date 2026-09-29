<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\OrganizerCompletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerCompletionService.php';

class OrganizerCompletionServiceTest extends TestCase
{
    public function testCompleteOrganizerRequiresTitleLogoAndDescription(): void
    {
        $service = new OrganizerCompletionService();

        $this->assertTrue($service->isComplete(true, true, 'Description'));
        $this->assertFalse($service->isComplete(false, true, 'Description'));
        $this->assertFalse($service->isComplete(true, false, 'Description'));
        $this->assertFalse($service->isComplete(true, true, ''));
        $this->assertFalse($service->isComplete(true, true, '   '));
    }
}
