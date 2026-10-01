<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\EngagedHuntsRenderer;
use PHPUnit\Framework\TestCase;

final class EngagedHuntsRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersCardsAndPaginationWithoutThemeFunctions(): void {
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
            return $postId === 12 ? 'La chasse & le trésor' : '';
        }
        function get_permalink(int $postId): string {
            return 'https://example.test/chasse/' . $postId;
        }
        function get_post_field(string $field): string {
            return $field === 'post_excerpt' ? '<strong>Une aventure</strong>' : '';
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
        function ca_get_engaged_hunts_page_param(): string {
            return 'engaged-page';
        }
        function cta_render_pager(int $page, int $pages, string $class, array $attributes): string {
            return sprintf(
                '<nav class="%s" data-page="%d" data-pages="%d" data-param="%s"></nav>',
                $class,
                $page,
                $pages,
                $attributes['data-param']
            );
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsRenderer.php';

        $html = (new EngagedHuntsRenderer())->render([
            'ids' => [12],
            'page' => 2,
            'total_pages' => 3,
            'total_items' => 3,
        ]);

        self::assertStringContainsString('engaged-hunt-card', $html);
        self::assertStringContainsString('La chasse &amp; le trésor', $html);
        self::assertStringContainsString('Une aventure', $html);
        self::assertStringContainsString('data-param="engaged-page"', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersAnEmptyStateWithoutQueryingRecommendations(): void {
        function esc_html__($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsRenderer.php';

        $html = (new EngagedHuntsRenderer())->render([
            'ids' => [],
            'page' => 1,
            'total_pages' => 1,
            'total_items' => 0,
        ]);

        self::assertStringContainsString('Vous ne participez à aucune chasse', $html);
    }
}
