<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\UserProgressPaginationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserProgressPaginationService.php';

final class UserProgressPaginationServiceTest extends TestCase {
    /** @dataProvider requestProvider */
    public function testPreparesRequest(array $arguments, array $expected): void {
        $this->assertSame($expected, (new UserProgressPaginationService())->prepare(...$arguments));
    }

    public function requestProvider(): array {
        return [
            'anonymous' => [[false, 0, 2, 10], ['allowed' => false, 'page' => 2, 'per_page' => 10]],
            'missing user' => [[true, 0, 2, 10], ['allowed' => false, 'page' => 2, 'per_page' => 10]],
            'minimums' => [[true, 7, 0, 0], ['allowed' => true, 'page' => 1, 'per_page' => 1]],
            'valid' => [[true, 7, 3, 20], ['allowed' => true, 'page' => 3, 'per_page' => 20]],
        ];
    }
}
