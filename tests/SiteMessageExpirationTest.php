<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, $value, int $expiration): void
    {
        global $transients, $current_time;
        $transients[$key] = [
            'value'   => $value,
            'expires' => $expiration > 0 ? $current_time + $expiration : 0,
        ];
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key)
    {
        global $transients, $current_time;
        if (!isset($transients[$key])) {
            return false;
        }
        $expires = $transients[$key]['expires'];
        if ($expires > 0 && $expires <= $current_time) {
            unset($transients[$key]);
            return false;
        }
        return $transients[$key]['value'];
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data)
    {
        return json_encode($data);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!class_exists('UserMessageRepository')) {
    class UserMessageRepository
    {
        public function __construct($wpdb) {}
        public function insert($userId, $message, $status, $expiresAt, $locale): void {}
        public function get($userId, $status, $expired): array
        {
            return [];
        }
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages.php';

class SiteMessageExpirationTest extends TestCase
{
    private int $now;

    protected function setUp(): void
    {
        global $current_time, $transients, $wpdb;
        $this->now    = time();
        $current_time = $this->now;
        $transients   = [];
        $_SESSION     = [];
        $wpdb = new class {
            public string $prefix   = 'wp_';
            public int $insert_id   = 0;

            public function insert(string $table, array $data, array $format): void
            {
                $this->insert_id++;
            }

            public function get_results(string $sql, $output): array
            {
                return [];
            }

            public function query(string $sql): void
            {
            }
        };
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_persistent_message_expires_after_duration(): void
    {
        add_site_message('info', 'Hello', true, null, null, DAY_IN_SECONDS * 2);
        $this->assertNotFalse(get_transient('cat_site_messages'));
        $this->assertNotSame('', get_site_messages());

        global $current_time;
        $current_time += DAY_IN_SECONDS * 2 + 1;
        $this->assertSame('', get_site_messages());
    }
}
