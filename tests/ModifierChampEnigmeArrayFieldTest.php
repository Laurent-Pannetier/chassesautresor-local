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
        function get_field($field, $post_id) { return null; }
    }
    if (!function_exists('update_field')) {
        function update_field($field, $value, $post_id) {
            global $updated_fields;
            $updated_fields[$field] = $value;
            return true;
        }
    }
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false) {
            global $updated_fields;
            return $updated_fields[$key] ?? null;
        }
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($text) {
            if (is_array($text)) {
                throw new \Exception('sanitize_text_field array');
            }
            return $text;
        }
    }
    if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
        function verifier_ou_mettre_a_jour_cache_complet($post_id) {}
    }
    if (!function_exists('recuperer_id_chasse_associee')) {
        function recuperer_id_chasse_associee($post_id) { return 0; }
    }
    if (!function_exists('enigme_mettre_a_jour_etat_systeme')) {
        function enigme_mettre_a_jour_etat_systeme($post_id) {}
    }
    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-enigme.php';
}

namespace ModifierChampEnigmeArrayFieldTest {
    use PHPUnit\Framework\TestCase;

    class ModifierChampEnigmeArrayFieldTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_updates_array_field_without_warning(): void
        {
            global $updated_fields, $json_success;
            $updated_fields = [];
            $json_success = null;

            $_POST = [
                'champ'   => 'mon_champ_array',
                'valeur'  => ['foo', 'bar'],
                'post_id' => 99,
            ];

            \modifier_champ_enigme();

            $this->assertSame(['foo', 'bar'], $updated_fields['mon_champ_array']);
        }
    }
}
