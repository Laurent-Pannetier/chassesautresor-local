<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return false;
    }
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;

        public string $user_login = '';

        public array $roles = [];

        /**
         * @param mixed $data
         */
        public function __construct($data = 0)
        {
            if (is_object($data)) {
                $this->ID         = isset($data->ID) ? (int) $data->ID : 0;
                $this->user_login = isset($data->user_login) ? (string) $data->user_login : '';
            } elseif (is_array($data)) {
                $this->ID         = isset($data['ID']) ? (int) $data['ID'] : 0;
                $this->user_login = isset($data['user_login']) ? (string) $data['user_login'] : '';
            } elseif (is_int($data)) {
                $this->ID         = $data;
                $this->user_login = 'user' . $data;
            } else {
                $this->user_login = (string) $data;
            }
        }

        public function add_role($role): void
        {
            if (!in_array($role, $this->roles, true)) {
                $this->roles[] = $role;
            }
        }

        public function remove_role($role): void
        {
            $this->roles = array_values(array_filter(
                $this->roles,
                static function ($registered_role) use ($role) {
                    return $registered_role !== $role;
                }
            ));
        }

        public function set_role($role): void
        {
            $this->roles = [$role];
        }
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        return false;
    }
}

if (!function_exists('get_organisateur_from_chasse')) {
    function get_organisateur_from_chasse($chasse_id)
    {
        return 5;
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value)
    {
        $login = ((int) $value === 42) ? 'demo' : 'regular';

        return new WP_User([
            'ID'         => (int) $value,
            'user_login' => $login,
        ]);
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

if (!function_exists('get_user_points')) {
    function get_user_points($user_id)
    {
        return 100;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, ...$args)
    {
        $value = $args[0] ?? null;

        if ($hook === 'ca_demo_organisateur_logins') {
            return ['demo'];
        }

        if ($hook === 'ca_demo_is_demo_hunt') {
            $chasse_id  = $args[1] ?? null;
            $overrides = $GLOBALS['force_demo_overrides'] ?? [];

            if ($chasse_id !== null && isset($overrides[$chasse_id])) {
                return (bool) $overrides[$chasse_id];
            }
        }

        return $value;
    }
}

if (!defined('CA_DEMO_ORGANISATEUR_LOGINS')) {
    define('CA_DEMO_ORGANISATEUR_LOGINS', []);
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
        $GLOBALS['get_field_values']            = [
            'utilisateurs_associes' => [],
        ];
        $cta                                    = generer_cta_chasse(123, 5);

        $this->assertSame(
            [
                'cta_html'    => '<button class="bouton-cta" disabled>Participer</button>',
                'cta_message' => '',
                'type'        => 'indisponible',
                'is_demo'     => false,
            ],
            $cta
        );
    }

    public function test_guest_gets_login_cta_without_message(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [
            'utilisateurs_associes' => [],
        ];
        $cta                                    = generer_cta_chasse(123, 0);

        $this->assertSame(
            [
                'cta_html'    => '<a href="https://example.com/wp-login.php?redirect_to=https%3A%2F%2Fexample.com%2Fchasse%2F123" class="bouton-cta bouton-cta--color">S\'identifier</a>',
                'cta_message' => '',
                'type'        => 'connexion',
                'is_demo'     => false,
            ],
            $cta
        );
    }

    public function test_engaged_without_enigme_shows_prompt(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = true;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [
            'utilisateurs_associes' => [],
        ];
        $cta                                    = generer_cta_chasse(123, 1);

        $this->assertSame(
            [
                'cta_html'    => '<a href="#chasse-enigmes-wrapper" class="bouton-secondaire">Voir mes énigmes</a>',
                'cta_message' => '<p>✅ Vous participez à cette chasse</p>',
                'type'        => 'engage',
                'is_demo'     => false,
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
            'utilisateurs_associes'          => [],
        ];

        $cta          = generer_cta_chasse(123, 5);
        $expected_url = 'https://example.com/chasse/123?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
                'is_demo'     => false,
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
            'utilisateurs_associes'          => [],
        ];

        $cta          = generer_cta_chasse(456, 7);
        $expected_url = 'https://example.com/chasse/456?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
                'is_demo'     => false,
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
            'utilisateurs_associes'          => [],
        ];

        $cta          = generer_cta_chasse(789, 11);
        $expected_url = 'https://example.com/chasse/789?edition=open&tab=stats';

        $this->assertSame(
            [
                'cta_html'    => '<a href="' . $expected_url . '" class="bouton-secondaire">Statistiques</a>',
                'cta_message' => '',
                'type'        => 'statistiques',
                'is_demo'     => false,
            ],
            $cta
        );
    }

    public function test_demo_hunt_returns_virtual_engagement_cta(): void
    {
        $GLOBALS['force_admin_override']        = false;
        unset($GLOBALS['force_engage_override']);
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['force_demo_overrides']        = [123 => true];
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'en_cours',
            'chasse_cache_statut_validation' => 'valide',
            'utilisateurs_associes'          => [
                ['ID' => 42],
            ],
        ];
        $GLOBALS['wpdb'] = new class
        {
            public string $prefix = 'wp_';

            public function prepare(string $query, ...$args): string
            {
                return $query;
            }

            public function get_var(string $query)
            {
                return null;
            }
        };

        $cta = generer_cta_chasse(123, 21);

        $this->assertTrue(ca_demo_is_demo_hunt(123));
        $this->assertSame('engage', $cta['type']);
        $this->assertTrue($cta['is_demo']);
        $this->assertStringContainsString('Voir mes énigmes', $cta['cta_html']);
        $this->assertStringNotContainsString('<form', $cta['cta_html']);
    }

    public function test_finished_hunt_requires_engagement_cta(): void
    {
        $GLOBALS['force_admin_override']        = false;
        $GLOBALS['force_engage_override']       = false;
        $GLOBALS['force_organisateur_override'] = false;
        $GLOBALS['get_field_values']            = [
            'chasse_cache_statut'            => 'termine',
            'chasse_cache_statut_validation' => 'valide',
            'utilisateurs_associes'          => [],
        ];

        $cta = generer_cta_chasse(123, 5);

        $this->assertSame(
            [
                'cta_html'    => '<form method="post" action="/traitement-engagement" class="cta-chasse-form"><input type="hidden" name="chasse_id" value="123"><button type="submit" class="bouton-cta bouton-cta--color">Redécouvrir</button></form>',
                'cta_message' => 'Cette chasse est terminée',
                'type'        => 'engager',
                'is_demo'     => false,
            ],
            $cta,
        );
    }
}

