<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepStructureLockService;
use PHPUnit\Framework\TestCase;

final class RiddleStepStructureLockServiceTest extends TestCase {
    /** @dataProvider lockedStatusProvider */
    public function testLocksAnActiveOrFinishedHunt(string $status): void {
        $fields = [
            'enigme_chasse_associee:12' => [45],
            'chasse_cache_statut:45' => $status,
        ];

        self::assertTrue((new RiddleStepStructureLockService())->isLocked(
            12,
            static fn (string $field, int $postId) => $fields[$field . ':' . $postId] ?? null,
            static fn (): bool => false
        ));
    }

    public function testLocksAsSoonAsPlayerProgressExists(): void {
        self::assertTrue((new RiddleStepStructureLockService())->isLocked(
            12,
            static fn (): int => 45,
            static fn (): bool => true
        ));
    }

    /** @dataProvider lockedValidationStatusProvider */
    public function testLocksAValidatedHuntBeforeItStarts(string $validationStatus): void {
        $fields = [
            'enigme_chasse_associee:12' => 45,
            'chasse_cache_statut:45' => 'a_venir',
            'chasse_cache_statut_validation:45' => $validationStatus,
        ];

        self::assertTrue((new RiddleStepStructureLockService())->isLocked(
            12,
            static fn (string $field, int $postId) => $fields[$field . ':' . $postId] ?? null,
            static fn (): bool => false
        ));
    }

    public function testLeavesPreparationStatusesEditable(): void {
        $fields = [
            'enigme_chasse_associee:12' => 45,
            'chasse_cache_statut:45' => 'revision',
        ];

        self::assertFalse((new RiddleStepStructureLockService())->isLocked(
            12,
            static fn (string $field, int $postId) => $fields[$field . ':' . $postId] ?? null,
            static fn (): bool => false
        ));
    }

    public function testLeavesCorrectionStatusEditableWithoutProgress(): void {
        $fields = [
            'enigme_chasse_associee:12' => 45,
            'chasse_cache_statut:45' => 'a_venir',
            'chasse_cache_statut_validation:45' => 'correction',
        ];

        self::assertFalse((new RiddleStepStructureLockService())->isLocked(
            12,
            static fn (string $field, int $postId) => $fields[$field . ':' . $postId] ?? null,
            static fn (): bool => false
        ));
    }

    /** @return array<string, array{string}> */
    public function lockedStatusProvider(): array {
        return [
            'active' => ['en_cours'],
            'paid active' => ['payante'],
            'finished' => ['termine'],
        ];
    }

    /** @return array<string, array{string}> */
    public function lockedValidationStatusProvider(): array {
        return [
            'pending validation' => ['en_attente'],
            'validated' => ['valide'],
        ];
    }
}
