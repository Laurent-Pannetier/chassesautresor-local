<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HintUnlockPolicy;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockPolicy.php';

final class HintUnlockPolicyTest extends TestCase {
    /** @dataProvider decisionProvider */
    public function testValidatesUnlock(array $arguments, ?string $expected): void {
        $this->assertSame($expected, (new HintUnlockPolicy())->validate(...$arguments));
    }

    public function decisionProvider(): array {
        return [
            'anonymous' => [[false, true, 10, 'indice', 5, 5], 'non_connecte'],
            'nonce' => [[true, false, 10, 'indice', 5, 5], 'invalid_nonce'],
            'id' => [[true, true, 0, 'indice', 5, 5], 'indice_invalide'],
            'CPT' => [[true, true, 10, 'post', 5, 5], 'indice_invalide'],
            'points' => [[true, true, 10, 'indice', 6, 5], 'points_insuffisants'],
            'free' => [[true, true, 10, 'indice', 0, 0], null],
            'paid' => [[true, true, 10, 'indice', 5, 5], null],
        ];
    }
}
