<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $message;
        public function __construct($code = '', $message = '')
        {
            $this->message = $message;
        }
        public function get_error_message() { return $this->message; }
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return $thing instanceof WP_Error; }
}

class SolutionCacheUpdateTest extends TestCase
{
    private function registerStubs(): void
    {
        if (!defined('TITRE_DEFAUT_SOLUTION')) {
            define('TITRE_DEFAUT_SOLUTION', 'solution');
        }
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 3 ? 'chasse' : ($id === 123 ? 'solution' : ''); }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('wp_insert_post')) {
            function wp_insert_post($args) { return 123; }
        }
        if (!function_exists('wp_update_post')) {
            function wp_update_post($args) {}
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) { return []; }
        }
        if (!function_exists('update_field')) {
            function update_field($key, $value, $post_id)
            {
                global $captured_fields;
                $captured_fields[$key] = $value;
            }
        }
        if (!function_exists('delete_field')) {
            function delete_field($key, $post_id) {}
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { throw new Exception((string) $data); }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) { global $json_success_data; $json_success_data = $data; return $data; }
        }
        if (!function_exists('sanitize_key')) {
            function sanitize_key($key) { return $key; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($data) { return $data; }
        }
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($text) { return $text; }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
        }
        if (!function_exists('current_time')) {
            function current_time($type) { return 1; }
        }
        if (!function_exists('wp_clear_scheduled_hook')) {
            function wp_clear_scheduled_hook($hook, $args = []) {}
        }
        if (!function_exists('delete_post_meta')) {
            function delete_post_meta($id, $key) {}
        }
        if (!function_exists('get_post_status')) {
            function get_post_status($id) { return 'pending'; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id) { return 'Titre'; }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id)
            {
                global $captured_fields;
                if ($key === 'chasse_cache_statut') {
                    return 'termine';
                }
                return $captured_fields[$key] ?? null;
            }
        }
        if (!function_exists('wp_schedule_single_event')) {
            function wp_schedule_single_event($timestamp, $hook, $args = []) {}
        }
        if (!function_exists('get_post')) {
            function get_post($id)
            {
                return (object) [
                    'post_date'     => '2024-01-01 00:00:00',
                    'post_date_gmt' => '2024-01-01 00:00:00',
                ];
            }
        }
        if (!function_exists('update_post_meta')) {
            function update_post_meta($id, $key, $value) {}
        }
        if (!function_exists('wp_is_post_revision')) {
            function wp_is_post_revision($id) { return false; }
        }
        if (!function_exists('wp_is_post_autosave')) {
            function wp_is_post_autosave($id) { return false; }
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cache_fields_updated_on_creation(): void
    {
        global $captured_fields, $json_success_data;
        $captured_fields   = [];
        $json_success_data = null;
        $_POST             = [];

        $this->registerStubs();
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'             => 3,
            'objet_type'           => 'chasse',
            'solution_explication' => 'texte',
        ];

        ajax_creer_solution_modal();

        $this->assertSame(1, $captured_fields['solution_cache_complet']);
        $this->assertSame(SOLUTION_STATE_EN_COURS, $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame(123, $json_success_data['solution_id']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cache_fields_updated_on_modification(): void
    {
        global $captured_fields, $json_success_data;
        $captured_fields = [
            'solution_cible_type'  => 'chasse',
            'solution_chasse_linked' => 3,
        ];
        $json_success_data = null;
        $_POST             = [];

        $this->registerStubs();
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'solution_id'          => 123,
            'objet_id'             => 3,
            'objet_type'           => 'chasse',
            'solution_explication' => 'nouveau',
            'solution_disponibilite' => 'fin_chasse',
            'solution_decalage_jours' => 0,
            'solution_heure_publication' => '00:00',
        ];

        ajax_modifier_solution_modal();

        $this->assertSame(1, $captured_fields['solution_cache_complet']);
        $this->assertSame(SOLUTION_STATE_EN_COURS, $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame(123, $json_success_data['solution_id']);
    }
}
