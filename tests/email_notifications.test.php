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

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string
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

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri(string $path = ''): string
    {
        return 'https://example.com/wp-content/themes/chassesautresor/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data)
    {
        return $data;
    }
}

if (!function_exists('network_site_url')) {
    function network_site_url(string $path = '', string $scheme = 'login'): string
    {
        return 'https://example.com/' . ltrim($path, '/');
    }
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        public $user_login;
        public $user_email;
        public $display_name;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/emails/template.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/emails/user-registration.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/emails/password-reset.php';

class EmailNotificationsTest extends TestCase
{
    public function test_user_registration_email_headers_are_string(): void
    {
        $user = new WP_User();
        $user->user_login   = 'test';
        $user->user_email   = 'foo@example.com';
        $user->display_name = 'Test';

        $email = [
            'to'      => '',
            'subject' => '',
            'message' => '',
            'headers' => '',
        ];

        $result = cta_new_user_notification_email($email, $user, 'Site');

        $this->assertSame('foo@example.com', $result['to']);
        $this->assertIsString($result['headers']);
        $this->assertStringContainsString('Content-Type: text/html', $result['headers']);
        $this->assertStringContainsString('Configurer mon mot de passe', $result['message']);
    }

    public function test_password_reset_email_uses_template(): void
    {
        $user = new WP_User();
        $user->user_login   = 'test';
        $user->user_email   = 'foo@example.com';
        $user->display_name = 'Test';

        $email = [
            'to'      => '',
            'subject' => '',
            'message' => '',
            'headers' => '',
        ];

        $result = cta_password_reset_notification_email($email, 'key', 'test', $user);

        $this->assertSame('foo@example.com', $result['to']);
        $this->assertStringContainsString('Réinitialiser mon mot de passe', $result['message']);
        $this->assertStringContainsString('<header', $result['message']);
        $this->assertStringContainsString('<footer', $result['message']);
        $this->assertStringContainsString('Content-Type: text/html', $result['headers']);
    }
}
