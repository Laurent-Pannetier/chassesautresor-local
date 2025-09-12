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

if (!function_exists('_x')) {
    function _x($text, $context, $domain = null)
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

if (!function_exists('current_time')) {
    function current_time($type)
    {
        if ($type === 'timestamp') {
            return strtotime('2023-11-10 10:00:00');
        }

        return '';
    }
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
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

if (!function_exists('wp_timezone')) {
    function wp_timezone()
    {
        return new DateTimeZone('UTC');
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
        global $mocked_posts;
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

if (!function_exists('get_post_field')) {
    function get_post_field($field, $post_id)
    {
        global $mocked_post_dates;
        if ($field === 'post_date') {
            return $mocked_post_dates[$post_id] ?? null;
        }

        return null;
    }
}

if (!function_exists('get_post_timestamp')) {
    function get_post_timestamp($post_id, $field = 'date')
    {
        $date = get_post_field('post_date', $post_id);
        return $date ? strtotime($date) : false;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

class EnigmeParticipationInfosTest extends TestCase
{
    public function setUp(): void
    {
        global $fields, $resolved, $wpdb, $mocked_posts, $mocked_titles, $mocked_post_dates;
        $fields = [
            10 => [
                'indices'                      => [],
                'enigme_mode_validation'       => 'automatique',
                'enigme_tentative_cout_points' => 5,
                'enigme_tentative_max'         => 10,
            ],
        ];
        $resolved          = false;
        $mocked_posts      = [];
        $mocked_titles     = [];
        $mocked_post_dates = [];
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
    public function test_programmed_indices_date_format(): void
    {
        global $mocked_posts, $fields;
        global $mocked_post_dates;
        $mocked_posts = [401, 402, 403];

        $fields[401]['indice_cache_etat_systeme'] = 'programme';
        $fields[401]['indice_cout_points']        = 0;
        $fields[401]['indice_date_disponibilite'] = '10/11/2023 18:00';

        $fields[402]['indice_cache_etat_systeme'] = 'programme';
        $fields[402]['indice_cout_points']        = 0;
        $fields[402]['indice_date_disponibilite'] = '2023-11-12 15:30:00';

        $fields[403]['indice_cache_etat_systeme'] = 'programme';
        $fields[403]['indice_cout_points']        = 0;
        // Fallback to the post's publication date when meta is absent.
        $mocked_post_dates[403] = '2024-11-20 12:00:00';

        ob_start();
        render_enigme_participation(10, 'defaut', 1);
        $html = ob_get_clean();

        $this->assertStringContainsString("Aujourd'hui à 18:00", $html);
        $this->assertStringContainsString('12/11/23 à 15:30', $html);
        $this->assertStringContainsString('20/11/24', $html);
    }
}
