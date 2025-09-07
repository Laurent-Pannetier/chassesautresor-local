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
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = null) {
        return $text;
    }
}
if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri($path) {
        return 'https://example.com/' . ltrim($path, '/');
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com/' . ltrim($path, '/');
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        return $value;
    }
}
if (!function_exists('wp_encode_mime_header')) {
    function wp_encode_mime_header($str) {
        return $str;
    }
}
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = []) {
        $GLOBALS['cta_last_email'] = [
            'to'      => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];
        return true;
    }
}

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/emails/template.php';

class EmailSendTitleTest extends TestCase
{
    public function test_header_title_excludes_bracket_prefix(): void
    {
        $subject = '[Chasses au Trésor] Confirmez votre inscription organisateur';
        $body    = '<p>Content</p>';

        cta_send_email('test@example.com', $subject, $body);

        $html = $GLOBALS['cta_last_email']['message'] ?? '';
        $this->assertStringContainsString('Confirmez votre inscription organisateur', $html);
        $this->assertStringNotContainsString('[Chasses au Trésor]', $html);
    }
}
