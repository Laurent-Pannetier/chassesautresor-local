<?php
namespace {
    if (!function_exists('get_post_type')) {
        function get_post_type($id) {
            global $post_type; return $post_type; }
    }
    if (!function_exists('wp_is_post_revision')) {
        function wp_is_post_revision($id) { return false; }
    }
    if (!function_exists('wp_is_post_autosave')) {
        function wp_is_post_autosave($id) { return false; }
    }
    if (!function_exists('get_field')) {
        function get_field($field, $post_id) {
            global $fields; return $fields[$field] ?? null; }
    }
    if (!function_exists('update_field')) {
        function update_field($field, $value, $post_id) {
            global $updated_fields; $updated_fields[$field] = $value; }
    }
    if (!function_exists('get_organisateur_from_chasse')) {
        function get_organisateur_from_chasse($chasse_id) { return 10; }
    }
    if (!function_exists('recuperer_id_chasse_associee')) {
        function recuperer_id_chasse_associee($id) { return 99; }
    }
    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';
}

namespace IndiceOrganisateur {
    use PHPUnit\Framework\TestCase;

    class IndiceOrganisateurAutoFillTest extends TestCase
    {
        protected function setUp(): void
        {
            global $fields, $updated_fields, $post_type;
            $fields = [];
            $updated_fields = [];
            $post_type = 'indice';
        }

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_sets_organisateur_on_save(): void
        {
            global $fields, $updated_fields;
            $fields = [
                'indice_organisateur_linked' => null,
                'indice_cible' => 'chasse',
                'indice_cible_objet' => 42,
            ];
            \sauvegarder_indice_organisateur_si_manquant(123);
            $this->assertSame(10, $updated_fields['indice_organisateur_linked']);
        }
    }
}
