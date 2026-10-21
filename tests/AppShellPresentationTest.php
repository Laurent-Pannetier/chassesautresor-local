<?php

use PHPUnit\Framework\TestCase;

class AppShellPresentationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_team_stats_without_a_hunt_do_not_invent_values(): void
    {
        $this->bootShell();

        ob_start();
        cta_shell_render_team_stats();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Aucune chasse engagée pour le moment.', $html);
        $this->assertStringNotContainsString('Emplacement', $html);
        $this->assertStringNotContainsString('team-stat', $html);
        $text = trim(strip_tags($html));
        $this->assertSame(0, preg_match('/\d/', $text));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hunt_banner_stays_empty_without_a_hunt_image(): void
    {
        $this->bootShell();

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('hunt-banner', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_virtual_pages_are_not_left_as_an_empty_singular_query(): void
    {
        $this->bootShell();

        $query = new stdClass();
        $query->is_404 = true;
        $query->is_single = true;
        $query->is_singular = true;
        $query->is_page = false;
        $query->queried_object = null;
        $query->queried_object_id = null;
        $query->post = null;
        $query->posts = [];
        $query->post_count = 0;
        $GLOBALS['post'] = null;

        cta_shell_normalize_virtual_query($query);

        $this->assertFalse($query->is_404);
        $this->assertFalse($query->is_single);
        $this->assertFalse($query->is_singular);
        $this->assertNull($query->queried_object);
        $this->assertNull($GLOBALS['post']);
    }

    public function test_home_pages_drop_the_welcome_and_tools_leave_the_home(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $layout = (string) file_get_contents($theme . '/templates/myaccount/layout.php');
        $organizer = (string) file_get_contents($theme . '/template-parts/myaccount/dashboard-organisateur.php');
        $admin = (string) file_get_contents($theme . '/template-parts/myaccount/dashboard-admin.php');
        $outils = (string) file_get_contents($theme . '/template-parts/shell/outils.php');

        $this->assertStringNotContainsString('Bienvenue', $layout);
        $this->assertStringNotContainsString('cat_render_cockpit_demo_notice', $layout);
        $this->assertStringNotContainsString(
            'présentée avant sa publication officielle',
            (string) file_get_contents($theme . '/inc/single-hunt-home.php')
        );
        $this->assertStringNotContainsString('Accès rapide édition', $organizer);
        $this->assertStringNotContainsString('Accès rapide édition', $admin);
        $this->assertStringNotContainsString('cat_render_hunt_quick_edit_card', $outils);
        $this->assertStringNotContainsString('Pilotage de la chasse', $outils);
        $this->assertStringNotContainsString('Éditez vos entités', $outils);
        $this->assertStringContainsString('myaccount-main', $outils);
        $this->assertStringContainsString('dashboard-grid', $outils);
        $this->assertStringContainsString('renderProtectionCard', $outils);
        $this->assertStringContainsString('Taux de conversion', $outils);
    }

    public function test_trophy_icon_is_shared_by_the_menu_and_the_top_bar(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $sidebar = (string) file_get_contents($theme . '/template-parts/shell/sidebar.php');
        $topbar = (string) file_get_contents($theme . '/template-parts/shell/topbar.php');

        $this->assertStringContainsString("cta_shell_icon(", $sidebar);
        $this->assertStringNotContainsString('organizer-nav', $sidebar);
        $this->assertStringContainsString("cta_shell_icon('trophy')", $topbar);
        $this->assertStringContainsString("cta_shell_icon('flame')", $topbar);
        $this->assertStringContainsString("cta_shell_icon('logout')", $topbar);
        $this->assertStringNotContainsString('Chasse au trésor', $topbar);
        $this->assertStringContainsString('Antre de feu', $topbar);
        $this->assertStringContainsString('solo', $topbar);
        $this->assertStringContainsString('>0<', $topbar);
        $this->assertStringNotContainsString('customer-logout', $topbar);
    }

    public function test_profile_menu_script_no_longer_offers_logout(): void
    {
        $script = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/js/header-account-menu.js'
        );

        $this->assertStringNotContainsString('logoutUrl', $script);
        $this->assertStringNotContainsString('Déconnexion', $script);
    }

    public function test_day_theme_uses_the_light_shell_background_for_the_enigmas_canvas(): void
    {
        $scss = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_app-shell.scss'
        );

        $this->assertMatchesRegularExpression(
            '/html\[data-theme="day"\] \.home-enigmas-preserve \{\s*'
            . 'background-color:\s*var\(--shell-bg\);\s*'
            . 'background-image:\s*none;\s*'
            . 'color:\s*var\(--shell-text\);/s',
            $scss
        );
    }

    public function test_player_home_keeps_enigmas_and_moves_team_stats_off_home(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $dashboard = (string) file_get_contents(
            $theme . '/template-parts/myaccount/dashboard-joueur.php'
        );
        $statsPage = (string) file_get_contents($theme . '/template-parts/shell/progression.php');
        $loop = (string) file_get_contents(
            $theme . '/template-parts/enigme/chasse-partial-boucle-enigmes.php'
        );

        $banner = strpos($dashboard, 'cta_shell_render_hunt_banner');
        $enigmas = strpos($dashboard, 'cat_render_single_hunt_home_enigmas');

        $this->assertNotFalse($banner);
        $this->assertNotFalse($enigmas);
        $this->assertLessThan($enigmas, $banner);
        $placeholder = (string) file_get_contents($theme . '/templates/shell-placeholder.php');
        $navigation = (string) file_get_contents($theme . '/inc/myaccount-functions.php');

        $this->assertStringNotContainsString('cta_shell_render_team_stats', $dashboard);
        $this->assertStringContainsString('cta_shell_render_team_stats', $statsPage);
        $this->assertStringContainsString("get_template_part('template-parts/shell/progression')", $placeholder);
        $this->assertStringNotContainsString("'statistiques'", $navigation);
        $this->assertStringContainsString('class="bloc-enigmes-chasse"', $loop);
        $this->assertStringNotContainsString('team-stat', $loop);
        $this->assertStringNotContainsString('app-shell', $loop);
    }

    public function test_staff_homes_keep_the_hunt_banner_before_the_enigmas(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        foreach (['dashboard-organisateur.php', 'dashboard-admin.php'] as $file) {
            $dashboard = (string) file_get_contents($theme . '/template-parts/myaccount/' . $file);
            $banner = strpos($dashboard, 'cta_shell_render_hunt_banner');
            $enigmas = strpos($dashboard, 'cat_render_single_hunt_home_enigmas');

            $this->assertNotFalse($banner, $file);
            $this->assertNotFalse($enigmas, $file);
            $this->assertLessThan($enigmas, $banner, $file);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_home_sidebar_is_omitted_on_an_enigma_page(): void
    {
        if (!function_exists('is_singular')) {
            eval('function is_singular($postType = "") {
                $current = $GLOBALS["cat_test_singular"] ?? "";
                if ($postType === "") {
                    return $current !== "";
                }
                return $current === $postType;
            }');
        }
        $this->bootShell();

        $GLOBALS['cat_test_singular'] = 'enigme';
        $this->assertFalse(cta_shell_shows_sidebar());

        $GLOBALS['cat_test_singular'] = 'page';
        $this->assertTrue(cta_shell_shows_sidebar());

        $shell = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/app-shell.php'
        );
        $topbar = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/template-parts/shell/topbar.php'
        );
        $this->assertStringContainsString('app-shell--no-nav', $shell);
        $this->assertStringContainsString('cta_shell_shows_sidebar()', $topbar);
    }

    private function bootShell(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        if (!function_exists('__')) {
            eval('function __($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_html')) {
            eval('function esc_html($text){return $text;}');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($text){return $text;}');
        }
        if (!function_exists('esc_attr__')) {
            eval('function esc_attr__($text, $domain = null){return $text;}');
        }
        if (!function_exists('add_action')) {
            eval('function add_action($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!function_exists('add_filter')) {
            eval('function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }

        require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/app-shell.php';
    }
}
