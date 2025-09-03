<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return $url;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return $text;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('get_theme_mod')) {
    function get_theme_mod(string $name)
    {
        return 0;
    }
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url(int $id, string $size): string
    {
        return '';
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return 'Example Site';
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data)
    {
        return $data;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/emails/template.php';

class EmailTemplateTest extends TestCase
{
    public function test_template_contains_title_content_and_sections(): void
    {
        $title   = 'Mon titre';
        $content = '<p>Mon contenu</p>';

        $html = cta_render_email_template($title, $content);

        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString($content, $html);
        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<footer', $html);
        $this->assertSame(2, substr_count($html, '#0B132B'));
    }
}

