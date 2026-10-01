<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntCardRenderer;
use PHPUnit\Framework\TestCase;

final class HuntCardRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersAThemeIndependentGrid(): void {
        function esc_attr($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html__($value): string {
            return esc_html($value);
        }
        function esc_url($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function get_the_title(int $postId): string {
            return 'Chasse ' . $postId;
        }
        function get_permalink(int $postId): string {
            return 'https://example.test/' . $postId;
        }
        function get_post_field(string $field): string {
            return $field === 'post_excerpt' ? 'Résumé' : '';
        }
        function wp_strip_all_tags(string $value): string {
            return strip_tags($value);
        }
        function wp_trim_words(string $value): string {
            return $value;
        }
        function get_the_post_thumbnail(): string {
            return '<img src="hunt.jpg" alt="">';
        }
        function wp_kses_post(string $value): string {
            return $value;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntCardRenderer.php';

        $html = (new HuntCardRenderer())->grid([12], 'portable-grid');

        self::assertStringContainsString('class="portable-grid"', $html);
        self::assertStringContainsString('Chasse 12', $html);
        self::assertStringContainsString('Résumé', $html);
        self::assertStringNotContainsString('get_template_part', $html);
    }
}
