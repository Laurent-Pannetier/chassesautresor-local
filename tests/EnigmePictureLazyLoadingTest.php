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

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EnigmePictureLazyLoadingTest extends TestCase
{
    public function test_build_picture_enigme_adds_lazy_loading(): void
    {
        $html = build_picture_enigme(42, 'Alt text', ['full']);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertSame(1, substr_count($html, 'loading="lazy"'));
    }

    public function test_loading_attribute_cannot_be_overridden(): void
    {
        $html = build_picture_enigme(42, 'Alt text', ['full'], ['loading' => 'eager']);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('loading="eager"', $html);
    }
}
