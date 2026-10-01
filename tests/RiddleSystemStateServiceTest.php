<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleSystemStateService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSystemStateService.php';

final class RiddleSystemStateServiceTest extends TestCase {
    /** @dataProvider stateProvider */
    public function testCalculatesSystemState(array $input, string $expected): void {
        $this->assertSame($expected, (new RiddleSystemStateService())->calculate(...$input));
    }

    /** @return array<string, array{array<int,mixed>,string}> */
    public function stateProvider(): array {
        return [
            'missing hunt' => [[false, '', 'immediat', null, 'manuelle', false, 100], 'bloquee_chasse'],
            'inactive hunt' => [[true, 'a_venir', 'immediat', null, 'manuelle', false, 100], 'bloquee_chasse'],
            'future date' => [[true, 'en_cours', 'date_programmee', 101, 'manuelle', false, 100], 'bloquee_date'],
            'prerequisites' => [[true, 'en_cours', 'pre_requis', null, 'manuelle', false, 100], 'bloquee_pre_requis'],
            'answers missing' => [[true, 'en_cours', 'immediat', null, 'automatique', false, 100], 'invalide'],
            'accessible' => [[true, 'payante', 'immediat', null, 'automatique', true, 100], 'accessible'],
        ];
    }
}
