<?php
namespace {
    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in() { return true; }
    }
    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($msg) { throw new \Exception($msg); }
    }
    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null) { global $json_success; $json_success = $data; }
    }
    if (!function_exists('get_post_type')) {
        function get_post_type($id) { return $id === 123 ? 'indice' : 'chasse'; }
    }
    if (!function_exists('indice_action_autorisee')) {
        function indice_action_autorisee($action, $type, $id) { return true; }
    }
    if (!function_exists('wp_kses_post')) {
        function wp_kses_post($data) { return $data; }
    }
    if (!function_exists('sanitize_key')) {
        function sanitize_key($key) { return $key; }
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($text) { return $text; }
    }
    if (!function_exists('update_field')) {
        function update_field($field, $value, $post_id) { global $updated_fields; $updated_fields[$field] = $value; }
    }
    if (!function_exists('delete_field')) {
        function delete_field($field, $post_id) { global $deleted_fields; $deleted_fields[] = $field; }
    }
    if (!function_exists('get_field')) {
        function get_field($field, $post_id) {
            global $existing_fields, $updated_fields;
            return $updated_fields[$field] ?? ($existing_fields[$field] ?? null);
        }
    }
    if (!function_exists('get_post')) {
        function get_post($post_id) {
            return (object) [
                'post_date'     => '2024-01-01 00:00:00',
                'post_date_gmt' => '2024-01-01 00:00:00',
            ];
        }
    }
    if (!function_exists('current_time')) {
        function current_time($type) { return $type === 'timestamp' ? 1704067200 : '2024-01-01 00:00:00'; }
    }
    if (!function_exists('wp_date')) {
        function wp_date($format, $timestamp, $timezone = null) { return gmdate($format, $timestamp); }
    }
    if (!function_exists('wp_is_post_revision')) {
        function wp_is_post_revision($id) { return false; }
    }
    if (!function_exists('wp_is_post_autosave')) {
        function wp_is_post_autosave($id) { return false; }
    }
    if (!function_exists('convertir_en_datetime')) {
        function convertir_en_datetime($date) { return new \DateTime($date); }
    }
    if (!function_exists('get_post_status')) {
        function get_post_status($id) { return 'pending'; }
    }
    if (!function_exists('wp_update_post')) {
        function wp_update_post($args) { return true; }
    }
    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';
}

namespace ModifierIndiceImmediateDateTest {
    use PHPUnit\Framework\TestCase;

    class ModifierIndiceImmediateDateTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_keeps_existing_date_when_immediate(): void
        {
            global $existing_fields, $updated_fields;
            $existing_fields = ['indice_date_disponibilite' => '2024-03-10 00:00:00'];
            $updated_fields  = [];

            $_POST = [
                'indice_id' => 123,
                'objet_id' => 10,
                'objet_type' => 'chasse',
                'indice_image' => 0,
                'indice_contenu' => 'foo',
                'indice_disponibilite' => 'immediate',
            ];

            \ajax_modifier_indice_modal();

            $this->assertSame('2024-03-10 00:00:00', $updated_fields['indice_date_disponibilite']);
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_sets_today_when_no_existing_date(): void
        {
            global $existing_fields, $updated_fields;
            $existing_fields = [];
            $updated_fields  = [];

            $_POST = [
                'indice_id' => 123,
                'objet_id' => 10,
                'objet_type' => 'chasse',
                'indice_image' => 0,
                'indice_contenu' => 'foo',
                'indice_disponibilite' => 'immediate',
            ];

            \ajax_modifier_indice_modal();

            $this->assertSame('2024-01-01 00:00:00', $updated_fields['indice_date_disponibilite']);
        }
    }
}
