<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($cap)
    {
        return 'administrator' === $cap;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $nonce_name)
    {
        return true;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null)
    {
        // no-op
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        $GLOBALS['wp_send_json_success_data'] = $data;
    }
}

if (!function_exists('delete_metadata')) {
    function delete_metadata($type, $object_id, $meta_key, $meta_value = '', $delete_all = false)
    {
        $GLOBALS['delete_metadata_args'] = func_get_args();
        return true;
    }
}

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public function query($sql)
    {
        // no-op
    }
};

require_once __DIR__ . '/../inc/admin-functions.php';

class ResetStatsClearsMessagesTest extends TestCase
{
    public function test_reset_stats_clears_messages(): void
    {
        $_POST['nonce'] = 'dummy';
        cta_reset_stats();

        $this->assertSame(
            ['user', 0, '_myaccount_messages', '', true],
            $GLOBALS['delete_metadata_args']
        );
    }
}
