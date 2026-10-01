<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return $capability === 'manage_options'; }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionManagementService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionQueryService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionManagementAjaxHandler.php';

if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts;
        public function __construct($args) { $this->posts = recuperer_enigmes_pour_chasse(10); }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        if ($hook === 'chassesautresor_can_manage_solution') {
            return solution_action_autorisee($args[0], $args[1], $args[2]);
        }
        if ($hook === 'chassesautresor_hunt_riddles') {
            return recuperer_enigmes_pour_chasse($args[0]);
        }
        if ($hook === 'chassesautresor_solution_exists') {
            return solution_existe_pour_objet($args[0], $args[1]);
        }

        return $value;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $queryArg = false): bool
    {
        global $checkedAjaxNonce;
        $checkedAjaxNonce = [$action, $queryArg];

        return true;
    }
}

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
        if (!function_exists('get_posts')) {
            function get_posts($args) {
                $targetId = $args['meta_query'][2][0]['value'] ?? 0;
                return ($args['post_type'] ?? '') === 'solution' && in_array($targetId, [1, 10], true)
                    ? [99]
                    : [];
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

        \ChassesAuTresor\Core\Content\SolutionManagementAjaxHandler::getHuntStatus();

        global $checkedAjaxNonce;
        $this->assertSame(['solution_management', 'nonce'], $checkedAjaxNonce);
        global $json_success_data;
        $this->assertSame(1, $json_success_data['has_solution_chasse']);
        $this->assertSame(1, $json_success_data['has_solution_enigme']);
        $this->assertSame(0, $json_success_data['has_enigmes']);
    }
}
