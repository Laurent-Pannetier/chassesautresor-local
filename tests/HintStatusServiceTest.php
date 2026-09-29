<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintStatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintStatusService.php';

class HintStatusServiceTest extends TestCase
{
    private HintStatusService $service;

    protected function setUp(): void
    {
        $this->service = new HintStatusService();
    }

    public function testEmptyHintIsIncompleteAndDisabled(): void
    {
        $this->assertSame(
            ['complete' => false, 'state' => 'desactive'],
            $this->service->resolve(false, false, 'immediate', null, 100)
        );
    }

    public function testAvailabilityIsNormalized(): void
    {
        $this->assertSame('differe', $this->service->normalizeAvailability('differe'));
        $this->assertSame('immediate', $this->service->normalizeAvailability('immediate'));
        $this->assertSame('immediate', $this->service->normalizeAvailability('inconnu'));
    }

    public function testAvailabilityDateUsesSubmittedThenExistingThenFallbackValue(): void
    {
        $this->assertSame(
            'submitted',
            $this->service->resolveAvailabilityDate('submitted', 'existing', 'fallback')
        );
        $this->assertSame(
            'existing',
            $this->service->resolveAvailabilityDate('', 'existing', 'fallback')
        );
        $this->assertSame(
            'fallback',
            $this->service->resolveAvailabilityDate('', '', 'fallback')
        );
    }

    public function testImmediateHintWithContentIsAccessible(): void
    {
        $this->assertSame(
            ['complete' => true, 'state' => 'accessible'],
            $this->service->resolve(true, false, 'immediate', null, 100)
        );
    }

    public function testDeferredHintRequiresAValidDate(): void
    {
        $this->assertSame(
            ['complete' => false, 'state' => 'desactive'],
            $this->service->resolve(false, true, 'differe', null, 100)
        );
    }

    public function testDeferredHintIsProgrammedUntilItsDate(): void
    {
        $this->assertSame(
            ['complete' => true, 'state' => 'programme'],
            $this->service->resolve(true, false, 'differe', 101, 100)
        );
        $this->assertSame(
            ['complete' => true, 'state' => 'accessible'],
            $this->service->resolve(true, false, 'differe', 100, 100)
        );
    }

    public function testPublicationStatusFollowsHintAvailability(): void
    {
        $this->assertSame(
            'publish',
            $this->service->resolvePublicationStatus(true, 'accessible', 'pending')
        );
        $this->assertSame(
            'pending',
            $this->service->resolvePublicationStatus(true, 'programme', 'publish')
        );
        $this->assertNull(
            $this->service->resolvePublicationStatus(true, 'accessible', 'publish')
        );
        $this->assertNull(
            $this->service->resolvePublicationStatus(false, 'desactive', 'pending')
        );
    }
}
