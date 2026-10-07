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
        self::assertStringContainsString('cta_voir_image_enigme_url', $source);
        self::assertStringContainsString('data-step-page-preview', $source);
        self::assertStringNotContainsString('riddle-player-step__image', $source);
        self::assertStringNotContainsString('wp_get_attachment_image($imageId', $source);
    }

    public function testProtectedControllerUsesProgressAccessAndPrivateCache(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );

        self::assertStringContainsString('riddleStepImages($wpdb)', $source);
        self::assertStringContainsString(
            "header('Cache-Control: private, max-age=120, must-revalidate')",
            $source
        );
        self::assertStringContainsString('http_response_code(304)', $source);
        self::assertStringContainsString('tryLiteSpeedSend', $source);
        self::assertStringContainsString('ProtectedRiddleImageSignedUrlService', $source);
        self::assertStringContainsString('Lien image invalide ou expiré', $source);
        self::assertStringNotContainsString('Cache-Control: public', $source);
        self::assertStringNotContainsString('no-store', $source);
    }
}
