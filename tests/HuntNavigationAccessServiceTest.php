<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntNavigationAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntNavigationAccessService.php';

final class HuntNavigationAccessServiceTest extends TestCase {
    /** @dataProvider accessProvider */
    public function testDecidesNavigationAccess(array $arguments, bool $expected): void {
        $this->assertSame($expected, (new HuntNavigationAccessService())->canView(...$arguments));
    }

    public function accessProvider(): array {
        return [
            'anonymous' => [[0, false, false, false], false],
            'anonymous cannot use engagement' => [[0, false, false, true], false],
            'administrator' => [[0, true, false, false], true],
            'organizer' => [[7, false, true, false], true],
            'engaged player' => [[7, false, false, true], true],
            'unrelated player' => [[7, false, false, false], false],
        ];
    }
}
