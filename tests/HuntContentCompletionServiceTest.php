<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntCompletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntCompletionService.php';

class HuntContentCompletionServiceTest extends TestCase
{
    public function testCompleteHuntRequiresTitleDescriptionAndImage(): void
    {
        $service = new HuntCompletionService();

        $this->assertTrue($service->isComplete(true, 'Description', 10, 99, 'manuelle', false));
        $this->assertFalse($service->isComplete(false, 'Description', 10, 99, 'manuelle', false));
        $this->assertFalse($service->isComplete(true, ' ', 10, 99, 'manuelle', false));
        $this->assertFalse($service->isComplete(true, 'Description', 0, 99, 'manuelle', false));
        $this->assertFalse($service->isComplete(true, 'Description', 99, 99, 'manuelle', false));
    }

    public function testAutomaticHuntRequiresAValidatableRiddle(): void
    {
        $service = new HuntCompletionService();

        $this->assertFalse($service->isComplete(true, 'Description', 10, 99, 'automatique', false));
        $this->assertTrue($service->isComplete(true, 'Description', 10, 99, 'automatique', true));
    }
}
