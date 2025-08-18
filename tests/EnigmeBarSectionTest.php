<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return $text; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null) { return $text; }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

class EnigmeBarSectionTest extends TestCase
{
    public function test_rates_under_threshold_display_outside(): void
    {
        $html = enigme_render_bar_section('Test', 0, 10, 'test-section');
        $this->assertStringContainsString('<span class="bar-value bar-value--outside">0%</span>', $html);
    }

    public function test_rates_over_threshold_display_inside(): void
    {
        $html = enigme_render_bar_section('Test', 60, 70, 'test-section');
        $this->assertStringNotContainsString('bar-value--outside', $html);
        $this->assertStringContainsString('60%', $html);
    }
}
