<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCompletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCompletionService.php';

class RiddleCompletionServiceTest extends TestCase
{
    public function testCompleteRiddleRequiresTitleAndNonPlaceholderImage(): void
    {
        $service = new RiddleCompletionService();

        $this->assertTrue($service->isComplete(true, 10, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(false, 10, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(true, 0, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(true, 99, 99, 'manuelle', false, 'immediat', false));
    }

    public function testAutomaticRiddleRequiresAnAnswer(): void
    {
        $service = new RiddleCompletionService();

        $this->assertFalse($service->isComplete(true, 10, 99, 'automatique', false, 'immediat', false));
        $this->assertTrue($service->isComplete(true, 10, 99, 'automatique', true, 'immediat', false));
    }

    public function testPrerequisiteAccessRequiresAtLeastOnePrerequisite(): void
    {
        $service = new RiddleCompletionService();

        $this->assertFalse($service->isComplete(true, 10, 99, 'manuelle', false, 'pre_requis', false));
        $this->assertTrue($service->isComplete(true, 10, 99, 'manuelle', false, 'pre_requis', true));
    }
}
