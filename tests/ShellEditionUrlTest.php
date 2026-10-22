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
        self::assertSame('revenus', cta_shell_normalize_edition_section('organisateur', 'points'));
        self::assertSame('revenus', cta_shell_normalize_edition_section('organisateur', 'revenus'));
        self::assertSame('parametres', cta_shell_normalize_edition_section('organisateur', 'tentatives'));
        self::assertSame('parametres', cta_shell_normalize_edition_section('enigme', 'points'));
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
        self::assertStringContainsString('cta_shell_render_organizer_edit_card', $outils);
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

    public function testOrganizerEditorLivesOnTheOutilsPage(): void
    {
        $url = cta_shell_edition_url('organisateur', 4, 'points');
        self::assertStringStartsWith('https://example.test/outils/', $url);
        self::assertStringContainsString('organisateur=4', $url);
        self::assertStringContainsString('section=revenus', $url);

        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $plugin = dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core';
        $header = (string) file_get_contents($theme . '/template-parts/organisateur/organisateur-header.php');
        $enqueue = (string) file_get_contents($theme . '/inc/edition/edition-organisateur.php');
        $editor = (string) file_get_contents($theme . '/assets/js/organisateur-edit.js');
        $styles = (string) file_get_contents($theme . '/assets/scss/_edition.scss');
        $template = (string) file_get_contents(
            $theme . '/template-parts/organisateur/organisateur-edition-main.php'
        );
        $lifecycle = (string) file_get_contents($plugin . '/src/Content/hunt-lifecycle-functions.php');
        $messages = (string) file_get_contents($plugin . '/src/Messages/important-messages.php');
        $tabs = (string) file_get_contents($theme . '/assets/js/core/modal-tabs.js');

        self::assertStringNotContainsString('toggle-mode-edition', $header);
        self::assertStringNotContainsString('organisateur-edition-main', $header);
        self::assertStringContainsString("cta_shell_edition_post_id('organisateur')", $enqueue);
        self::assertStringNotContainsString("is_singular('organisateur')", $enqueue);
        self::assertStringNotContainsString('toggle-mode-edition', $editor);
        self::assertStringNotContainsString('formulaire-coordonnees', $editor);
        self::assertStringNotContainsString('edition-panel-modal', $styles);
        self::assertStringContainsString('edition-panel-organisateur', $styles);
        self::assertStringContainsString('Édition organisateur', $template);
        self::assertStringContainsString('champ-texte-editeur', $template);
        self::assertStringContainsString('champ-coordonnees-editeur', $template);
        self::assertStringNotContainsString('panneau-fermer', $template);
        self::assertStringNotContainsString('organisateur-edition-description', $template);
        self::assertStringNotContainsString("'edition' => 'open'", $lifecycle);
        self::assertStringNotContainsString("'edition' => 'open'", $messages);
        self::assertStringContainsString('organisateur-tab-revenus', $tabs);
        $modal = (string) file_get_contents($theme . '/template-parts/modals/modal-conversion.php');
        self::assertStringNotContainsString('render_conversion_modal_content', $modal);
        self::assertStringContainsString('ConversionModalRenderer', $modal);
        self::assertFileDoesNotExist(
            $theme . '/template-parts/organisateur/panneaux/organisateur-edition-description.php'
        );
    }
}
