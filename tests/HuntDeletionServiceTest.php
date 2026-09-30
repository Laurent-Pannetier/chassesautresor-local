<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntDeletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDeletionService.php';

final class HuntDeletionServiceTest extends TestCase {
    private HuntDeletionService $service;

    protected function setUp(): void {
        $this->service = new HuntDeletionService();
    }

    /** @dataProvider invalidRequestProvider */
    public function testRejectsInvalidDeletionRequests(array $request, string $error): void {
        $this->assertSame($error, $this->service->getRequestError(...$request));
    }

    public function invalidRequestProvider(): array {
        return [
            'missing hunt' => [[0, '', '', '', false], 'id_invalide'],
            'wrong post type' => [[12, 'enigme', 'pending', 'revision', true], 'id_invalide'],
            'published hunt' => [[12, 'chasse', 'publish', 'revision', true], 'chasse_ineligible'],
            'active hunt' => [[12, 'chasse', 'pending', 'active', true], 'chasse_ineligible'],
            'unrelated user' => [[12, 'chasse', 'pending', 'revision', false], 'acces_refuse'],
        ];
    }

    public function testAllowsAssociatedUserToDeletePendingRevision(): void {
        $this->assertNull($this->service->getRequestError(12, 'chasse', 'pending', 'revision', true));
    }

    public function testTrashesChildrenAttachmentsAndHuntInOrder(): void {
        $calls = [];
        $result = $this->service->trash(
            12,
            [101, 0, '102'],
            [(object) ['ID' => 201], ['ID' => 202], null],
            function (int $postId) use (&$calls) {
                $calls[] = ['trash', $postId];
                return $postId === 12 ? (object) ['ID' => 12] : true;
            },
            function (int $riddleId) use (&$calls): void {
                $calls[] = ['files', $riddleId];
            },
            function (int $huntId) use (&$calls): void {
                $calls[] = ['sync', $huntId];
            }
        );

        $this->assertTrue($result);
        $this->assertSame([
            ['trash', 101],
            ['files', 101],
            ['trash', 102],
            ['files', 102],
            ['sync', 12],
            ['trash', 201],
            ['trash', 202],
            ['trash', 12],
        ], $calls);
    }
}
