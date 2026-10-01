<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintManagementService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRiddleOptionsAjaxHandler.php';

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $queryArg = false): bool
    {
        global $checkedAjaxNonce;
        $checkedAjaxNonce = [$action, $queryArg];
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        if ($hook === 'chassesautresor_can_manage_hint') {
            return indice_action_autorisee($args[0], $args[1], $args[2]);
        }
        if ($hook === 'chassesautresor_hint_target_riddles') {
            return fournir_enigmes_cibles_indice($value, $args[0]);
        }
        if ($hook === 'chassesautresor_next_hint_rank') {
            return fournir_prochain_rang_indice($value, $args[0], $args[1]);
        }
        if ($hook === 'chassesautresor_hint_target_has_solution') {
            return indiquer_solution_cible_indice($value, $args[0], $args[1]);
        }
        if ($hook === 'chassesautresor_hint_hunt_riddle_ids') {
            return fournir_ids_enigmes_table_indice($value, $args[0]);
        }
        if ($hook === 'chassesautresor_hint_related_hunt_id') {
            return fournir_chasse_liee_table_indice($value, $args[0]);
        }
        if ($hook === 'chassesautresor_render_hint_table') {
            return rendre_table_indices($value, ...$args);
        }

        return $value;
    }
}

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
            function current_user_can($capability) { return $capability === 'manage_options'; }
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

        \ChassesAuTresor\Core\Content\HintRiddleOptionsAjaxHandler::handle();

        global $checkedAjaxNonce, $json_success_data;
        $this->assertSame(['hint_management', 'nonce'], $checkedAjaxNonce);
        $ids = array_column($json_success_data['enigmes'], 'id');
        $this->assertSame([2], $ids);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_decodes_html_entities_in_titles(): void
    {
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
            function current_user_can($capability) { return $capability === 'manage_options'; }
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
                return [(object) ['ID' => 1]];
            }
        }
        if (!function_exists('solution_existe_pour_objet')) {
            function solution_existe_pour_objet($id, $type) { return false; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($post) { return 'Sans &#8211; titre'; }
        }
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        $_POST = [
            'chasse_id' => 10,
        ];

        \ChassesAuTresor\Core\Content\HintRiddleOptionsAjaxHandler::handle();

        global $json_success_data;
        $this->assertSame('Sans – titre', $json_success_data['enigmes'][0]['title']);
    }
}
