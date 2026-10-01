<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptAccessPolicy;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptAccessPolicy.php';

final class RiddleAttemptAccessPolicyTest extends TestCase {
    /** @dataProvider accessProvider */
    public function testDecidesAttemptVisibility(array $arguments, bool $expected): void {
        $this->assertSame($expected, (new RiddleAttemptAccessPolicy())->canView(...$arguments));
    }

    public function accessProvider(): array {
        return [
            'anonymous owner' => [[0, 0, false, false], false],
            'owner' => [[7, 7, false, false], true],
            'administrator' => [[7, 8, true, false], true],
            'organizer' => [[7, 8, false, true], true],
            'unrelated user' => [[7, 8, false, false], false],
        ];
    }
}
