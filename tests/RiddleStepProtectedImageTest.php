<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiddleStepProtectedImageTest extends TestCase {
    public function testPlayerTemplateDoesNotRenderTheDirectAttachmentUrl(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/'
            . 'template-parts/enigme/partials/enigme-partial-etapes-joueur.php'
        );

        self::assertStringContainsString("site_url('/voir-image-enigme')", $source);
        self::assertStringNotContainsString('wp_get_attachment_image($imageId', $source);
    }

    public function testProtectedControllerUsesProgressAccessAndPrivateCache(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );

        self::assertStringContainsString('riddleStepImages($wpdb)', $source);
        self::assertStringContainsString("header('Cache-Control: private, no-store, max-age=0')", $source);
        self::assertStringNotContainsString('Cache-Control: public', $source);
    }
}
