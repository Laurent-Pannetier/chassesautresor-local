<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return false;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) {
        return false;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        return null;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id) {
        return '';
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain) {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain) {
        return $number === 1 ? $single : $plural;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('get_user_points')) {
    function get_user_points($user_id) {
        return 0;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name, $referer = true, $echo = false) {
        return '';
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '') {
        return $path;
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp) {
        return '';
    }
}

if (!function_exists('utilisateur_est_engage_dans_chasse')) {
    function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) {
        return $GLOBALS['is_engage'] ?? false;
    }
}

if (!function_exists('chasse_calculer_progression_utilisateur')) {
    function chasse_calculer_progression_utilisateur($chasse_id, $user_id) {
        return $GLOBALS['progression'] ?? ['engagees' => 0, 'total' => 0, 'resolues' => 0, 'resolvables' => 0];
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

class GenererCtaChasseTest extends TestCase
{
    public function test_admin_or_organizer_gets_disabled_button(): void
    {
        $GLOBALS['force_admin_override'] = true;
        $GLOBALS['force_engage_override'] = false;
        $GLOBALS['force_organisateur_override'] = false;
        $cta = generer_cta_chasse(123, 5);
        $this->assertSame(
            [
                'cta_html'    => '<button class="bouton-cta" disabled>Participer</button>',
                'cta_message' => '',
                'type'        => 'indisponible',
            ],
            $cta
        );
    }

    public function test_guest_gets_login_cta_without_message(): void
    {
        $GLOBALS['force_admin_override'] = false;
        $GLOBALS['force_engage_override'] = false;
        $GLOBALS['force_organisateur_override'] = false;
        $cta = generer_cta_chasse(123, 0);
        $this->assertSame(
            [
                'cta_html'    => '<a href="https://example.com/mon-compte" class="bouton-cta bouton-cta--color">S\'identifier</a>',
                'cta_message' => '',
                'type'        => 'connexion',
            ],
            $cta
        );
    }

    public function test_engaged_without_enigme_shows_prompt(): void
    {
        $GLOBALS['force_admin_override'] = false;
        $GLOBALS['force_engage_override'] = true;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['progression'] = ['engagees' => 0, 'total' => 3, 'resolues' => 0, 'resolvables' => 2];
        $cta = generer_cta_chasse(123, 1);
        $this->assertSame(
            [
                'cta_html'    => '<a href="#chasse-enigmes-wrapper" class="bouton-secondaire">Voir les énigmes</a>',
                'cta_message' => '<p>✅ Vous participez à cette chasse</p><p>Commencez par consulter les énigmes disponibles</p>',
                'type'        => 'engage',
            ],
            $cta
        );
    }

}

