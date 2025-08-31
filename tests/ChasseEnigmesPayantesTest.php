<?php
use PHPUnit\Framework\TestCase;

class ChasseEnigmesPayantesTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_ignore_enigmes_without_validation_mode(): void
    {
        if (!function_exists('get_post_type')) {
            function get_post_type($id) { return 'chasse'; }
        }
        if (!function_exists('get_permalink')) {
            function get_permalink($id) { return 'https://example.com/chasse/' . $id; }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) { return false; }
        }
        if (!function_exists('est_organisateur')) {
            function est_organisateur($user_id) { return false; }
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) { return false; }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = null) { return $text; }
        }
        if (!function_exists('esc_url')) {
            function esc_url($url) { return $url; }
        }
        if (!function_exists('site_url')) {
            function site_url($path = '') { return 'https://example.com' . $path; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) { return $text; }
        }
        if (!function_exists('date_i18n')) {
            function date_i18n($format, $timestamp) { return date($format, $timestamp); }
        }
        if (!function_exists('get_user_points')) {
            function get_user_points($user_id) { return 100; }
        }
        if (!function_exists('get_field')) {
            function get_field($field, $post_id) {
                global $fields;
                return $fields[$post_id][$field] ?? null;
            }
        }
        if (!function_exists('wp_strip_all_tags')) {
            function wp_strip_all_tags($text) { return $text; }
        }
        if (!function_exists('wp_trim_words')) {
            function wp_trim_words($text, $num_words = 55, $more = '...') { return $text; }
        }
        if (!function_exists('recuperer_enigmes_associees')) {
            function recuperer_enigmes_associees($chasse_id) { return [10, 11, 12]; }
        }
        if (!function_exists('get_post_meta')) {
            function get_post_meta($post_id, $key, $single = false) { return null; }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

        global $fields, $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public function prepare($query, ...$args) { return $query; }
            public function get_var($query) { return null; }
        };
        $fields = [
            1 => [
                'chasse_principale_description' => '',
                'chasse_principale_image' => null,
                'chasse_principale_liens' => [],
            ],
            10 => [
                'enigme_tentative_cout_points' => 5,
                'enigme_mode_validation' => 'manuelle',
            ],
            11 => [
                'enigme_tentative_cout_points' => 3,
                'enigme_mode_validation' => 'aucune',
            ],
            12 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation' => 'manuelle',
            ],
        ];

        $infos = preparer_infos_affichage_chasse(1, 2);
        $this->assertSame(1, $infos['nb_enigmes_payantes']);
    }
}
