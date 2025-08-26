<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() { return true; }
}
if (!function_exists('get_post_type')) {
    function get_post_type($id) { return $id === 3 ? 'chasse' : 'enigme'; }
}
if (!function_exists('indice_action_autorisee')) {
    function indice_action_autorisee($action, $type, $id) { return true; }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) { throw new Exception((string) $data); }
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $json_success_data;
        $json_success_data = $data;
        return $data;
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return $key; }
}
if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($id) { return [5,6]; }
}
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public $max_num_pages = 1;
        public function __construct($args) {
            global $captured_query_args;
            $captured_query_args = $args;
            $this->posts = [];
            $this->max_num_pages = 1;
        }
    }
}
if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = []) { echo 'table'; }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

class IndicesListerTableChasseTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        global $captured_query_args, $json_success_data;
        $captured_query_args = [];
        $json_success_data   = null;
        $_POST = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_query_includes_riddle_indices_for_hunt(): void {
        global $captured_query_args, $json_success_data;

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
            'page'       => 1,
        ];

        ajax_indices_lister_table();

        $meta = $captured_query_args['meta_query'];
        $this->assertSame(5, $captured_query_args['posts_per_page']);
        $this->assertSame('OR', $meta['relation']);
        $this->assertSame('AND', $meta[0]['relation']);
        $this->assertSame('indice_chasse_linked', $meta[0][1]['key']);
        $this->assertSame(3, $meta[0][1]['value']);
        $this->assertSame('AND', $meta[1]['relation']);
        $this->assertSame('enigme', $meta[1][0]['value']);
        $this->assertSame([5,6], $meta[1][1]['value']);
        $this->assertSame('IN', $meta[1][1]['compare']);
        $this->assertIsArray($json_success_data);
    }
}
