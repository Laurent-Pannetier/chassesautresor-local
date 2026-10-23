<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintAccessPolicy;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintAccessPolicy.php';

final class HintAccessPolicyTest extends TestCase {
    private HintAccessPolicy $policy;

    protected function setUp(): void {
        $this->policy = new HintAccessPolicy();
    }

    public function testEveryPrerequisiteMustBeConsulted(): void {
        $closed = $this->policy->evaluate(1_000, [2, 3], 0, 'aucun', null, [2 => 100]);
        $open = $this->policy->evaluate(1_000, [2, 3], 0, 'aucun', null, [2 => 100, 3 => 200]);

        $this->assertFalse($closed['open']);
        $this->assertSame('prerequisite', $closed['reason']);
        $this->assertSame([3], $closed['missing']);
        $this->assertTrue($open['open']);
    }

    public function testDelayStartsAtTheFirstEngagement(): void {
        $waiting = $this->policy->evaluate(1_500, [], 10, 'engagement', 1_000, []);
        $beforeEngagement = $this->policy->evaluate(1_500, [], 10, 'engagement', null, []);
        $ready = $this->policy->evaluate(1_600, [], 10, 'engagement', 1_000, []);

        $this->assertFalse($waiting['open']);
        $this->assertSame('delay', $waiting['reason']);
        $this->assertSame(1_600, $waiting['available_at']);
        $this->assertFalse($beforeEngagement['open']);
        $this->assertNull($beforeEngagement['available_at']);
        $this->assertTrue($ready['open']);
    }

    public function testDelayStartsWhenTheLastPrerequisiteWasConsulted(): void {
        $consulted = [2 => 1_000, 3 => 2_000];
        $waiting = $this->policy->evaluate(2_300, [2, 3], 10, 'prerequis', null, $consulted);
        $ready = $this->policy->evaluate(2_600, [2, 3], 10, 'prerequis', null, $consulted);
        $missing = $this->policy->evaluate(9_000, [2, 3], 10, 'prerequis', null, [2 => 1_000]);

        $this->assertFalse($waiting['open']);
        $this->assertSame(2_600, $waiting['available_at']);
        $this->assertTrue($ready['open']);
        $this->assertSame('prerequisite', $missing['reason']);
        $this->assertSame([3], $missing['missing']);
    }

    public function testNoDelayAndNoPrerequisiteStaysOpen(): void {
        $gate = $this->policy->evaluate(10, [], 0, 'aucun', null, []);

        $this->assertTrue($gate['open']);
        $this->assertSame('open', $gate['reason']);
    }

    public function testNormalizeKeepsEligiblePrerequisitesAndDropsTheHintItself(): void {
        $result = $this->policy->normalize(5, [5, 8, 9, 0], [8, 4], [], 0, 'engagement');

        $this->assertNull($result['error']);
        $this->assertSame([8], $result['prerequisites']);
        $this->assertSame(0, $result['delay_minutes']);
        $this->assertSame('aucun', $result['anchor']);
    }

    public function testPrerequisiteDelayRequiresAtLeastOnePrerequisite(): void {
        $result = $this->policy->normalize(5, [], [8], [], 15, 'prerequis');

        $this->assertSame('missing_prerequisites', $result['error']);
        $this->assertSame([], $result['prerequisites']);
    }

    public function testCycleBetweenHintsIsRejected(): void {
        $result = $this->policy->normalize(5, [8], [8], [8 => [5]], 0, 'aucun');

        $this->assertSame('cycle_prerequis', $result['error']);
    }

    public function testDelayWithoutAnchorUsesTheEngagement(): void {
        $result = $this->policy->normalize(5, [8], [8], [], 90, 'inconnu');

        $this->assertNull($result['error']);
        $this->assertSame(90, $result['delay_minutes']);
        $this->assertSame('engagement', $result['anchor']);
        $this->assertSame([8], $result['prerequisites']);
    }

    public function testDelayIsCappedAtSevenDays(): void {
        $result = $this->policy->normalize(5, [], [8], [], 999999, 'engagement');

        $this->assertSame(HintAccessPolicy::MAX_DELAY_MINUTES, $result['delay_minutes']);
    }
}
