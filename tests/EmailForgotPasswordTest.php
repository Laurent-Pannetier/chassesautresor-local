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
if (!function_exists('network_site_url')) {
    function network_site_url($path = '', $scheme = null) {
        return 'https://example.com/' . ltrim($path, '/');
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com/' . ltrim($path, '/');
    }
}

require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/emails/template.php';
require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/emails/forgot-password.php';

class EmailForgotPasswordTest extends TestCase
{
    public function test_custom_forgot_password_email(): void
    {
        $user = (object) [
            'user_login'   => 'test4',
            'display_name' => 'test4',
            'user_email'   => 'test4@example.com',
        ];

        $email = cta_retrieve_password_notification_email([], 'abc123', 'test4', $user);

        $this->assertSame('Réinitialisation de votre mot de passe', $email['subject']);
        $this->assertStringContainsString('Réinitialiser mon mot de passe', $email['message']);
        $this->assertStringContainsString('abc123', $email['message']);
        $this->assertIsArray($email['headers']);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $email['headers']);
    }

    public function test_forgot_password_email_handles_string_headers(): void
    {
        $user = (object) [
            'display_name' => 'test4',
            'user_email'   => 'test4@example.com',
        ];

        $email = [ 'headers' => 'From: admin@example.com' ];
        $email = cta_retrieve_password_notification_email($email, 'abc123', 'test4', $user);

        $this->assertContains('From: admin@example.com', $email['headers']);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $email['headers']);
    }
}
