<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return false;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        return false;
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id = null, $format_value = true)
    {
        return $GLOBALS['get_field_values'][$field] ?? null;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id)
    {
        return 'post';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id)
    {
        return "https://example.com/chasse/{$id}";
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain)
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!function_exists('__')) {
    function __($text, $domain)
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return $text;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name, $referer = true, $echo = false)
    {
        return '';
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        return $path;
    }
}

if (!function_exists('wp_login_url')) {
    function wp_login_url($redirect = '', $force_reauth = false)
    {
        $base = 'https://example.com/wp-login.php';

        return $redirect
            ? $base . '?redirect_to=' . rawurlencode($redirect)
            : $base;
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp)
    {
        return '';
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/chasse-functions.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GenererCtaChasseTest extends TestCase
{
    public function test_admin_or_organizer_gets_disabled_button(): void
    {
        $GLOBALS['force_admin_override']        = true;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [];
        $cta                                    = generer_cta_chasse(123, 5);

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
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [];
        $cta                                    = generer_cta_chasse(123, 0);

        $this->assertSame(
            [
                'cta_html'    => '<a href="https://example.com/wp-login.php?redirect_to=https%3A%2F%2Fexample.com%2Fchasse%2F123" class="bouton-cta bouton-cta--color">S\'identifier</a>',
                'cta_message' => '',
                'type'        => 'connexion',
            ],
            $cta
        );
    }

    public function test_engaged_without_enigme_shows_prompt(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = true;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [];
        $cta                                    = generer_cta_chasse(123, 1);

        $this->assertSame(
            [
                'cta_html'    => '<a href="#chasse-enigmes-wrapper" class="bouton-secondaire">Voir mes énigmes</a>',
                'cta_message' => '<p>✅ Vous participez à cette chasse</p>',
                'type'        => 'engage',
            ],
            $cta
        );
    }

    public function test_organizer_in_progress_shows_statistics_link(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = true;
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'en_cours',
            'chasse_cache_statut_validation' => 'valide',
        ];

        $cta          = generer_cta_chasse(123, 5);
        $expected_url = 'https://example.com/chasse/123?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
            ],
            $cta
        );
    }

    public function test_organizer_with_active_validation_shows_statistics_link(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = true;
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'en_cours',
            'chasse_cache_statut_validation' => 'active',
        ];

        $cta          = generer_cta_chasse(456, 7);
        $expected_url = 'https://example.com/chasse/456?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
            ],
            $cta
        );
    }

    public function test_organizer_with_paid_status_shows_statistics_link(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = true;
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'payante',
            'chasse_cache_statut_validation' => 'valide',
        ];

        $cta          = generer_cta_chasse(789, 11);
        $expected_url = 'https://example.com/chasse/789?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
            ],
            $cta
        );
    }
    public function test_finished_hunt_requires_engagement_cta(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'termine',
            'chasse_cache_statut_validation' => 'valide',
        ];

        $cta = generer_cta_chasse(123, 5);

        $this->assertSame(
            [
                'cta_html'    => '<form method="post" action="/traitement-engagement" class="cta-chasse-form"><input type="hidden" name="chasse_id" value="123"><button type="submit" class="bouton-cta bouton-cta--color">Redécouvrir</button></form>',
                'cta_message' => 'Cette chasse est terminée',
                'type'        => 'engager',
            ],
            $cta,
        );
    }
}

