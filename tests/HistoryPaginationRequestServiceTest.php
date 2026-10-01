<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\HistoryPaginationRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/HistoryPaginationRequestService.php';

final class HistoryPaginationRequestServiceTest extends TestCase {
    /** @dataProvider paginationProvider */
    public function testPreparesHistoryPagination(array $arguments, array $expected): void {
        $this->assertSame($expected, (new HistoryPaginationRequestService())->prepare(...$arguments));
    }

    public function paginationProvider(): array {
        return [
            'anonymous' => [
                [false, 2, 20],
                ['allowed' => false, 'page' => 2, 'per_page' => 20, 'offset' => 20],
            ],
            'minimums' => [
                [true, 0, 0],
                ['allowed' => true, 'page' => 1, 'per_page' => 1, 'offset' => 0],
            ],
            'page three' => [
                [true, 3, 10],
                ['allowed' => true, 'page' => 3, 'per_page' => 10, 'offset' => 20],
            ],
        ];
    }
}
