<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ChasseListerEnigmesAjaxTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_excludes_enigmas_with_existing_solutions(): void
    {
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 10 ? 'chasse' : 'enigme'; }
        }
        if (!function_exists('indice_action_autorisee')) {
            function indice_action_autorisee($a, $b, $c) { return true; }
        }
        if (!function_exists('sanitize_key')) {
            function sanitize_key($key) { return $key; }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { throw new Exception((string) $data); }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) { global $json_success_data; $json_success_data = $data; return $data; }
        }
        if (!function_exists('recuperer_enigmes_pour_chasse')) {
            function recuperer_enigmes_pour_chasse($id)
            {
                $e1 = (object) ['ID' => 1];
                $e2 = (object) ['ID' => 2];
                return [$e1, $e2];
            }
        }
        if (!function_exists('solution_existe_pour_objet')) {
            function solution_existe_pour_objet($id, $type) { return $id === 1; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($post) { return 'Title'; }
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) { return []; }
        }
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        $_POST = [
            'chasse_id'     => 10,
            'sans_solution' => 1,
        ];

        ajax_chasse_lister_enigmes();

        global $json_success_data;
        $ids = array_column($json_success_data['enigmes'], 'id');
        $this->assertSame([2], $ids);
    }
}
