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
        self::assertStringContainsString('findContext($image_id)', $source);
        self::assertStringContainsString('canView($image_id, $current_user_id)', $source);
        self::assertStringNotContainsString('$enigme_id ? null : $step_image_service->findContext', $source);
        self::assertStringNotContainsString('Cache-Control: public', $source);
        self::assertStringNotContainsString('no-store', $source);
    }

    public function testGalleryOpensOnFirstPageAndSecuresStepImages(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php'
        );

        self::assertStringContainsString('$activeIndex = 0;', $source);
        self::assertStringNotContainsString('$pageCount - 1', $source);
        self::assertStringContainsString('ensureProtected', $source);
    }
}
