<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Post')) {
    class WP_Post {
        public $ID;
        public function __construct($id)
        {
            $this->ID = $id;
        }
    }
}

class ChasseSolutionsRenderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_details_are_open_by_default(): void
    {
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return 'chasse'; }
        }
        if (!defined('SOLUTION_STATE_EN_COURS')) {
            define('SOLUTION_STATE_EN_COURS', 'en_cours');
        }
        if (!defined('SOLUTION_STATE_A_VENIR')) {
            define('SOLUTION_STATE_A_VENIR', 'a_venir');
        }
        if (!defined('SOLUTION_STATE_FIN_CHASSE')) {
            define('SOLUTION_STATE_FIN_CHASSE', 'fin_chasse');
        }
        if (!defined('SOLUTION_STATE_FIN_CHASSE_DIFFERE')) {
            define('SOLUTION_STATE_FIN_CHASSE_DIFFERE', 'fin_chasse_differee');
        }
        if (!function_exists('utilisateur_peut_voir_solution_chasse')) {
            function utilisateur_peut_voir_solution_chasse($id, $user) { return true; }
        }
        if (!function_exists('recuperer_enigmes_pour_chasse')) {
            function recuperer_enigmes_pour_chasse($id) { return []; }
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) { return [new WP_Post(10)]; }
        }
        if (!function_exists('current_time')) {
            function current_time($type) { return 0; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($str) { return $str; }
        }
        if (!function_exists('get_field')) {
            function get_field($name, $post_id, $format = true) {
                $map = [
                    1 => ['chasse_cache_statut' => 'termine'],
                    10 => [
                        'solution_disponibilite' => 'fin_chasse',
                        'solution_decalage_jours' => 0,
                        'solution_heure_publication' => '00:00',
                        'solution_explication' => 'CONTENT',
                    ],
                ];
                return $map[$post_id][$name] ?? null;
            }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = null) { return $text; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) { return $text; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        ob_start();
        render_chasse_solutions(1, 0);
        $html = ob_get_clean();

        $this->assertStringContainsString('<details open>', $html);
        $this->assertStringContainsString('CONTENT', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_riddle_solutions_not_displayed(): void
    {
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 1 ? 'chasse' : 'enigme'; }
        }
        if (!defined('SOLUTION_STATE_EN_COURS')) {
            define('SOLUTION_STATE_EN_COURS', 'en_cours');
        }
        if (!defined('SOLUTION_STATE_A_VENIR')) {
            define('SOLUTION_STATE_A_VENIR', 'a_venir');
        }
        if (!defined('SOLUTION_STATE_FIN_CHASSE')) {
            define('SOLUTION_STATE_FIN_CHASSE', 'fin_chasse');
        }
        if (!defined('SOLUTION_STATE_FIN_CHASSE_DIFFERE')) {
            define('SOLUTION_STATE_FIN_CHASSE_DIFFERE', 'fin_chasse_differee');
        }
        if (!function_exists('utilisateur_peut_voir_solution_chasse')) {
            function utilisateur_peut_voir_solution_chasse($id, $user) { return true; }
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) { return [new WP_Post(10), new WP_Post(20)]; }
        }
        if (!function_exists('current_time')) {
            function current_time($type) { return 0; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($str) { return $str; }
        }
        if (!function_exists('get_field')) {
            function get_field($name, $post_id, $format = true) {
                $map = [
                    1 => ['chasse_cache_statut' => 'termine'],
                    10 => [
                        'solution_disponibilite' => 'fin_chasse',
                        'solution_decalage_jours' => 0,
                        'solution_heure_publication' => '00:00',
                        'solution_explication' => 'HUNT',
                    ],
                    20 => [
                        'solution_disponibilite' => 'fin_chasse',
                        'solution_decalage_jours' => 0,
                        'solution_heure_publication' => '00:00',
                        'solution_explication' => 'RIDDLE',
                    ],
                ];
                return $map[$post_id][$name] ?? null;
            }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = null) { return $text; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        ob_start();
        render_chasse_solutions(1, 0);
        $html = ob_get_clean();

        $this->assertStringContainsString('HUNT', $html);
        $this->assertStringNotContainsString('RIDDLE', $html);
    }
}
