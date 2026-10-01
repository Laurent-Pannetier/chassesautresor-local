<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-attempt-functions.php';

final class RiddleAttemptFunctionsTest extends TestCase {
    /** @dataProvider modeProvider */
    public function testNormalizesValidationModes($value, string $expected): void {
        self::assertSame($expected, enigme_normaliser_mode_validation($value));
    }

    public function modeProvider(): array {
        return [
            'acf choice' => [['value' => 'manuelle'], 'manuelle'],
            'none' => ['none', 'aucune'],
            'empty' => [null, 'aucune'],
            'trimmed' => ['  Automatique ', 'automatique'],
        ];
    }
}
