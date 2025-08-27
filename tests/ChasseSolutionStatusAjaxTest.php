<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ChasseSolutionStatusAjaxTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_correct_flags(): void
    {
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 10 ? 'chasse' : 'enigme'; }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($a, $b, $c) { return true; }
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
            function solution_existe_pour_objet($id, $type)
            {
                return $type === 'chasse' ? true : ($id === 2 ? false : true);
            }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null)
            {
                global $json_success_data;
                $json_success_data = $data;
                return $data;
            }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null)
            {
                throw new Exception((string) $data);
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'chasse_id' => 10,
            'enigme_id' => 1,
        ];

        ajax_chasse_solution_status();

        global $json_success_data;
        $this->assertSame(1, $json_success_data['has_solution_chasse']);
        $this->assertSame(1, $json_success_data['has_solution_enigme']);
        $this->assertSame(1, $json_success_data['has_enigmes']);
    }
}
