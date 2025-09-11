<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID;

        public function __construct(int $id)
        {
            $this->ID = $id;
        }
    }
}

class SolutionPdfDisplayTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_solution_pdf_is_embedded_when_field_returns_id(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__);
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
        }
        if (!function_exists('add_filter')) {
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id)
            {
                return $key === 'solution_fichier' ? 42 : '';
            }
        }
        if (!function_exists('wp_get_attachment_url')) {
            function wp_get_attachment_url($id)
            {
                return $id === 42 ? 'https://example.com/file.pdf' : '';
            }
        }
        if (!function_exists('esc_url')) {
            function esc_url($url) { return $url; }
        }
        if (!function_exists('esc_attr__')) {
            function esc_attr__($text, $domain = null) { return $text; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($text) { return $text; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        $solution = new WP_Post(10);
        $html     = solution_contenu_html($solution);

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('https://example.com/file.pdf', $html);
    }
}
