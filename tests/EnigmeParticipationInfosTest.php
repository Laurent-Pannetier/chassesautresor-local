<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('esc_html')) {
    function esc_html($text)
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

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('get_field')) {
    function get_field($key, $id)
    {
        global $fields;
        return $fields[$id][$key] ?? null;
    }
}

if (!function_exists('get_user_points')) {
    function get_user_points($user_id)
    {
        return 100;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($cap)
    {
        return false;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($uid, $cid)
    {
        return false;
    }
}

if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($enigme_id)
    {
        return 1;
    }
}

if (!function_exists('compter_tentatives_du_jour')) {
    function compter_tentatives_du_jour($uid, $enigme_id)
    {
        return 3;
    }
}

if (!function_exists('remove_accents')) {
    function remove_accents($string)
    {
        return $string;
    }
}

if (!function_exists('wp_date')) {
    function wp_date($format, $timestamp, $timezone = null)
    {
        return date($format, $timestamp);
    }
}

if (!function_exists('locate_template')) {
    function locate_template($template)
    {
        return false;
    }
}

if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = [])
    {
    }
}

if (!function_exists('cat_debug')) {
    function cat_debug($msg)
    {
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = [])
    {
        global $mocked_posts, $last_get_posts_args;
        $last_get_posts_args[] = $args;
        return $mocked_posts ?? [];
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id)
    {
        global $mocked_titles;
        return $mocked_titles[$post_id] ?? '';
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null)
    {
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0)
    {
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '')
    {
        return true;
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone()
    {
        return new DateTimeZone('UTC');
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0)
    {
        return time();
    }
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        if ($name === 'date_format') {
            return 'd/m/y';
        }

        return $default;
    }
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

class EnigmeParticipationInfosTest extends TestCase
{
    public function setUp(): void
    {
        global $fields, $resolved, $wpdb, $mocked_posts, $mocked_titles, $last_get_posts_args;
        $fields = [
            10 => [
                'indices'                      => [],
                'enigme_mode_validation'       => 'automatique',
                'enigme_tentative_cout_points' => 5,
                'enigme_tentative_max'         => 10,
            ],
        ];
        $resolved = false;
        $mocked_posts        = [];
        $mocked_titles       = [];
        $last_get_posts_args = [];
        $wpdb = new class {
            public string $prefix = 'wp_';
            public function prepare($query, ...$args)
            {
                return $query;
            }
            public function get_var($query)
            {
                global $resolved;
                return $resolved ? 'resolue' : null;
            }
        };
    }

    public function test_infos_displayed_when_not_solved(): void
    {
        global $resolved;
        $resolved = false;
        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();
        $this->assertStringContainsString('Solde', $html);
        $this->assertStringContainsString('Tentatives quotidiennes', $html);
    }

    public function test_infos_hidden_after_resolution(): void
    {
        global $resolved;
        $resolved = true;
        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();
        $this->assertStringNotContainsString('Solde', $html);
        $this->assertStringNotContainsString('Tentatives quotidiennes', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_indices_links_displayed(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [101, 102];
        $fields[101]['indice_cout_points'] = 3;
        $fields[102]['indice_cout_points'] = 4;

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString('Indice #1', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_programmed_indices_date_format_future(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [405];
        $fields[405]['indice_cout_points']        = 0;
        $fields[405]['indice_cache_etat_systeme'] = 'programme';
        $fields[405]['indice_date_disponibilite'] = date('d/m/Y H:i', time() + 30 * DAY_IN_SECONDS);

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();
        $this->assertStringContainsString('<span class="indice-label', $html);
        $expected = date('d/m/y', time() + 30 * DAY_IN_SECONDS);
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_programmed_indices_label_today(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [501];
        $fields[501]['indice_cout_points']        = 0;
        $fields[501]['indice_cache_etat_systeme'] = 'programme';
        $fields[501]['indice_date_disponibilite'] = date('d/m/Y H:i', time() + HOUR_IN_SECONDS);

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString('indice-label', $html);
        $this->assertMatchesRegularExpression('/Aujourd’hui à \d{2}:\d{2}/', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_programmed_indices_label_within_week(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [502];
        $fields[502]['indice_cout_points']        = 0;
        $fields[502]['indice_cache_etat_systeme'] = 'programme';
        $fields[502]['indice_date_disponibilite'] = date('d/m/Y H:i', time() + 2 * DAY_IN_SECONDS);

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $expected = date('d/m/y \à H:i', time() + 2 * DAY_IN_SECONDS);
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_programmed_indices_past_date_is_available(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [503];
        $fields[503]['indice_cout_points']        = 0;
        $fields[503]['indice_cache_etat_systeme'] = 'programme';
        $fields[503]['indice_date_disponibilite'] = date('d/m/Y H:i', time() - HOUR_IN_SECONDS);

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString('indice-link', $html);
        $this->assertStringNotContainsString('indice-link--upcoming', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_posts_includes_pending_status(): void
    {
        global $last_get_posts_args;
        render_enigme_participation(10, 'defaut', 1);
        $this->assertNotEmpty($last_get_posts_args);
        foreach ($last_get_posts_args as $args) {
            $this->assertContains('pending', $args['post_status']);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_free_indices_locked_initially(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [201];
        $fields[201]['indice_cout_points'] = 0;

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString('indice-link--locked', $html);
        $this->assertStringContainsString('fa-lightbulb', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_reponse_before_indices(): void
    {
        global $mocked_posts, $fields, $resolved;
        $mocked_posts = [301];
        $fields[301]['indice_cout_points'] = 2;
        $resolved = true;

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $pos_reponse = strpos($html, 'zone-reponse');
        $pos_indices = strpos($html, 'zone-indices');

        $this->assertNotFalse($pos_reponse);
        $this->assertNotFalse($pos_indices);
        $this->assertLessThan(
            $pos_indices,
            $pos_reponse,
            'La section "Votre réponse" doit précéder les indices.'
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_separator_displayed_with_indices(): void
    {
        global $mocked_posts, $fields;
        $mocked_posts = [401];
        $fields[401]['indice_cout_points'] = 0;

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString('reponse-indices-separator', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_no_separator_without_indices(): void
    {
        global $mocked_posts;
        $mocked_posts = [];

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringNotContainsString('reponse-indices-separator', $html);
    }
}
