<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\StatisticsParticipantRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsParticipantRequestService.php';

final class StatisticsParticipantRequestServiceTest extends TestCase {
    /** @dataProvider requestProvider */
    public function testNormalizesParticipantRequest(array $arguments, array $expected): void {
        $this->assertSame($expected, (new StatisticsParticipantRequestService())->prepare(...$arguments));
    }

    public function requestProvider(): array {
        return [
            'minimums' => [[0, 0, 'invalid'], ['page' => 1, 'limit' => 1, 'offset' => 0, 'order' => 'ASC']],
            'ascending' => [[2, 25, 'asc'], ['page' => 2, 'limit' => 25, 'offset' => 25, 'order' => 'ASC']],
            'descending' => [[3, 25, 'DESC'], ['page' => 3, 'limit' => 25, 'offset' => 50, 'order' => 'DESC']],
        ];
    }
}
