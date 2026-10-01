<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptListRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListRequestService.php';

final class RiddleAttemptListRequestServiceTest extends TestCase {
    /** @dataProvider requestProvider */
    public function testPreparesListRequest(array $arguments, array $expected): void {
        $this->assertSame($expected, (new RiddleAttemptListRequestService())->prepare(...$arguments));
    }

    public function requestProvider(): array {
        return [
            'anonymous' => [
                [false, 12, 'enigme', false, 1],
                ['error' => 'non_connecte', 'page' => 1, 'per_page' => 20, 'offset' => 0],
            ],
            'wrong CPT' => [
                [true, 12, 'post', true, 1],
                ['error' => 'post_invalide', 'page' => 1, 'per_page' => 20, 'offset' => 0],
            ],
            'denied' => [
                [true, 12, 'enigme', false, 1],
                ['error' => 'acces_refuse', 'page' => 1, 'per_page' => 20, 'offset' => 0],
            ],
            'normalized page' => [
                [true, 12, 'enigme', true, -2],
                ['error' => null, 'page' => 1, 'per_page' => 20, 'offset' => 0],
            ],
            'pagination' => [
                [true, 12, 'enigme', true, 3],
                ['error' => null, 'page' => 3, 'per_page' => 20, 'offset' => 40],
            ],
        ];
    }
}
