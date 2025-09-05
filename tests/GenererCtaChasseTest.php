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

if (!function_exists('est_organisateur')) {
    function est_organisateur($user_id = null) {
        return false;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id) {
        return $GLOBALS['chasse_fields'][$field] ?? null;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id) {
        return "https://example.com/chasse/{$id}";
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

if (!function_exists('wp_login_url')) {
    function wp_login_url($redirect = '', $force_reauth = false) {
        $base = 'https://example.com/wp-login.php';

        return $redirect
            ? $base . '?redirect_to=' . rawurlencode($redirect)
            : $base;
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp) {
        return '';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'nonce';
    }
}

if (!function_exists('utilisateur_est_engage_dans_chasse')) {
    function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) {
        return $GLOBALS['is_engage'] ?? false;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

class GenererCtaChasseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['chasse_fields']               = [];
        $GLOBALS['fields']                      = &$GLOBALS['chasse_fields'];
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
    }

    public function test_admin_or_organizer_gets_disabled_button(): void
    {
        $GLOBALS['force_admin_override'] = true;
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
        $cta = generer_cta_chasse(123, 0);
        $this->assertSame(
            [
                'cta_html'    => '<a href="https://example.com/wp-login.php?redirect_to=http%3A%2F%2Fexample.com%2F123" class="bouton-cta bouton-cta--color">S\'identifier</a>',
                'cta_message' => '',
                'type'        => 'connexion',
            ],
            $cta
        );
    }

    public function test_engaged_without_enigme_shows_prompt(): void
    {
        $GLOBALS['force_engage_override'] = true;
        $cta = generer_cta_chasse(123, 1);
        $this->assertSame(
            [
                'cta_html'    => '<a href="#chasse-enigmes-wrapper" class="bouton-secondaire">Voir mes énigmes</a>',
                'cta_message' => '<p>✅ Vous participez à cette chasse</p>',
                'type'        => 'engage',
            ],
            $cta
        );
    }

    public function test_organizer_can_cancel_validation_request(): void
    {
        $GLOBALS['force_organisateur_override'] = true;
        $GLOBALS['chasse_fields'] = [
            123 => [
                'chasse_cache_statut_validation' => 'en_attente',
            ],
        ];
        $cta = generer_cta_chasse(123, 5);
        $this->assertSame('annuler_validation', $cta['type']);
    }

    public function test_standard_user_sees_pending_button(): void
    {
        $GLOBALS['chasse_fields'] = [
            123 => [
                'chasse_cache_statut_validation' => 'en_attente',
            ],
        ];
        $cta = generer_cta_chasse(123, 5);
        $this->assertSame(
            [
                'cta_html'    => '<span class="bouton-cta bouton-cta--pending" aria-disabled="true">Demande de validation en cours</span>',
                'cta_message' => '',
                'type'        => 'en_attente',
            ],
            $cta
        );
    }
}

