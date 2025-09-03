<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {}
}

if (!function_exists('__')) {
    function __($text, $domain = null) { return $text; }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool { return true; }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void { throw new Exception((string) $data); }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $json_success_data;
        $json_success_data = $data;
        return $data;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id) { return 'enigme'; }
}

if (!function_exists('indice_action_autorisee')) {
    function indice_action_autorisee($action, $type, $id) { return true; }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return false; }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($v) { return $v; }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($v) { return $v; }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($v) { return $v; }
}

if (!defined('TITRE_DEFAUT_INDICE')) {
    define('TITRE_DEFAUT_INDICE', 'indice');
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!function_exists('utilisateur_peut_modifier_post')) {
    function utilisateur_peut_modifier_post($id) { return true; }
}

if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($id) { return 1; }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return 1; }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($args) { return 123; }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($args) { return true; }
}

if (!function_exists('get_posts')) {
    function get_posts($args) { return []; }
}

if (!function_exists('wp_is_post_revision')) {
    function wp_is_post_revision($id) { return false; }
}

if (!function_exists('wp_is_post_autosave')) {
    function wp_is_post_autosave($id) { return false; }
}

if (!function_exists('convertir_en_datetime')) {
    function convertir_en_datetime($date) { return new DateTime($date); }
}

if (!function_exists('update_field')) {
    function update_field($field, $value, $id) {
        global $updated_fields;
        $updated_fields[$field] = $value;
        return true;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $id) {
        global $existing_fields;
        return $existing_fields[$field] ?? null;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return $type === 'timestamp' ? 1704067200 : '2024-01-01 00:00:00';
    }
}

if (!function_exists('wp_date')) {
    function wp_date($format, $timestamp, $timezone = null) { return gmdate($format, $timestamp); }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

class IndiceEnigmeCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        global $json_success_data, $updated_fields, $existing_fields;
        $json_success_data = null;
        $updated_fields    = [];
        $existing_fields   = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_missing_riddle_id_returns_error(): void
    {
        $_POST['objet_id']   = 7;
        $_POST['objet_type'] = 'enigme';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('post_invalide');

        ajax_creer_indice_modal();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_mismatched_riddle_id_returns_error(): void
    {
        $_POST['objet_id']            = 7;
        $_POST['objet_type']          = 'enigme';
        $_POST['indice_enigme_linked'] = 9;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('post_invalide');

        ajax_creer_indice_modal();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_creates_index_for_riddle(): void
    {
        $_POST = [
            'objet_id'             => 7,
            'objet_type'           => 'enigme',
            'indice_enigme_linked' => 7,
            'indice_disponibilite' => 'immediate',
        ];

        ajax_creer_indice_modal();

        global $json_success_data, $updated_fields;
        $this->assertSame(['indice_id' => 123], $json_success_data);
        $this->assertSame(7, $updated_fields['indice_enigme_linked'] ?? null);
        $this->assertArrayHasKey('indice_disponibilite', $updated_fields);
    }
}
