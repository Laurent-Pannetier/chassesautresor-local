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

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

class EnigmeParticipationInfosTest extends TestCase
{
    public function setUp(): void
    {
        global $fields, $resolved, $wpdb, $mocked_posts, $mocked_titles;
        $fields = [
            10 => [
                'indices'                      => [],
                'enigme_mode_validation'       => 'automatique',
                'enigme_tentative_cout_points' => 5,
                'enigme_tentative_max'         => 10,
            ],
        ];
        $resolved = false;
        $mocked_posts  = [];
        $mocked_titles = [];
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
}
