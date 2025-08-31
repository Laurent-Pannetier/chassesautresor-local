<?php

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('get_field')) {
    function get_field($key, $post_id)
    {
        $map = $GLOBALS['get_field_values'] ?? [];
        return $map[$post_id][$key] ?? true;
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
        $map = $GLOBALS['engagements'] ?? [];
        return $map[$enigme_id] ?? false;
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
        return $GLOBALS['posts'] ?? [
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

    public function test_menu_item_classes_reflect_cta_state(): void
    {
        $GLOBALS['is_admin'] = true;
        $GLOBALS['posts'] = [
            (object) ['ID' => 1],
            (object) ['ID' => 2],
            (object) ['ID' => 3],
            (object) ['ID' => 4],
            (object) ['ID' => 5],
            (object) ['ID' => 6],
        ];

        $GLOBALS['ctas'] = [
            1 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'en_cours',
            ],
            2 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'non_commencee',
            ],
            3 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'resolue',
            ],
            4 => [
                'etat_systeme'       => 'bloquee_pre_requis',
                'statut_utilisateur' => 'non_commencee',
            ],
            5 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'soumis',
            ],
            6 => [
                'etat_systeme'       => 'bloquee_chasse',
                'statut_utilisateur' => 'non_commencee',
            ],
        ];

        $GLOBALS['engagements']      = [1 => true];
        $GLOBALS['get_field_values'] = [
            1 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
            2 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
            3 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
            4 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
            5 => [
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
            6 => [
                'enigme_cache_complet'         => false,
                'enigme_tentative_cout_points' => 0,
                'enigme_mode_validation'       => 'aucune',
            ],
        ];

        $data = sidebar_prepare_chasse_nav(10, 5);
        $items = $data['menu_items'];

        $this->assertStringContainsString('<li data-enigme-id="1">', $items[0]);
        $this->assertStringContainsString('class="non-engagee"', $items[1]);
        $this->assertStringContainsString('class="succes"', $items[2]);
        $this->assertStringContainsString('class="bloquee"', $items[3]);
        $this->assertStringContainsString('class="en-attente"', $items[4]);
        $this->assertStringContainsString('class="incomplete"', $items[5]);
    }

    public function test_menu_item_icons_are_displayed(): void
    {
        $GLOBALS['is_admin'] = true;
        $GLOBALS['posts']    = [
            (object) ['ID' => 1],
            (object) ['ID' => 2],
            (object) ['ID' => 3],
            (object) ['ID' => 4],
            (object) ['ID' => 5],
        ];

        $GLOBALS['ctas'] = [
            1 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'en_cours',
            ],
            2 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'en_cours',
            ],
            3 => [
                'etat_systeme'       => 'accessible',
                'statut_utilisateur' => 'en_cours',
            ],
            4 => [
                'etat_systeme'       => 'bloquee_date',
                'statut_utilisateur' => 'non_commencee',
            ],
            5 => [
                'etat_systeme'       => 'bloquee_pre_requis',
                'statut_utilisateur' => 'non_commencee',
            ],
        ];

        $GLOBALS['get_field_values'] = [
            1 => [
                'enigme_mode_validation'       => 'aucune',
                'enigme_tentative_cout_points' => 0,
            ],
            2 => [
                'enigme_mode_validation'       => 'automatique',
                'enigme_tentative_cout_points' => 0,
            ],
            3 => [
                'enigme_mode_validation'       => 'manuelle',
                'enigme_tentative_cout_points' => 5,
            ],
            4 => [
                'enigme_mode_validation'       => 'aucune',
                'enigme_tentative_cout_points' => 0,
            ],
            5 => [
                'enigme_mode_validation'       => 'aucune',
                'enigme_tentative_cout_points' => 0,
            ],
        ];

        $data  = sidebar_prepare_chasse_nav(10, 5);
        $items = $data['menu_items'];

        $this->assertStringContainsString('enigme-menu__icon--bullet', $items[0]);
        $this->assertStringContainsString('Réponse automatique', $items[1]);
        $this->assertStringContainsString('validation manuelle', $items[2]);
        $this->assertStringContainsString("l'accès à cette chasse nécessite des points", $items[2]);
        $this->assertStringContainsString('hourglass', $items[3]);
        $this->assertStringContainsString('lock', $items[4]);
    }
}

