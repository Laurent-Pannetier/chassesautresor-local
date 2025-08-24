<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {}
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool { return true; }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void { throw new Exception((string) $data); }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $json_success_data;
        $json_success_data = $data;
        return $data;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id) { return 'enigme'; }
}

if (!function_exists('indice_action_autorisee')) {
    function indice_action_autorisee($action, $type, $id) { return true; }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return false; }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($v) { return $v; }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($v) { return $v; }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($v) { return $v; }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

class AjaxCreerIndiceModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
    }

    public function test_missing_riddle_id_returns_error(): void
    {
        $_POST['objet_id'] = 7;
        $_POST['objet_type'] = 'enigme';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('post_invalide');

        ajax_creer_indice_modal();
    }

    public function test_mismatched_riddle_id_returns_error(): void
    {
        $_POST['objet_id'] = 7;
        $_POST['objet_type'] = 'enigme';
        $_POST['indice_enigme_linked'] = 9;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('post_invalide');

        ajax_creer_indice_modal();
    }
}

