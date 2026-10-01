<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HintUnlockRenderer;
use PHPUnit\Framework\TestCase;

final class HintUnlockRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersSanitizedContentAndLinkedImage(): void
    {
        function get_field(string $field, int $hintId)
        {
            return $field === 'indice_contenu' ? '<p>Contenu</p>' : 42;
        }

        function apply_filters(string $filter, string $content): string
        {
            return $content;
        }

        function wp_kses_post(string $content): string
        {
            return $content;
        }

        function wp_get_attachment_image(int $imageId, string $size): string
        {
            return '<img src="thumbnail.jpg" alt="">';
        }

        function wp_get_attachment_image_url(int $imageId, string $size): string
        {
            return 'https://example.test/full.jpg';
        }

        function esc_url(string $url): string
        {
            return $url;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockRenderer.php';

        $html = (new HintUnlockRenderer())->render(12);

        self::assertStringContainsString('class="indice-contenu"', $html);
        self::assertStringContainsString('https://example.test/full.jpg', $html);
        self::assertStringContainsString('<p>Contenu</p>', $html);
    }
}
