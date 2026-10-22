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
        afficher_picture_vignette_enigme(123, 'Alt', ['full']);
        $html = ob_get_clean();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringContainsString('1920w', $html);
        $this->assertStringContainsString('taille=large', $html);
        $this->assertStringContainsString('1024w', $html);
        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('media=', $html);
        $this->assertStringNotContainsString(' 1x', $html);
        $this->assertStringNotContainsString(' 2x', $html);
        $this->assertGreaterThan(0, substr_count($html, 'srcset='));
    }

    public function test_medium_size_includes_thumbnail_fallback(): void
    {
        ob_start();
        afficher_picture_vignette_enigme(321, 'Alt', ['medium']);
        $html = ob_get_clean();

        $this->assertStringContainsString('taille=medium', $html);
        $this->assertStringContainsString('300w', $html);
        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringNotContainsString('media=', $html);
        $this->assertStringNotContainsString('<source', $html);
    }

    public function test_thumbnail_slot_does_not_request_a_larger_file(): void
    {
        $html = build_picture_enigme(42, 'Vignette', ['thumbnail'], ['sizes' => '4.25rem']);

        $this->assertStringContainsString('taille=thumbnail', $html);
        $this->assertStringContainsString('150w', $html);
        $this->assertStringContainsString('sizes="4.25rem"', $html);
        $this->assertStringNotContainsString('taille=medium', $html);
        $this->assertStringNotContainsString('taille=large', $html);
        $this->assertStringNotContainsString(' 2x', $html);
    }

    public function test_hero_floor_skips_thumbnail_on_small_screens(): void
    {
        $html = build_picture_enigme(42, 'Planche', ['large', 'full'], ['sizes' => '100vw'], 'large');

        $this->assertStringNotContainsString('taille=thumbnail', $html);
        $this->assertStringNotContainsString('taille=medium', $html);
        $this->assertStringContainsString('taille=large', $html);
        $this->assertStringContainsString('taille=full', $html);
        $this->assertStringContainsString('sizes="100vw"', $html);
    }
}
