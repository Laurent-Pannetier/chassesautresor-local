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

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/affichage.php';

class EnigmeParticipationInfosTest extends TestCase
{
    public function setUp(): void
    {
        global $fields, $resolved, $wpdb;
        $fields = [
            10 => [
                'indices'                      => [],
                'enigme_mode_validation'       => 'automatique',
                'enigme_tentative_cout_points' => 5,
                'enigme_tentative_max'         => 10,
            ],
        ];
        $resolved = false;
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
}
