<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintMutationService;
use ChassesAuTresor\Core\Content\HintStatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintStatusService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintMutationService.php';

final class HintMutationServiceTest extends TestCase {
    public function testCreationPreservesEmptyOptionalFieldsAndNormalizesSchedule(): void {
        $updates = [];
        $deletions = [];
        $refreshedHintId = null;
        $service = new HintMutationService(new HintStatusService());

        $result = $service->applyModal(
            42,
            0,
            '',
            'unsupported',
            '',
            '',
            '2026-09-30 12:00:00',
            false,
            static function (string $field, $value, int $hintId) use (&$updates): void {
                $updates[] = [$field, $value, $hintId];
            },
            static function (string $field, int $hintId) use (&$deletions): void {
                $deletions[] = [$field, $hintId];
            },
            static function (int $hintId) use (&$refreshedHintId): void {
                $refreshedHintId = $hintId;
            }
        );

        $this->assertSame([
            ['indice_disponibilite', 'immediate', 42],
            ['indice_date_disponibilite', '2026-09-30 12:00:00', 42],
        ], $updates);
        $this->assertSame([], $deletions);
        $this->assertSame(42, $refreshedHintId);
        $this->assertSame([
            'availability' => 'immediate',
            'availability_date' => '2026-09-30 12:00:00',
        ], $result);
    }

    public function testEditionReplacesContentAndRemovesMissingImage(): void {
        $updates = [];
        $deletions = [];
        $service = new HintMutationService(new HintStatusService());

        $result = $service->applyModal(
            81,
            0,
            '',
            'differe',
            '2026-10-05 18:30:00',
            '2026-10-01 18:00:00',
            '2026-09-30 12:00:00',
            true,
            static function (string $field, $value, int $hintId) use (&$updates): void {
                $updates[] = [$field, $value, $hintId];
            },
            static function (string $field, int $hintId) use (&$deletions): void {
                $deletions[] = [$field, $hintId];
            },
            static function (int $hintId): void {
            }
        );

        $this->assertSame([['indice_image', 81]], $deletions);
        $this->assertSame([
            ['indice_contenu', '', 81],
            ['indice_disponibilite', 'differe', 81],
            ['indice_date_disponibilite', '2026-10-05 18:30:00', 81],
        ], $updates);
        $this->assertSame('differe', $result['availability']);
    }

    public function testCreationPersistsSubmittedImageAndContent(): void {
        $updates = [];
        $service = new HintMutationService(new HintStatusService());

        $service->applyModal(
            13,
            99,
            '<p>Indice</p>',
            'immediate',
            '',
            '2026-10-01 10:00:00',
            '2026-09-30 12:00:00',
            false,
            static function (string $field, $value, int $hintId) use (&$updates): void {
                $updates[] = [$field, $value, $hintId];
            },
            static function (): void {
            },
            static function (): void {
            }
        );

        $this->assertSame(['indice_image', 99, 13], $updates[0]);
        $this->assertSame(['indice_contenu', '<p>Indice</p>', 13], $updates[1]);
        $this->assertSame(['indice_date_disponibilite', '2026-10-01 10:00:00', 13], $updates[3]);
    }
}
