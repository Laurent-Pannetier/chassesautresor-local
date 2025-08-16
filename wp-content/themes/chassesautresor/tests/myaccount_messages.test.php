<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('est_organisateur')) {
    function est_organisateur()
    {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}

if (!function_exists('get_organisateur_from_user')) {
    function get_organisateur_from_user($user_id)
    {
        return 99;
    }
}

if (!function_exists('recuperer_enigmes_tentatives_en_attente')) {
    function recuperer_enigmes_tentatives_en_attente($organisateur_id)
    {
        return [];
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single)
    {
        return [
            [
                'statut' => 'en attente',
            ],
        ];
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        return 'https://example.com/organisateur';
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '')
    {
        $query = http_build_query($args);
        $sep = strpos($url, '?') === false ? '?' : '&';
        return $url . $sep . $query;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

require_once __DIR__ . '/../inc/user-functions.php';

class MyAccountMessagesTest extends TestCase
{
    public function test_conversion_request_message_contains_link(): void
    {
        $output = myaccount_get_important_messages();

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString('?edition=open&onglet=revenus', $output);
        $this->assertStringContainsString('demande de conversion', $output);
    }
}
