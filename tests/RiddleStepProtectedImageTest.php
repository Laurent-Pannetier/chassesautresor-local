<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiddleStepProtectedImageTest extends TestCase {
    public function testPlayerTemplateDoesNotRenderTheDirectAttachmentUrl(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/'
            . 'template-parts/enigme/partials/enigme-partial-etapes-joueur.php'
        );

        self::assertStringContainsString('data-step-page-preview', $source);
        self::assertStringContainsString('data-revealed-step-id', $source);
        self::assertStringContainsString('enigme_step_comic_page_view', $source);
        self::assertStringNotContainsString('riddle-player-step__image', $source);
        self::assertStringNotContainsString('wp_get_attachment_image(', $source);
        self::assertStringNotContainsString("get_field('etape_image'", $source);
    }

    public function testUnlockedPagesStayOutOfHtmlAndFilesUseAPublicCache(): void {
        $gate = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImagePublicationGate.php'
        );
        $rules = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionService.php'
        );
        $routes = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedAssetRouteHandler.php'
        );

        self::assertStringContainsString('canViewRiddle(', $gate);
        self::assertStringContainsString('->canView(', $gate);
        self::assertStringContainsString('findContext(', $gate);
        self::assertStringContainsString('PUBLIC_CACHE_CONTROL', $rules);
        self::assertStringContainsString('public, max-age=31536000, immutable', $rules);
        self::assertStringNotContainsString('Cache-Control: private', $rules);
        self::assertStringNotContainsString('voir-image-enigme', $routes);
        self::assertStringNotContainsString('X-LiteSpeed-Location', $rules);
        self::assertFileDoesNotExist(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );
    }

    public function testGalleryOpensOnFirstPageAndSecuresStepImages(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php'
        );

        self::assertStringContainsString('$activeIndex = 0;', $source);
        self::assertStringNotContainsString('$pageCount - 1', $source);
        self::assertStringNotContainsString('ensureProtected', $source);
        self::assertStringNotContainsString('update_field', $source);
        $storage = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStepImageStorageService.php'
        );
        $hook = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleStepImageSaveHookHandler.php'
        );
        self::assertStringContainsString('function persistProtectedGallery', $storage);
        self::assertStringContainsString('ensureProtected', $storage);
        self::assertStringContainsString("'acf/save_post'", $hook);
        self::assertStringContainsString('persistProtectedGallery', $hook);
        self::assertStringContainsString("completed_step_ids", $source);
        self::assertStringContainsString('cta_voir_image_enigme_url', $source);
        self::assertStringContainsString('enigme_direct_image_url', $source);
        self::assertStringNotContainsString('voir-image-enigme', $source);
        self::assertStringNotContainsString('visible_step_ids', $source);
    }
}
