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
}
