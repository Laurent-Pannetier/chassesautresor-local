<?php

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('get_field')) {
    function get_field($key, $post_id)
    {
        return true;
    }
}

if (!function_exists('utilisateur_peut_ajouter_enigme')) {
    function utilisateur_peut_ajouter_enigme($chasse_id)
    {
        return false;
    }
}

if (!function_exists('utilisateur_est_engage_dans_enigme')) {
    function utilisateur_est_engage_dans_enigme($user_id, $enigme_id)
    {
        return false;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id)
    {
        return 'Enigme ' . $id;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($id)
    {
        return '#';
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($text)
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

if (!function_exists('add_action')) {
    function add_action($hook, $callback)
    {
    }
}

if (!function_exists('user_can')) {
    function user_can($user_id, $cap)
    {
        return $GLOBALS['is_admin'] ?? false;
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        return $GLOBALS['is_orga_assoc'] ?? false;
    }
}

if (!function_exists('recuperer_enigmes_pour_chasse')) {
    function recuperer_enigmes_pour_chasse($chasse_id)
    {
        return [
            (object) ['ID' => 1],
            (object) ['ID' => 2],
        ];
    }
}

if (!function_exists('get_cta_enigme')) {
    function get_cta_enigme($enigme_id, $user_id)
    {
        return $GLOBALS['ctas'][$enigme_id];
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/sidebar.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SidebarPrepareChasseNavTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['ctas'] = [
            1 => [
                'etat_systeme'      => 'accessible',
                'statut_utilisateur' => 'non_commencee',
            ],
            2 => [
                'etat_systeme'      => 'bloquee_pre_requis',
                'statut_utilisateur' => 'non_commencee',
            ],
        ];

        $GLOBALS['is_admin'] = false;
        $GLOBALS['is_orga_assoc'] = false;
    }

    public function test_player_skips_blocked_enigma(): void
    {
        $data = sidebar_prepare_chasse_nav(10, 5);

        $this->assertSame([1], $data['visible_ids']);
    }

    public function test_admin_sees_all_enigmas(): void
    {
        $GLOBALS['is_admin'] = true;

        $data = sidebar_prepare_chasse_nav(10, 5);

        $this->assertSame([1, 2], $data['visible_ids']);
    }

    public function test_associated_organizer_sees_all_enigmas(): void
    {
        $GLOBALS['is_orga_assoc'] = true;

        $data = sidebar_prepare_chasse_nav(10, 5);

        $this->assertSame([1, 2], $data['visible_ids']);
    }
}

