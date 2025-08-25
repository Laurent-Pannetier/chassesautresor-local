<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error {
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
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public $max_num_pages = 1;
        public function __construct($args)
        {
            global $captured_query_args;
            $captured_query_args = $args;
            $this->posts = [];
            $this->max_num_pages = 1;
        }
    }
}

class ChasseSolutionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $captured_fields, $json_success_data, $captured_query_args;
        $captured_fields     = [];
        $json_success_data   = null;
        $captured_query_args = [];
        $_POST = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_modal_prefills_metadata_for_hunt(): void
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
                if ($key === 'statut_chasse') {
                    return 'terminée';
                }
                return $captured_fields[$key] ?? null;
            }
        }
        if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
            function recuperer_ids_enigmes_pour_chasse($id) { return [5,6]; }
        }
        if (!function_exists('get_template_part')) {
            function get_template_part($slug, $name = null, $args = []) { echo 'table'; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
            'solution_explication' => 'Test',
        ];

        ajax_creer_solution_modal();

        global $captured_fields, $json_success_data;
        $this->assertSame(123, $json_success_data['solution_id']);
        $this->assertSame('chasse', $captured_fields['solution_cible_type']);
        $this->assertSame(3, $captured_fields['solution_chasse_linked']);
        $this->assertSame('fin_chasse', $captured_fields['solution_disponibilite']);
        $this->assertSame(0, $captured_fields['solution_decalage_jours']);
        $this->assertSame('00:00', $captured_fields['solution_heure_publication']);
        $this->assertSame('accessible', $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame(1, $captured_fields['solution_cache_complet']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_modal_sets_programme_when_hunt_not_finished(): void
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
                if ($key === 'statut_chasse') {
                    return 'active';
                }
                return $captured_fields[$key] ?? null;
            }
        }
        if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
            function recuperer_ids_enigmes_pour_chasse($id) { return [5,6]; }
        }
        if (!function_exists('get_template_part')) {
            function get_template_part($slug, $name = null, $args = []) { echo 'table'; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
            'solution_explication' => 'Test',
        ];

        ajax_creer_solution_modal();

        global $captured_fields, $json_success_data;
        $this->assertSame(123, $json_success_data['solution_id']);
        $this->assertSame('programme', $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame(1, $captured_fields['solution_cache_complet']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_lister_table_for_hunt_includes_riddle_solutions(): void
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
            function get_post_type($id) { return $id === 3 ? 'chasse' : 'enigme'; }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
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
        if (!function_exists('get_posts')) {
            function get_posts($args) { return []; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id) { return 'Titre'; }
        }
        if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
            function recuperer_ids_enigmes_pour_chasse($id) { return [5,6]; }
        }
        if (!function_exists('get_template_part')) {
            function get_template_part($slug, $name = null, $args = []) { echo 'table'; }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
            'page'       => 1,
        ];

        ajax_solutions_lister_table();

        global $captured_query_args, $json_success_data;
        $meta = $captured_query_args['meta_query'];
        $this->assertSame(5, $captured_query_args['posts_per_page']);
        $this->assertSame('OR', $meta['relation']);
        $this->assertSame('AND', $meta[0]['relation']);
        $this->assertSame('solution_chasse_linked', $meta[0][1]['key']);
        $this->assertSame(3, $meta[0][1]['value']);
        $this->assertSame('AND', $meta[1]['relation']);
        $this->assertSame('enigme', $meta[1][0]['value']);
        $this->assertSame([5,6], $meta[1][1]['value']);
        $this->assertSame('IN', $meta[1][1]['compare']);
        $this->assertIsArray($json_success_data);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_modal_requires_content(): void
    {
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 3 ? 'chasse' : ''; }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
        }
        if (!function_exists('sanitize_key')) {
            function sanitize_key($key) { return $key; }
        }
        if (!function_exists('wp_kses_post')) {
            function wp_kses_post($data) { return $data; }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { throw new Exception((string) $data); }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('contenu_manquant');
        ajax_creer_solution_modal();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_modifier_solution_modal_requires_content(): void
    {
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 10 ? 'solution' : ($id === 3 ? 'chasse' : ''); }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
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
        if (!function_exists('get_field')) {
            function get_field($key, $id) { return ''; }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { throw new Exception((string) $data); }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'solution_id' => 10,
            'objet_id'    => 3,
            'objet_type'  => 'chasse',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('contenu_manquant');
        ajax_modifier_solution_modal();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_modifier_solution_modal_marks_invalid_when_content_removed(): void
    {
        if (!function_exists('__')) {
            function __($text, $domain = null) { return $text; }
        }
        if (!function_exists('is_user_logged_in')) {
            function is_user_logged_in() { return true; }
        }
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return $id === 123 ? 'solution' : ($id === 3 ? 'chasse' : ''); }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
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
        if (!function_exists('get_field')) {
            function get_field($key, $id)
            {
                if ($key === 'solution_fichier') {
                    return 5;
                }
                if ($key === 'solution_explication') {
                    return '';
                }
                return 0;
            }
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
        if (!function_exists('wp_clear_scheduled_hook')) {
            function wp_clear_scheduled_hook($hook, $args = []) {}
        }
        if (!function_exists('delete_post_meta')) {
            function delete_post_meta($id, $key) {}
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) { throw new Exception((string) $data); }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) { global $json_success_data; $json_success_data = $data; return $data; }
        }
        if (!function_exists('add_action')) {
            function add_action($hook, $callable, $priority = 10, $accepted_args = 1) {}
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'solution_id' => 123,
            'objet_id'    => 3,
            'objet_type'  => 'chasse',
            'solution_explication' => '',
        ];

        ajax_modifier_solution_modal();

        global $captured_fields, $json_success_data;
        $this->assertSame(123, $json_success_data['solution_id']);
        $this->assertSame('invalide', $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame(0, $captured_fields['solution_cache_complet']);
    }
}
