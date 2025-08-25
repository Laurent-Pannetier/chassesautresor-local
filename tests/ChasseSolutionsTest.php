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
    public function test_creer_solution_modal_creates_complete_solution(): void
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
                    return 'en cours';
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
            'objet_id'             => 3,
            'objet_type'           => 'chasse',
            'solution_explication' => 'texte',
        ];

        ajax_creer_solution_modal();

        global $captured_fields, $json_success_data;
        $this->assertSame(123, $json_success_data['solution_id']);
        $this->assertSame('chasse', $captured_fields['solution_cible_type']);
        $this->assertSame(3, $captured_fields['solution_chasse_linked']);
        $this->assertSame('fin_chasse', $captured_fields['solution_disponibilite']);
        $this->assertSame(0, $captured_fields['solution_decalage_jours']);
        $this->assertSame('00:00', $captured_fields['solution_heure_publication']);
        $this->assertSame('programme', $captured_fields['solution_cache_etat_systeme']);
        $this->assertTrue($captured_fields['solution_cache_complet']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_modal_requires_content(): void
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
            function wp_send_json_success($data = null) { return $data; }
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
        if (!function_exists('wp_delete_post')) {
            function wp_delete_post($id, $force = false) {}
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) { return null; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id) { return 'Titre'; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
        ];

        try {
            ajax_creer_solution_modal();
            $this->fail('Expected exception not thrown');
        } catch (Exception $e) {
            $this->assertSame('Un texte ou un PDF est requis.', $e->getMessage());
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creer_solution_modal_enigme_requires_content(): void
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
            function get_post_type($id) { return $id === 5 ? 'enigme' : ($id === 123 ? 'solution' : ''); }
        }
        if (!function_exists('recuperer_id_chasse_associee')) {
            function recuperer_id_chasse_associee($id) { return 3; }
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
            function wp_send_json_success($data = null) { return $data; }
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
        if (!function_exists('wp_delete_post')) {
            function wp_delete_post($id, $force = false) {}
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) { return null; }
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id) { return 'Titre'; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 5,
            'objet_type' => 'enigme',
        ];

        try {
            ajax_creer_solution_modal();
            $this->fail('Expected exception not thrown');
        } catch (Exception $e) {
            $this->assertSame('Un texte ou un PDF est requis.', $e->getMessage());
        }
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
            function get_post_type($id) { return $id === 3 ? 'chasse' : ($id === 123 ? 'solution' : ''); }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
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
            function wp_send_json_success($data = null) { return $data; }
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
        if (!function_exists('get_field')) {
            function get_field($key, $post_id)
            {
                if ($key === 'solution_cible_type') {
                    return 'chasse';
                }
                if ($key === 'solution_chasse_linked') {
                    return 3;
                }
                return null;
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'solution_id' => 123,
            'objet_id'    => 3,
            'objet_type'  => 'chasse',
        ];

        try {
            ajax_modifier_solution_modal();
            $this->fail('Expected exception not thrown');
        } catch (Exception $e) {
            $this->assertSame('Un texte ou un PDF est requis.', $e->getMessage());
        }
        global $captured_fields;
        $this->assertSame('invalide', $captured_fields['solution_cache_etat_systeme']);
        $this->assertFalse($captured_fields['solution_cache_complet']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_modifier_solution_modal_keeps_existing_content(): void
    {
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
        if (!function_exists('update_post_meta')) {
            function update_post_meta($id, $key, $value) {}
        }
        if (!function_exists('wp_schedule_single_event')) {
            function wp_schedule_single_event($timestamp, $hook, $args = []) {}
        }
        if (!function_exists('get_post_status')) {
            function get_post_status($id) { return 'pending'; }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id)
            {
                global $captured_fields;
                if ($key === 'solution_cible_type') {
                    return 'chasse';
                }
                if ($key === 'solution_chasse_linked') {
                    return 3;
                }
                if (array_key_exists($key, $captured_fields)) {
                    return $captured_fields[$key];
                }
                if ($key === 'solution_fichier') {
                    return 10;
                }
                if ($key === 'solution_explication') {
                    return 'ancien';
                }
                if ($key === 'statut_chasse') {
                    return 'terminée';
                }
                return null;
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'solution_id'              => 123,
            'objet_id'                 => 3,
            'objet_type'               => 'chasse',
            'solution_disponibilite'   => 'differee',
            'solution_decalage_jours'  => 2,
            'solution_heure_publication' => '12:00',
        ];

        ajax_modifier_solution_modal();

        global $captured_fields, $json_success_data;
        $this->assertSame(123, $json_success_data['solution_id']);
        $this->assertTrue($captured_fields['solution_cache_complet']);
        $this->assertSame('programme', $captured_fields['solution_cache_etat_systeme']);
        $this->assertSame('differee', $captured_fields['solution_disponibilite']);
        $this->assertSame(2, $captured_fields['solution_decalage_jours']);
        $this->assertSame('12:00', $captured_fields['solution_heure_publication']);
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
    public function test_creer_solution_modal_uploads_pdf_file(): void
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
            function get_post_type($id) { return $id === 3 ? 'chasse' : 'solution'; }
        }
        if (!function_exists('solution_action_autorisee')) {
            function solution_action_autorisee($action, $type, $id) { return true; }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('wp_insert_post')) {
            function wp_insert_post($args) { return 456; }
        }
        if (!function_exists('wp_update_post')) {
            function wp_update_post($args) {}
        }
        if (!function_exists('get_the_title')) {
            function get_the_title($id) { return 'Titre'; }
        }
        if (!function_exists('get_posts')) {
            function get_posts($args) { return []; }
        }
        if (!function_exists('media_handle_upload')) {
            function media_handle_upload($field, $parent)
            {
                global $uploaded_args;
                $uploaded_args = [$field, $parent];
                return 789;
            }
        }
        if (!function_exists('update_field')) {
            function update_field($key, $value, $post_id)
            {
                global $captured_fields;
                $captured_fields[$key] = $value;
            }
        }
        if (!function_exists('get_field')) {
            function get_field($key, $post_id) { return ''; }
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

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-solution.php';

        $_POST = [
            'objet_id'   => 3,
            'objet_type' => 'chasse',
        ];
        $_FILES = [
            'solution_fichier' => [
                'name'     => 'test.pdf',
                'tmp_name' => '/tmp/test.pdf',
                'error'    => 0,
                'size'     => 123,
            ],
        ];

        ajax_creer_solution_modal();

        global $captured_fields, $uploaded_args, $json_success_data;
        $this->assertSame(['solution_fichier', 456], $uploaded_args);
        $this->assertSame(789, $captured_fields['solution_fichier']);
        $this->assertSame(456, $json_success_data['solution_id']);
    }
}
