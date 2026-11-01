<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepGallery;
use PHPUnit\Framework\TestCase;

final class RiddleStepGalleryTest extends TestCase {
    public function testNormalizesLegacyIdGalleryRowsAndDuplicates(): void {
        self::assertSame([12], RiddleStepGallery::normalize(12));
        self::assertSame([12], RiddleStepGallery::normalize('12'));
        self::assertSame([], RiddleStepGallery::normalize(0));
        self::assertSame([], RiddleStepGallery::normalize(false));
        self::assertSame([4, 9], RiddleStepGallery::normalize([4, '9', 4, 0]));
        self::assertSame([7], RiddleStepGallery::normalize([['ID' => 7], ['id' => 7]]));
    }

    public function testReadsFormattedIdsBeforeRawMeta(): void {
        $reads = [];
        $ids = RiddleStepGallery::idsForStep(
            8,
            static function (string $field, int $stepId) use (&$reads): array {
                $reads[] = [$field, $stepId];

                return [3, 5];
            }
        );

        self::assertSame([3, 5], $ids);
        self::assertSame([['etape_image', 8]], $reads);
    }
}
