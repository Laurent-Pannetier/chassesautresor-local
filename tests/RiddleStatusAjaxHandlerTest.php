<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatusAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatusAjaxHandler.php';

final class RiddleStatusAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedRiddleStatusEndpointOnce(): void {
        $hooks = [];
        RiddleStatusAjaxHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'wp_ajax_forcer_recalcul_statut_enigme',
            [RiddleStatusAjaxHandler::class, 'handle'],
            10,
            1,
        ]], $hooks);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReturnsLiveMissingLabelsAfterRefresh(): void
    {
        eval(<<<'PHP'
function check_ajax_referer($action, $query_arg) { return true; }
function is_user_logged_in() { return true; }
function get_post_type($post) { return 'enigme'; }
function utilisateur_peut_modifier_post($post_id) { return true; }
function do_action($hook, ...$args) { $GLOBALS['riddle_status_actions'][] = $hook; }
function cat_riddle_missing_labels($riddle_id) { return $GLOBALS['riddle_missing_labels']; }
function wp_send_json_success($data = null) { $GLOBALS['json_success'] = $data; }
PHP);
        $_POST['post_id'] = '11';
        $GLOBALS['riddle_status_actions'] = [];
        $GLOBALS['riddle_missing_labels'] = ['Titre'];

        RiddleStatusAjaxHandler::handle();

        $this->assertSame(['Titre'], $GLOBALS['json_success']['missing']);
        $this->assertFalse($GLOBALS['json_success']['complete']);
        $this->assertContains(
            'chassesautresor_riddle_state_refresh_requested',
            $GLOBALS['riddle_status_actions']
        );
        $this->assertContains(
            'chassesautresor_riddle_completeness_refresh_requested',
            $GLOBALS['riddle_status_actions']
        );

        $GLOBALS['riddle_missing_labels'] = [];
        $GLOBALS['riddle_status_actions'] = [];
        RiddleStatusAjaxHandler::handle();

        $this->assertSame([], $GLOBALS['json_success']['missing']);
        $this->assertTrue($GLOBALS['json_success']['complete']);
    }
}
