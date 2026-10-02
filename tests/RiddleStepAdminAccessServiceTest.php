<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepAdminAccessService;
use PHPUnit\Framework\TestCase;

final class RiddleStepAdminAccessServiceTest extends TestCase {
    /** @dataProvider relationshipProvider */
    public function testAllowsAValidStepWhenItsParentRiddleCanBeModified($relationship): void {
        $types = [80 => 'enigme_etape', 42 => 'enigme'];
        $service = new RiddleStepAdminAccessService();

        self::assertTrue($service->canEdit(
            80,
            static fn (int $id): string => $types[$id] ?? '',
            static fn () => $relationship,
            static fn (int $id): bool => $id === 42
        ));
    }

    public function relationshipProvider(): array {
        return [
            'integer ID' => [42],
            'array ID' => [[42]],
            'post object' => [(object) ['ID' => 42]],
        ];
    }

    public function testRejectsUnrelatedInvalidOrForbiddenContent(): void {
        $service = new RiddleStepAdminAccessService();
        $postType = static fn (int $id): string => $id === 80 ? 'enigme_etape' : 'post';

        self::assertFalse($service->canEdit(0, $postType, static fn () => 42, static fn (): bool => true));
        self::assertFalse($service->canEdit(80, $postType, static fn () => 0, static fn (): bool => true));
        self::assertFalse($service->canEdit(80, $postType, static fn () => 42, static fn (): bool => true));
        self::assertFalse($service->canEdit(
            80,
            static fn (int $id): string => $id === 80 ? 'enigme_etape' : 'enigme',
            static fn () => 42,
            static fn (): bool => false
        ));
    }
}
