<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleImagePublicationGate;
use ChassesAuTresor\Core\Media\RiddleImagePublicUrlService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImagePublicationGate.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImagePublicUrlService.php';

final class RiddleImagePublicationGateTest extends TestCase
{
    public function testAnUnlinkedAttachmentKeepsItsPublicUrl(): void
    {
        $gate = new RiddleImagePublicationGate(
            static fn (int $imageId): bool => false,
            static function (): bool {
                self::fail('An ordinary attachment must not be access-checked.');
            }
        );

        self::assertTrue($gate->mayPublishUrl(12, 0));
        self::assertFalse($gate->mayPublishUrl(0, 4));
    }

    public function testALockedStepImagePublishesNoUrl(): void
    {
        $gate = new RiddleImagePublicationGate(
            static fn (int $imageId): bool => true,
            static fn (int $imageId, int $userId): bool => false
        );
        $urls = new RiddleImagePublicUrlService(
            static fn (): string => 'https://example.test/uploads/_enigmes/enigme-3/secret.jpg'
        );

        self::assertFalse($gate->mayPublishUrl(9, 4));
        $published = $gate->mayPublishUrl(9, 4) ? $urls->url(9, 'full') : '';
        self::assertSame('', $published);
    }

    public function testAnAllowedImageUsesTheStaticFileIncludingWebp(): void
    {
        $gate = new RiddleImagePublicationGate(
            static fn (int $imageId): bool => true,
            static fn (int $imageId, int $userId): bool => $userId === 7
        );
        $urls = new RiddleImagePublicUrlService(
            static function (int $imageId, string $size, string $format): string {
                $extension = $format === 'webp' ? 'webp' : 'jpg';

                return 'https://example.test/uploads/_enigmes/enigme-3/' . $size . '.' . $extension;
            }
        );

        self::assertTrue($gate->mayPublishUrl(9, 7));
        self::assertSame(
            'https://example.test/uploads/_enigmes/enigme-3/enigme-1280.jpg',
            $urls->url(9, 'enigme-1280')
        );
        self::assertSame(
            'https://example.test/uploads/_enigmes/enigme-3/enigme-1600.webp',
            $urls->url(9, 'enigme-1600', 'webp')
        );
        self::assertStringNotContainsString('voir-image-enigme', $urls->url(9, 'full', 'webp'));
    }

    public function testLinkedImagesStillUseTheRenderAccessChecks(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImagePublicationGate.php'
        );

        self::assertStringContainsString('canViewRiddle(', $source);
        self::assertStringContainsString('->canView(', $source);
        self::assertStringContainsString('findContext(', $source);
    }
}
