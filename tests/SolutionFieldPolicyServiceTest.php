<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionFieldPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionFieldPolicyService.php';

class SolutionFieldPolicyServiceTest extends TestCase {
    private SolutionFieldPolicyService $service;

    protected function setUp(): void {
        $this->service = new SolutionFieldPolicyService();
    }

    public function testSolutionRequiresAFileOrNonEmptyExplanation(): void {
        $this->assertTrue($this->service->hasRequiredContent(true, ''));
        $this->assertTrue($this->service->hasRequiredContent(false, ' Explication '));
        $this->assertFalse($this->service->hasRequiredContent(false, '  '));
    }

    public function testRiddleRelationshipMustMatchTarget(): void {
        $this->assertTrue($this->service->hasConsistentRiddleTarget('enigme', 12, 12));
        $this->assertFalse($this->service->hasConsistentRiddleTarget('enigme', 12, 24));
        $this->assertFalse($this->service->hasConsistentRiddleTarget('enigme', 12, 0));
        $this->assertTrue($this->service->hasConsistentRiddleTarget('chasse', 12, 0));
    }

    public function testScheduleSupportsOnlyDeferredOrEndOfHuntAvailability(): void {
        $this->assertSame(
            [
                'availability' => 'differee',
                'delay_days' => 3,
                'publication_time' => '08:30',
            ],
            $this->service->normalizeSchedule('differee', 3, '08:30')
        );
        $this->assertSame(
            [
                'availability' => 'fin_chasse',
                'delay_days' => -2,
                'publication_time' => '00:00',
            ],
            $this->service->normalizeSchedule('inconnue', -2, '')
        );
    }
}
