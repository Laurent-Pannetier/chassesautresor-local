<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCreationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCreationService.php';

class HintCreationServiceTest extends TestCase
{
    private HintCreationService $service;

    protected function setUp(): void
    {
        $this->service = new HintCreationService();
    }

    public function testOnlyHuntsAndRiddlesAreSupportedTargets(): void
    {
        $this->assertTrue($this->service->isSupportedTargetType('chasse'));
        $this->assertTrue($this->service->isSupportedTargetType('enigme'));
        $this->assertFalse($this->service->isSupportedTargetType('solution'));
        $this->assertFalse($this->service->isSupportedTargetType(''));
    }

    public function testNewHintStartsPendingImmediateAndDisabled(): void
    {
        $this->assertSame(
            [
                'post_status' => 'pending',
                'availability' => 'immediate',
                'availability_timestamp' => 200,
                'points_cost' => 0,
                'complete' => false,
                'system_state' => 'desactive',
            ],
            $this->service->getInitialState(100, 100)
        );
    }

    public function testNegativeAvailabilityDelayIsClampedToZero(): void
    {
        $state = $this->service->getInitialState(100, -10);

        $this->assertSame(100, $state['availability_timestamp']);
    }
}
