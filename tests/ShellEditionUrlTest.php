<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ShellEditionUrlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        if (!function_exists('add_action')) {
            function add_action(): void
            {
            }
        }
        if (!function_exists('add_filter')) {
            function add_filter(): void
            {
            }
        }
        if (!function_exists('cta_shell_location_url')) {
            function cta_shell_location_url(string $slug): string
            {
                return 'https://example.test/' . $slug . '/';
            }
        }
        if (!function_exists('add_query_arg')) {
            function add_query_arg($args, string $url): string
            {
                return $url . '?' . http_build_query($args);
            }
        }

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/shell-edition.php';
    }

    public function testLegacyTabNamesBecomeDashboardSections(): void
    {
        self::assertSame('parametres', cta_shell_normalize_edition_section('enigme', 'param'));
        self::assertSame('tentatives', cta_shell_normalize_edition_section('enigme', 'soumission'));
        self::assertSame('parametres', cta_shell_normalize_edition_section('chasse', 'tentatives'));
        self::assertSame('stats', cta_shell_normalize_edition_section('chasse', 'stats'));
    }

    public function testEditionUrlStaysOnTheOutilsScreen(): void
    {
        $url = cta_shell_edition_url('enigme', 12, 'soumission');

        self::assertStringStartsWith('https://example.test/outils/', $url);
        self::assertStringContainsString('enigme=12', $url);
        self::assertStringContainsString('section=tentatives', $url);
        self::assertStringNotContainsString('edition=open', $url);
    }

    public function testPublicGearsPointAtTheDashboardEditor(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $fiche = (string) file_get_contents($theme . '/inc/enigme/affichage.php');
        $cards = (string) file_get_contents($theme . '/template-parts/enigme/chasse-partial-boucle-enigmes.php');
        $hunt = (string) file_get_contents($theme . '/template-parts/chasse/chasse-affichage-complet.php');
        $outils = (string) file_get_contents($theme . '/template-parts/shell/outils.php');
        $sidebar = (string) file_get_contents($theme . '/template-parts/shell/sidebar.php');
        $sommaire = (string) file_get_contents(
            $theme . '/template-parts/enigme/chasse-partial-sommaire-enigmes.php'
        );

        self::assertStringContainsString('cta_shell_edition_url', $fiche);
        self::assertStringNotContainsString('toggle-mode-edition-enigme', $fiche);
        self::assertStringContainsString('cta_shell_edition_url', $cards);
        self::assertStringNotContainsString('toggle-mode-edition-enigme', $cards);
        self::assertStringContainsString('cta_shell_edition_url', $hunt);
        self::assertStringNotContainsString('toggle-mode-edition-chasse', $hunt);
        self::assertStringContainsString('cta_shell_render_hunt_edit_card', $outils);
        self::assertStringContainsString('cta_shell_render_outils_submenu', $sidebar);
        self::assertStringContainsString('cta_shell_edition_url', $sommaire);
    }

    public function testPublicPagesNoLongerBootTheOverlayEditor(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $plugin = dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core';
        $chasseEnqueue = (string) file_get_contents($theme . '/inc/edition/edition-chasse.php');
        $enigmeEnqueue = (string) file_get_contents($theme . '/inc/edition/edition-enigme.php');
        $core = (string) file_get_contents($theme . '/inc/edition/edition-core.php');
        $functions = (string) file_get_contents($theme . '/functions.php');
        $chasseJs = (string) file_get_contents($theme . '/assets/js/chasse-edit.js');
        $enigmeJs = (string) file_get_contents($theme . '/assets/js/enigme-edit.js');
        $styles = (string) file_get_contents($theme . '/assets/scss/_edition.scss');
        $navigation = (string) file_get_contents(
            $plugin . '/src/Progress/hunt-navigation-functions.php'
        );

        $headStart = strpos($functions, 'function forcer_acf_form_head_chasse');
        $headEnd = strpos($functions, 'TÂCHES QUOTIDIENNES', $headStart ?: 0);
        $head = ($headStart !== false && $headEnd !== false)
            ? substr($functions, $headStart, $headEnd - $headStart)
            : '';

        self::assertStringNotContainsString("is_singular('chasse')", $chasseEnqueue);
        self::assertStringNotContainsString("is_singular('enigme')", $enigmeEnqueue);
        self::assertStringNotContainsString('organisateur-edit', $enigmeEnqueue);
        self::assertStringNotContainsString('scroll-to-enigmes', $core);
        self::assertNotSame('', $head);
        self::assertStringNotContainsString("is_singular('chasse')", $head);
        self::assertStringNotContainsString('toggle-mode-edition-chasse', $chasseJs);
        self::assertStringNotContainsString('bouton-enregistrer-recompense', $chasseJs);
        self::assertStringNotContainsString('toggle-mode-edition-enigme', $enigmeJs);
        self::assertStringNotContainsString('panneau-variantes-enigme', $enigmeJs);
        self::assertStringNotContainsString('edition-active-indice', $styles);
        self::assertStringNotContainsString("'edition' => 'open'", $navigation);
    }
}
