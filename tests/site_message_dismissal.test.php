<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'timestamp' ? strtotime('2023-01-01 00:00:00') : '2023-01-01 00:00:00';
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}

if (!function_exists('wp_date')) {
    function wp_date(string $format, int $timestamp, ?DateTimeZone $timezone = null): string
    {
        $dt = new DateTime('@' . $timestamp);
        if ($timezone instanceof DateTimeZone) {
            $dt->setTimezone($timezone);
        }
        return $dt->format($format);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options);
    }
}

$cat_test_transients = [];
if (!function_exists('get_transient')) {
    function get_transient($key)
    {
        global $cat_test_transients;
        return $cat_test_transients[$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0)
    {
        global $cat_test_transients;
        $cat_test_transients[$key] = $value;
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($key)
    {
        global $cat_test_transients;
        unset($cat_test_transients[$key]);
        return true;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
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

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data, $status_code = 400)
    {
        // no-op for tests
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        // no-op for tests
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';

class SiteMessageDismissalTest extends TestCase
{
    private DummyWpdb $wpdb;

    protected function setUp(): void
    {
        global $wpdb, $cat_test_transients;
        $this->wpdb = new DummyWpdb();
        $wpdb       = $this->wpdb;
        $cat_test_transients = [];
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function test_dismiss_site_message_removes_it(): void
    {
        add_site_message('info', 'Hello', true, 'global_key');
        $this->assertStringContainsString('Hello', get_site_messages());

        $_POST['key'] = 'global_key';
        ca_dismiss_message();

        $this->assertSame('', get_site_messages());
    }
}

class DummyWpdb
{
    public string $prefix = 'wp_';
    public int $insert_id = 0;
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $data = [];

    public function insert(string $table, array $data, array $format): void
    {
        $this->insert_id++;
        $data['id'] = $this->insert_id;
        $this->data[$this->insert_id] = $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_results(string $sql, $output)
    {
        return array_values($this->data);
    }

    public function delete(string $table, array $where, array $whereFormat): void
    {
        unset($this->data[$where['id']]);
    }

    public function query(string $sql): void
    {
        if (str_contains($sql, 'DELETE FROM')) {
            $now = current_time('mysql');
            $this->data = array_filter(
                $this->data,
                fn($r) => $r['expires_at'] === null || $r['expires_at'] >= $now
            );
        }
    }
}
