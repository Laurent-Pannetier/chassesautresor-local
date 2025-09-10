<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return $text; }
}
if (!function_exists('site_url')) {
    function site_url($path = '') { return 'https://example.com' . $path; }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
}
if (!function_exists('get_field')) {
    function get_field($key, $id, $format = true) { return [$id]; }
}
if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() { return __DIR__; }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmeImageDimensionsTest extends TestCase
{
    public function test_picture_contains_breakpoint_sources(): void
    {
        ob_start();
        afficher_picture_vignette_enigme(123, 'Alt', ['medium', 'large', 'full']);
        $html = ob_get_clean();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringContainsString('media="(min-width: 1025px)"', $html);
        $this->assertStringContainsString('taille=large', $html);
        $this->assertStringContainsString('media="(min-width: 769px)"', $html);
        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringNotContainsString('media="(min-width: 481px)"', $html);
        $this->assertSame(2, substr_count($html, '<source'));
    }

    public function test_single_size_outputs_only_img(): void
    {
        ob_start();
        afficher_picture_vignette_enigme(321, 'Alt', ['medium']);
        $html = ob_get_clean();

        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringNotContainsString('<source', $html);
    }
}
