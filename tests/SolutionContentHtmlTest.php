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

final class SolutionContentHtmlTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_link_when_file_id_provided(): void
    {
        $solution = new WP_Post(10);

        if (!function_exists('get_field')) {
            function get_field($key, $post_id, $format = true) {
                return $key === 'solution_fichier' ? 55 : '';
            }
        }
        if (!function_exists('wp_get_attachment_url')) {
            function wp_get_attachment_url($id) {
                return $id === 55 ? 'https://example.com/solution.pdf' : '';
            }
        }
        if (!function_exists('get_attached_file')) {
            function get_attached_file($id) {
                return $id === 55 ? '/tmp/solution.pdf' : '';
            }
        }
        if (!function_exists('esc_url')) {
            function esc_url($url) { return $url; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) { return $text; }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = '') { return $text; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($text) { return $text; }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        $html = solution_contenu_html($solution);
        $this->assertStringContainsString('<object', $html);
        $this->assertStringContainsString('solution.pdf', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_pdf_and_text_when_both_provided(): void
    {
        $solution = new WP_Post(10);

        if (!function_exists('get_field')) {
            function get_field($key, $post_id, $format = true) {
                if ($key === 'solution_fichier') {
                    return 55;
                }
                if ($key === 'solution_explication') {
                    return 'Explication';
                }
                return '';
            }
        }
        if (!function_exists('wp_get_attachment_url')) {
            function wp_get_attachment_url($id) {
                return $id === 55 ? 'https://example.com/solution.pdf' : '';
            }
        }
        if (!function_exists('get_attached_file')) {
            function get_attached_file($id) {
                return $id === 55 ? '/tmp/solution.pdf' : '';
            }
        }
        if (!function_exists('esc_url')) {
            function esc_url($url) { return $url; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) { return $text; }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = '') { return $text; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($text) { return $text; }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        $html = solution_contenu_html($solution);
        $this->assertStringContainsString('<object', $html);
        $this->assertStringContainsString('solution.pdf', $html);
        $this->assertStringContainsString('<p>Explication</p>', $html);
    }
}
