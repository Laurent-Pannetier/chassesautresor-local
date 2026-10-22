<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ToolsSubmenuTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHuntAppearsBeforeIndentedRiddlesAndIsActiveWhileEdited(): void
    {
        $this->stubWordPress();
        $_GET = [
            'chasse' => '7',
            'section' => 'parametres',
        ];

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/shell-edition.php';

        ob_start();
        cta_shell_render_outils_submenu();
        $html = (string) ob_get_clean();

        $huntPosition = strpos($html, 'La chasse du phare');
        $riddlePosition = strpos($html, 'Première énigme');

        self::assertNotFalse($huntPosition);
        self::assertNotFalse($riddlePosition);
        self::assertLessThan($riddlePosition, $huntPosition);
        self::assertStringContainsString('app-nav__subitem--hunt is-current', $html);
        self::assertStringContainsString('app-nav__sublink--hunt is-active', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('app-nav__subitem--riddle', $html);
        self::assertStringContainsString('chasse=7', $html);
        self::assertStringContainsString('enigme=11', $html);
    }

    private function stubWordPress(): void
    {
        if (!class_exists('WP_Post')) {
            eval(
                'class WP_Post {'
                . 'public $ID; public $post_status;'
                . 'public function __construct($id, $status = "publish") {'
                . '$this->ID = $id; $this->post_status = $status;'
                . '}}'
            );
        }
        if (!function_exists('cta_shell_user_sees_outils')) {
            eval('function cta_shell_user_sees_outils(){return true;}');
        }
        if (!function_exists('cat_get_managed_hunt_id_for_user')) {
            eval('function cat_get_managed_hunt_id_for_user($user_id = 0){return 7;}');
        }
        if (!function_exists('get_current_user_id')) {
            eval('function get_current_user_id(){return 3;}');
        }
        if (!function_exists('recuperer_enigmes_associees')) {
            eval('function recuperer_enigmes_associees($hunt_id){return [11, 12];}');
        }
        if (!function_exists('get_posts')) {
            eval('function get_posts($args){return [new WP_Post(11), new WP_Post(12)];}');
        }
        if (!function_exists('cta_shell_request_slug')) {
            eval('function cta_shell_request_slug(){return "outils";}');
        }
        if (!function_exists('absint')) {
            eval('function absint($value){return abs((int) $value);}');
        }
        if (!function_exists('wp_unslash')) {
            eval('function wp_unslash($value){return $value;}');
        }
        if (!function_exists('get_post_type')) {
            eval('function get_post_type($id){return (int) $id === 7 ? "chasse" : "enigme";}');
        }
        if (!function_exists('utilisateur_peut_voir_panneau')) {
            eval('function utilisateur_peut_voir_panneau($id){return true;}');
        }
        if (!function_exists('sanitize_key')) {
            eval('function sanitize_key($value){return strtolower((string) $value);}');
        }
        if (!function_exists('get_the_title')) {
            eval(
                'function get_the_title($id){'
                . 'return [7 => "La chasse du phare", 11 => "Première énigme", 12 => "Seconde énigme"][$id] ?? "";'
                . '}'
            );
        }
        if (!function_exists('cat_riddle_missing_labels')) {
            eval('function cat_riddle_missing_labels($id){return [];}');
        }
        if (!function_exists('utilisateur_peut_ajouter_enigme')) {
            eval('function utilisateur_peut_ajouter_enigme($hunt_id, $user_id = 0){return false;}');
        }
        if (!function_exists('cta_shell_location_url')) {
            eval('function cta_shell_location_url($slug){return "https://example.test/" . $slug . "/";}');
        }
        if (!function_exists('add_query_arg')) {
            eval(
                'function add_query_arg($args, $url){'
                . 'return $url . "?" . http_build_query($args);'
                . '}'
            );
        }
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($value){return (string) $value;}');
        }
        if (!function_exists('esc_html')) {
            eval('function esc_html($value){return (string) $value;}');
        }
        if (!function_exists('esc_url')) {
            eval('function esc_url($value){return (string) $value;}');
        }
        if (!function_exists('__')) {
            eval('function __($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null){return $text;}');
        }
    }
}
