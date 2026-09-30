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

    public function testRiddleTargetMustMatchSubmittedRelationship(): void
    {
        $this->assertTrue($this->service->hasConsistentRiddleTarget('enigme', 12, 12));
        $this->assertFalse($this->service->hasConsistentRiddleTarget('enigme', 12, 24));
        $this->assertFalse($this->service->hasConsistentRiddleTarget('enigme', 12, 0));
        $this->assertTrue($this->service->hasConsistentRiddleTarget('chasse', 12, 0));
    }

    public function testLinkedHuntComesFromRiddleOrRequestContext(): void
    {
        $this->assertSame(24, $this->service->resolveLinkedHuntId('enigme', 24, 12));
        $this->assertSame(12, $this->service->resolveLinkedHuntId('chasse', 24, 12));
        $this->assertNull($this->service->resolveLinkedHuntId('enigme', null, 12));
        $this->assertNull($this->service->resolveLinkedHuntId('chasse', 24, 0));
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

    /**
     * @dataProvider creationErrorProvider
     */
    public function testCreationErrorsFollowValidationOrder(array $context, ?string $expected): void
    {
        $this->assertSame($expected, $this->service->getCreationError(...$context));
    }

    public function creationErrorProvider(): array
    {
        return [
            'unsupported type' => [[false, false, false, false, false, false], 'type_invalide'],
            'invalid target' => [[true, false, false, false, false, false], 'cible_invalide'],
            'guest' => [[true, true, false, false, false, false], 'non_connecte'],
            'target forbidden' => [[true, true, true, false, true, true], 'permission_refusee'],
            'missing hunt' => [[true, true, true, true, false, false], 'permission_refusee'],
            'hunt forbidden' => [[true, true, true, true, true, false], 'permission_refusee'],
            'allowed' => [[true, true, true, true, true, true], null],
        ];
    }

    public function testNegativeAvailabilityDelayIsClampedToZero(): void
    {
        $state = $this->service->getInitialState(100, -10);

        $this->assertSame(100, $state['availability_timestamp']);
    }
}
