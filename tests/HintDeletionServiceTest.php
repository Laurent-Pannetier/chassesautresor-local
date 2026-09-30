<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintDeletionService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionService.php';

final class HintDeletionServiceTest extends TestCase {
    private HintDeletionService $service;

    protected function setUp(): void {
        $this->service = new HintDeletionService(new RelationshipService());
    }

    public function testResolvesHuntTargetWithoutCallingRiddleResolver(): void {
        $resolverCalled = false;

        $context = $this->service->resolveContext(
            'chasse',
            [44],
            null,
            static function () use (&$resolverCalled): int {
                $resolverCalled = true;

                return 99;
            }
        );

        $this->assertFalse($resolverCalled);
        $this->assertSame([
            'target_id' => 44,
            'target_type' => 'chasse',
            'hunt_id' => 44,
            'reorder_targets' => [['id' => 44, 'type' => 'chasse']],
        ], $context);
    }

    public function testResolvesRiddleAndHuntReorderingTargets(): void {
        $context = $this->service->resolveContext(
            'enigme',
            null,
            [(object) ['ID' => 55]],
            static fn (int $riddleId): int => $riddleId === 55 ? 77 : 0
        );

        $this->assertSame(55, $context['target_id']);
        $this->assertSame(77, $context['hunt_id']);
        $this->assertSame([
            ['id' => 55, 'type' => 'enigme'],
            ['id' => 77, 'type' => 'chasse'],
        ], $context['reorder_targets']);
    }

    public function testPrefersStoredHuntAndRejectsMissingTarget(): void {
        $context = $this->service->resolveContext(
            'enigme',
            88,
            55,
            static fn (): int => 77
        );

        $this->assertSame(88, $context['hunt_id']);
        $this->assertNull($this->service->resolveContext('enigme', 88, null, static fn (): int => 77));
    }

    public function testDeletesOnlyHintPostsPermanently(): void {
        $deleted = [];
        $deletePost = static function (int $postId, bool $forceDelete) use (&$deleted): object {
            $deleted[] = [$postId, $forceDelete];

            return (object) ['ID' => $postId];
        };

        $this->assertFalse($this->service->delete(12, static fn (): string => 'post', $deletePost));
        $this->assertTrue($this->service->delete(13, static fn (): string => 'indice', $deletePost));
        $this->assertSame([[13, true]], $deleted);
    }

    public function testReportsDeletionFailure(): void {
        $this->assertFalse($this->service->delete(
            13,
            static fn (): string => 'indice',
            static fn () => false
        ));
    }
}
