<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SommaireVignetteInlineTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInlineMarkupReplacesTheProxy(): void
    {
        $this->boot();
        $GLOBALS['sommaire_inline_thumbnail'] = '<img src="data:image/png;base64,abc" alt="">';

        $html = enigme_sommaire_vignette_html(501, '');

        self::assertSame('<img src="data:image/png;base64,abc" alt="">', $html);
        self::assertStringNotContainsString('voir-image-enigme', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEmptyInlineFallsBackToAnEagerProxyThumbnail(): void
    {
        $this->boot();
        $GLOBALS['sommaire_inline_thumbnail'] = '';

        $html = enigme_sommaire_vignette_html(501, 'Vignette');

        self::assertStringContainsString('voir-image-enigme', $html);
        self::assertStringContainsString('taille=thumbnail', $html);
        self::assertStringContainsString('sizes="4.25rem"', $html);
        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringNotContainsString('loading="lazy"', $html);
        self::assertStringNotContainsString('taille=medium', $html);
        self::assertStringNotContainsString('data:image', $html);
    }

    private function boot(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        if (!function_exists('esc_url')) {
            eval('function esc_url($url) { return $url; }');
        }
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($text) { return $text; }');
        }
        if (!function_exists('site_url')) {
            eval('function site_url($path = "") { return "https://example.com" . $path; }');
        }
        if (!function_exists('add_query_arg')) {
            eval('function add_query_arg($args, $url) { return $url . "?" . http_build_query($args); }');
        }
        if (!function_exists('cta_inline_riddle_thumbnail')) {
            eval('function cta_inline_riddle_thumbnail($imageId, $alt = "") {'
                . ' return (string) ($GLOBALS["sommaire_inline_thumbnail"] ?? ""); }');
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';
    }
}
