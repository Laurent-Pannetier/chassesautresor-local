<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusService.php';

class ChasseCorrectionBadgeTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_badge_correction_when_validation_not_valid(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        global $acf_fields, $cache_cleared, $json_success_data;
        $acf_fields = [
            123 => [
                'chasse_cache_statut' => 'en_cours',
                'chasse_cache_statut_validation' => 'correction',
            ],
        ];
        $cache_cleared = false;

        // Stubs
        eval('function get_post_type($id){return "chasse";}');
        eval('function get_field($key,$id){global $acf_fields; return $acf_fields[$id][$key] ?? null;}');
        eval('function update_field($key,$val,$id){global $acf_fields; $acf_fields[$id][$key] = $val;}');
        eval('function current_time($type){return 1000;}');
        eval('function convertir_en_datetime($d){return $d ? new DateTime($d) : null;}');
        eval('function recuperer_enigmes_associees($cid){return [];}');
        eval('function get_posts($args){return [];}');
        eval('function do_action($hook, ...$args){'
            . 'if ($hook === "chassesautresor_hunt_display_cache_clear_requested") {'
            . 'global $cache_cleared; $cache_cleared = true;'
            . '}'
            . '}');
        eval('function planifier_ou_deplacer_pdf_solution_immediatement($id){}');
        eval('function synchroniser_cache_enigmes_chasse($cid, $a = true, $b = true){}');
        eval('function chasse_clear_infos_affichage_cache($cid){global $cache_cleared; $cache_cleared = true;}');
        eval('function cat_debug($msg){}');
        eval('function is_user_logged_in(){return true;}');
        eval('function utilisateur_peut_modifier_post($id){return true;}');
        eval('function check_ajax_referer($action, $field){return true;}');
        eval('function apply_filters($hook, $value, ...$args){'
            . 'if ($hook === "chassesautresor_can_modify_hunt") return true;'
            . 'if ($hook === "chassesautresor_render_hunt_status_badge") '
            . 'return chasse_preparer_badge_statut($args[0], $args[1]);'
            . 'return $value;'
            . '}');
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) {
                global $json_success_data;
                $json_success_data = $data;
                return $data;
            }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { return $data; }
        }

        require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/hunt-status-functions.php';
        require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/completion-functions.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/statut-functions.php';

        verifier_ou_recalculer_statut_chasse(123);

        $this->assertTrue($cache_cleared);
        $this->assertSame('revision', $acf_fields[123]['chasse_cache_statut']);

        $_POST['post_id'] = 123;
        recuperer_statut_chasse();
        $this->assertSame('correction', $json_success_data['statut_label']);
    }
}
