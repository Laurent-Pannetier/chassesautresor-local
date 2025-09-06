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

if (!function_exists('get_posts')) {
    function get_posts($args = [])
    {
        $GLOBALS['get_posts_args'] = $args;
        return [10];
    }
}

if (!function_exists('update_field')) {
    function update_field($field, $value, $post_id)
    {
        $GLOBALS['updated_fields'][] = [$field, $value, $post_id];
    }
}

if (!function_exists('delete_field')) {
    function delete_field($field, $post_id)
    {
        $GLOBALS['deleted_fields'][] = [$field, $post_id];
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

    public function test_reset_stats_resets_chasse_fields(): void
    {
        $GLOBALS['updated_fields'] = [];
        $GLOBALS['deleted_fields'] = [];
        $_POST['nonce']            = 'dummy';

        cta_reset_stats();

        $this->assertSame(
            [
                'post_type'   => 'chasse',
                'post_status' => 'any',
                'meta_query'  => [
                    [
                        'key'   => 'chasse_cache_statut',
                        'value' => 'termine',
                    ],
                ],
                'fields'   => 'ids',
                'nopaging' => true,
            ],
            $GLOBALS['get_posts_args']
        );

        $this->assertContains(
            ['chasse_cache_statut', 'en_cours', 10],
            $GLOBALS['updated_fields']
        );

        $this->assertContains(
            ['chasse_cache_gagnants', 10],
            $GLOBALS['deleted_fields']
        );

        $this->assertContains(
            ['chasse_cache_date_decouverte', 10],
            $GLOBALS['deleted_fields']
        );
    }
}
