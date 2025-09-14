<?php
namespace {
    if (!defined('TITRE_DEFAUT_INDICE')) {
        define('TITRE_DEFAUT_INDICE', 'indice');
    }

    if (!function_exists('__')) {
        function __($text, $domain = null) { return $text; }
    }

    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__);
    }

    if (!function_exists('get_post_type')) {
        function get_post_type($id) { return $id === 42 ? 'chasse' : 'indice'; }
    }

    if (!function_exists('get_post_status')) {
        function get_post_status($id) { return 'pending'; }
    }

    if (!function_exists('current_user_can')) {
        function current_user_can($cap) { return false; }
    }

    if (!function_exists('wp_get_current_user')) {
        function wp_get_current_user() { return (object) ['roles' => ['author']]; }
    }

    if (!defined('ROLE_ORGANISATEUR')) {
        define('ROLE_ORGANISATEUR', 'organisateur');
    }

    if (!defined('ROLE_ORGANISATEUR_CREATION')) {
        define('ROLE_ORGANISATEUR_CREATION', 'organisateur_creation');
    }

    if (!function_exists('get_userdata')) {
        function get_userdata($id) { return (object) ['roles' => [ROLE_ORGANISATEUR]]; }
    }

    if (!function_exists('add_action')) {
        function add_action(...$args) {}
    }

    if (!function_exists('add_filter')) {
        function add_filter(...$args) {}
    }

    if (!function_exists('get_field')) {
        function get_field($field, $post_id) {
            global $mocked_fields; return $mocked_fields[$post_id][$field] ?? null;
        }
    }

    if (!function_exists('cat_debug')) {
        function cat_debug(...$args) {}
    }

    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in() { global $is_logged_in; return $is_logged_in; }
    }

    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() { return 1; }
    }

    if (!function_exists('utilisateur_peut_modifier_post')) {
        function utilisateur_peut_modifier_post($id) { global $can_edit; return $can_edit; }
    }

    if (!function_exists('get_posts')) {
        function get_posts($args) { global $mocked_existing_indices; return $mocked_existing_indices ?? []; }
    }

    if (!function_exists('wp_insert_post')) {
        function wp_insert_post($args) { return 123; }
    }

    if (!function_exists('wp_update_post')) {
        function wp_update_post($args) { global $updated_posts; $updated_posts[] = $args; return $args['ID'] ?? 123; }
    }
    if (!function_exists('update_post_meta')) {
        function update_post_meta($post_id, $key, $value) { global $updated_meta; $updated_meta[$post_id][$key] = $value; }
    }

    if (!function_exists('update_field')) {
        function update_field($field, $value, $post_id) { global $updated_fields; $updated_fields[$field] = $value; }
    }

    if (!function_exists('current_time')) {
        function current_time($format) {
            if ($format === 'timestamp') return 1704067200;
            return '2024-01-01 00:00:00';
        }
    }

    if (!function_exists('wp_date')) {
        function wp_date($format, $timestamp, $timezone = null) { return gmdate($format, $timestamp); }
    }

    if (!function_exists('get_the_title')) {
        function get_the_title($id) { return 'Chasse de Test'; }
    }

    if (!defined('DAY_IN_SECONDS')) {
        define('DAY_IN_SECONDS', 86400);
    }

    if (!class_exists('WP_Error')) {
        class WP_Error {
            private $message;
            public function __construct($code = '', $message = '') { $this->message = $message; }
            public function get_error_message() { return $this->message; }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) { return $thing instanceof WP_Error; }
    }

    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';
    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';
}

namespace CreerIndice {

use PHPUnit\Framework\TestCase;

class CreerIndicePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        global $is_logged_in, $can_edit, $mocked_existing_indices, $mocked_fields, $updated_meta;
        $is_logged_in           = true;
        $can_edit               = false;
        $mocked_existing_indices = [];
        $mocked_fields          = [];
        $updated_meta           = [];
    }

    protected function tearDown(): void
    {
        global $is_logged_in, $can_edit, $mocked_existing_indices, $mocked_fields, $updated_meta;
        $is_logged_in           = true;
        $can_edit               = false;
        $mocked_existing_indices = [];
        $mocked_fields          = [];
        $updated_meta           = [];
    }

    public function test_creates_indice_when_authorised(): void
    {
        global $can_edit, $updated_fields, $updated_posts, $mocked_existing_indices, $updated_meta;
        $can_edit               = true;
        $updated_fields         = [];
        $updated_posts          = [];
        $updated_meta           = [];
        $mocked_existing_indices = [10, 11];

        $result = \creer_indice_pour_objet(42, 'chasse');
        $this->assertSame(123, $result);
        $this->assertSame('chasse', $updated_fields['indice_cible_type']);
        $this->assertSame(42, $updated_fields['indice_chasse_linked']);
        $this->assertArrayNotHasKey('indice_enigme_linked', $updated_fields);
        $expected_date = wp_date('Y-m-d H:i:s', (int) current_time('timestamp') + DAY_IN_SECONDS);
        $this->assertSame($expected_date, $updated_fields['indice_date_disponibilite']);
        $this->assertSame('desactive', $updated_fields['indice_cache_etat_systeme']);
        $this->assertFalse($updated_fields['indice_cache_complet']);
        $this->assertSame(3, $updated_meta[123]['indice_rank']);
    }

    public function test_utilisateur_peut_editer_indice_desactive(): void
    {
        global $can_edit, $mocked_fields;
        $can_edit = true;
        $mocked_fields = [123 => ['indice_cache_etat_systeme' => 'desactive']];
        $this->assertTrue(\utilisateur_peut_editer_champs(123));
    }

    // Les états « accessible », « programme » et « invalide » ne sont pas modifiables.
    // Ils ne sont pas testés ici car la fonction get_field de l’environnement
    // de test n’est pas surchargeable facilement.
}
}
