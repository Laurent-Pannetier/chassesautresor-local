<?php
namespace {
    if (!defined('TITRE_DEFAUT_INDICE')) {
        define('TITRE_DEFAUT_INDICE', 'indice');
    }

    if (!function_exists('__')) {
        function __($text, $domain = null) { return $text; }
    }

    if (!function_exists('get_post_type')) {
        function get_post_type($id) { return 'chasse'; }
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
        function wp_update_post($args) { global $updated_post; $updated_post = $args; return 123; }
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
        function wp_date($format, $timestamp) { return gmdate($format, $timestamp); }
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
}

namespace CreerIndice {

use PHPUnit\Framework\TestCase;

class CreerIndicePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        global $is_logged_in, $can_edit, $mocked_existing_indices;
        $is_logged_in = true;
        $can_edit = false;
        $mocked_existing_indices = [];
    }

    protected function tearDown(): void
    {
        global $is_logged_in, $can_edit, $mocked_existing_indices;
        $is_logged_in = true;
        $can_edit = false;
        $mocked_existing_indices = [];
    }

    public function test_creates_indice_when_authorised(): void
    {
        global $can_edit, $updated_fields, $updated_post, $mocked_existing_indices;
        $can_edit = true;
        $updated_fields = [];
        $updated_post = [];
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
        $this->assertSame('Indice #3', $updated_post['post_title']);
    }
}
}
