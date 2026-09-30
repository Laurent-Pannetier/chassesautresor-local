<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintDeletionLifecycleService;
use ChassesAuTresor\Core\Content\HintDeletionService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionLifecycleService.php';

final class HintDeletionLifecycleServiceTest extends TestCase {
    private HintDeletionLifecycleService $service;

    protected function setUp(): void {
        $deletionService = new HintDeletionService(new RelationshipService());
        $this->service = new HintDeletionLifecycleService($deletionService);
    }

    public function testCapturesRiddleAndHuntTargetsBeforeDeletion(): void {
        $context = $this->service->capture(
            'indice',
            'enigme',
            null,
            55,
            static fn (int $riddleId): int => $riddleId === 55 ? 77 : 0
        );

        $this->assertSame([
            'reorder_targets' => [
                ['id' => 55, 'type' => 'enigme'],
                ['id' => 77, 'type' => 'chasse'],
            ],
        ], $context);
    }

    public function testIgnoresOtherPostTypesAndMissingTargets(): void {
        $resolver = static fn (): int => 77;

        $this->assertNull($this->service->capture('post', 'chasse', 44, null, $resolver));
        $this->assertNull($this->service->capture('indice', 'enigme', null, null, $resolver));
    }

    public function testRestoresOnlyValidNormalizedTargets(): void {
        $targets = $this->service->restoreTargets([
            'reorder_targets' => [
                ['id' => '55', 'type' => 'enigme'],
                ['id' => 77, 'type' => 'unexpected'],
                ['id' => 0, 'type' => 'chasse'],
                'invalid',
            ],
        ]);

        $this->assertSame([
            ['id' => 55, 'type' => 'enigme'],
            ['id' => 77, 'type' => 'chasse'],
        ], $targets);
    }

    public function testReturnsNoTargetsForInvalidContext(): void {
        $this->assertSame([], $this->service->restoreTargets(null));
        $this->assertSame([], $this->service->restoreTargets([]));
        $this->assertSame([], $this->service->restoreTargets(['reorder_targets' => 'invalid']));
    }
}
