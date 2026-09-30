<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleDeletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleDeletionService.php';

class RiddleDeletionServiceTest extends TestCase {
    public function testOnlyRiddlePostTypeIsSupported(): void {
        $service = new RiddleDeletionService();

        $this->assertTrue($service->supportsPostType('enigme'));
        $this->assertFalse($service->supportsPostType('chasse'));
        $this->assertFalse($service->supportsPostType(''));
    }
}
