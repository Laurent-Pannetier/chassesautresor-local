<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintRelationshipService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRelationshipService.php';

final class HintRelationshipServiceTest extends TestCase {
    private HintRelationshipService $service;

    protected function setUp(): void {
        $this->service = new HintRelationshipService(new RelationshipService());
    }

    public function testHuntTargetUsesRequestedHuntWithoutResolvingRiddle(): void {
        $resolverCalled = false;

        $huntId = $this->service->resolveLinkedHuntId(
            'chasse',
            12,
            ['ID' => 44],
            static function () use (&$resolverCalled): int {
                $resolverCalled = true;

                return 99;
            }
        );

        $this->assertSame(44, $huntId);
        $this->assertFalse($resolverCalled);
    }

    public function testRiddleTargetResolvesItsHuntFromNormalizedRelationship(): void {
        $resolvedRiddleId = null;

        $huntId = $this->service->resolveLinkedHuntId(
            'enigme',
            [(object) ['ID' => 55]],
            44,
            static function (int $riddleId) use (&$resolvedRiddleId): array {
                $resolvedRiddleId = $riddleId;

                return ['ID' => 77];
            }
        );

        $this->assertSame(55, $resolvedRiddleId);
        $this->assertSame(77, $huntId);
    }

    public function testInvalidTargetAndMissingRiddleDoNotResolveHunt(): void {
        $resolver = static fn (): int => 77;

        $this->assertNull($this->service->resolveLinkedHuntId('solution', 55, 44, $resolver));
        $this->assertNull($this->service->resolveLinkedHuntId('enigme', null, 44, $resolver));
    }

    public function testPersistsOnlyValidLinkedHunt(): void {
        $updates = [];
        $updateField = static function (string $field, int $value, int $postId) use (&$updates): bool {
            $updates[] = [$field, $value, $postId];

            return true;
        };

        $this->assertFalse($this->service->persistLinkedHunt(10, null, $updateField));
        $this->assertTrue($this->service->persistLinkedHunt(10, 44, $updateField));
        $this->assertSame([['indice_chasse_linked', 44, 10]], $updates);
    }

    public function testNormalizesStoredHuntRelationship(): void {
        $this->assertSame(44, $this->service->normalizeHuntId([(object) ['ID' => 44]]));
        $this->assertNull($this->service->normalizeHuntId(''));
    }
}
