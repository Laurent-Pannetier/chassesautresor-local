<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountSectionAccessService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountSectionAccessService.php';

final class AccountSectionAccessServiceTest extends TestCase {
    /** @dataProvider decisionProvider */
    public function testResolvesSectionAccess(array $arguments, array $expected): void {
        $this->assertSame($expected, (new AccountSectionAccessService())->resolve(...$arguments));
    }

    public function decisionProvider(): array {
        return [
            'anonymous' => [
                [false, 'outils', true],
                ['error' => 'unauthorized', 'status' => 403, 'template' => ''],
            ],
            'unknown' => [
                [true, 'unknown', true],
                ['error' => 'not_found', 'status' => 404, 'template' => ''],
            ],
            'non admin' => [
                [true, 'outils', false],
                ['error' => 'unauthorized', 'status' => 403, 'template' => ''],
            ],
            'organizers' => [
                [true, 'organisateurs', true],
                ['error' => null, 'status' => 200, 'template' => 'content-organisateurs.php'],
            ],
            'statistics' => [
                [true, 'statistiques', true],
                ['error' => null, 'status' => 200, 'template' => 'content-statistiques.php'],
            ],
            'tools' => [
                [true, 'outils', true],
                ['error' => null, 'status' => 200, 'template' => 'content-outils.php'],
            ],
        ];
    }
}
