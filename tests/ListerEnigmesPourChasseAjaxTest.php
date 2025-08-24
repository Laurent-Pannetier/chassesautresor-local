<?php
use PHPUnit\Framework\TestCase;

class ListerEnigmesPourChasseAjaxTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_enigme_list(): void
    {
        if (!function_exists('is_user_logged_in')) { function is_user_logged_in() { return true; } }
        if (!function_exists('get_post_type')) { function get_post_type($id) { return 'chasse'; } }
        if (!function_exists('recuperer_enigmes_pour_chasse')) { function recuperer_enigmes_pour_chasse($id) { return [(object) ['ID' => 5], (object) ['ID' => 6]]; } }
        if (!function_exists('get_the_title')) { function get_the_title($id) { return 'Enigme ' . $id; } }
        if (!function_exists('wp_send_json_error')) { function wp_send_json_error($data = null) { throw new Exception('error'); } }
        if (!function_exists('wp_send_json_success')) { function wp_send_json_success($data = null) { global $json_success_data; $json_success_data = $data; } }
        if (!function_exists('current_user_can')) { function current_user_can($cap) { return false; } }
        if (!function_exists('get_post_status')) { function get_post_status($id) { return 'publish'; } }
        if (!function_exists('get_field')) { function get_field($key, $id) { return 'valide'; } }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) { function utilisateur_est_organisateur_associe_a_chasse($uid, $cid) { return true; } }
        if (!function_exists('get_current_user_id')) { function get_current_user_id() { return 1; } }
        if (!function_exists('indice_action_autorisee')) { function indice_action_autorisee($a,$t,$i){ return true; } }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        global $json_success_data;
        $json_success_data = null;
        $_POST = ['chasse_id' => 10];
        ajax_lister_enigmes_pour_chasse();
        $this->assertEquals([
            ['id' => 5, 'title' => 'Enigme 5'],
            ['id' => 6, 'title' => 'Enigme 6'],
        ], $json_success_data['enigmes']);
    }
}
