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
        function get_post_type($id) { return 'enigme'; }
    }
    if (!function_exists('utilisateur_peut_modifier_post')) {
        function utilisateur_peut_modifier_post($post_id) { return true; }
    }
    if (!function_exists('utilisateur_peut_editer_champs')) {
        function utilisateur_peut_editer_champs($post_id) { return true; }
    }
    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() { return 1; }
    }
    if (!function_exists('get_field')) {
        function get_field($field, $post_id) {
            global $fields;
            return $fields[$post_id][$field] ?? null;
        }
    }
    if (!function_exists('update_field')) {
        function update_field($field, $value, $post_id) {
            global $fields;
            $fields[$post_id][$field] = $value;
            return true;
        }
    }
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false) {
            global $fields;
            return $fields[$post_id][$key] ?? null;
        }
    }
    if (!function_exists('enigme_mettre_a_jour_etat_systeme')) {
        function enigme_mettre_a_jour_etat_systeme($post_id) {}
    }
    if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
        function verifier_ou_mettre_a_jour_cache_complet($post_id) {}
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($text) { return $text; }
    }
    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-enigme.php';
}

namespace ModifierChampEnigmePrerequisTest {
    use PHPUnit\Framework\TestCase;

    class ModifierChampEnigmePrerequisTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_saves_prerequisites_as_array(): void
        {
            global $fields, $json_success;
            $fields = [];
            $json_success = null;

            $_POST = [
                'champ' => 'enigme_acces_pre_requis',
                'valeur' => '1,2',
                'post_id' => 99,
            ];

            \modifier_champ_enigme();

            $this->assertSame([1, 2], $fields[99]['enigme_acces_pre_requis']);
            $this->assertSame('pre_requis', $fields[99]['enigme_acces_condition']);
        }
    }
}
