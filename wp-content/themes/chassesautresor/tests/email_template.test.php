<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return $content;
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return $text;
    }
}
if (!function_exists('esc_url')) {
    function esc_url($text) {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null) {
        return $text;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com/' . ltrim($path, '/');
    }
}
if (!function_exists('get_theme_mod')) {
    function get_theme_mod($name) {
        return null;
    }
}
if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($id, $size = 'full') {
        return '';
    }
}
if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri($path) {
        return 'https://example.com/' . ltrim($path, '/');
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') {
        return 'Site';
    }
}

require_once __DIR__ . '/../inc/emails/template.php';

class EmailTemplateTest extends TestCase
{
    public function test_template_contains_title_content_and_sections(): void
    {
        $title = 'Titre';
        $content = '<p>Bonjour</p>';

        $html = cta_render_email_template($title, $content);

        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString($content, $html);
        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<footer', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, '#0B132B'));
    }
}
