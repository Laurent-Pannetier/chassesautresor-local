<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleSidebarRequestPolicy;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSidebarRequestPolicy.php';

final class RiddleSidebarRequestPolicyTest extends TestCase {
    public function testValidatesWinnersRequest(): void {
        $policy = new RiddleSidebarRequestPolicy();
        $this->assertSame('invalid_nonce', $policy->winners(false, 4, 'enigme', 'automatique'));
        $this->assertSame('missing_enigme', $policy->winners(true, 4, 'post', 'automatique'));
        $this->assertSame('disabled', $policy->winners(true, 4, 'enigme', 'aucune'));
        $this->assertNull($policy->winners(true, 4, 'enigme', 'automatique'));
    }

    public function testValidatesProgressionAndRelationship(): void {
        $policy = new RiddleSidebarRequestPolicy();
        $valid = [true, true, 8, 'chasse', 4, 'enigme', 8];
        $this->assertNull($policy->progression(...$valid));
        $valid[0] = false;
        $this->assertSame('non_connecte', $policy->progression(...$valid));
        $valid[0] = true;
        $valid[1] = false;
        $this->assertSame('invalid_nonce', $policy->progression(...$valid));
        $valid[1] = true;
        $valid[6] = 9;
        $this->assertSame('missing_chasse', $policy->progression(...$valid));
    }
}
