<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SingleHuntHomepageTemplateTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testFrontPageRoutesBothSiteModes(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/front-page.php');

        self::assertStringContainsString('cat_is_single_hunt_mode()', $template);
        self::assertStringContainsString('cat_single_hunt_home_audience()', $template);
        self::assertStringContainsString("get_template_part('template-parts/home/platform')", $template);
        self::assertStringContainsString("get_template_part('template-parts/home/single-hunt')", $template);
        self::assertStringContainsString('content-dashboard-joueur.php', $template);
        self::assertStringContainsString('content-profil.php', (string) file_get_contents(
            self::THEME_PATH . '/woocommerce/myaccount/my-account.php'
        ));
    }

    public function testSingleHuntHomepageHasNoCatalogControls(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/home/single-hunt.php'
        );

        self::assertStringContainsString('cat_get_primary_hunt_id()', $template);
        self::assertStringContainsString('cat_is_demo_mode()', $template);
        self::assertStringNotContainsString('single-hunt-home__facts', $template);
        self::assertStringNotContainsString('Première visite', $template);
        self::assertStringContainsString('single-hunt-home__story', $template);
        self::assertStringContainsString('hero-start', (string) file_get_contents(
            self::THEME_PATH . '/template-parts/headers/front-page-latest-hero.php'
        ));
        self::assertStringContainsString('Départ le %s', (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-home.php'
        ));
        self::assertStringNotContainsString('single-hunt-home__riddles', $template);
        self::assertStringNotContainsString('Le parcours', $template);
        self::assertStringNotContainsString('home-hunts__toolbar', $template);
        self::assertStringNotContainsString('ca_home_filter_chasse_ids', $template);
    }

    public function testPlatformHomepageKeepsTheExistingCatalog(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/template-parts/home/platform.php');

        self::assertStringContainsString('home-hunts__toolbar', $template);
        self::assertStringContainsString('ca_home_filter_chasse_ids', $template);
    }

    public function testSingleHuntHeroIsImmediatelyVisibleWithoutThePlatformAnimation(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/headers/front-page-latest-hero.php'
        );

        self::assertStringContainsString("' is-home-hero-visible'", $template);
        self::assertStringContainsString('$single_hunt ? \'false\' : \'true\'', $template);
    }

    public function testSingleHuntNavigationKeepsASingleEnigmesLinkForPlayersWhoMayEnter(): void
    {
        $navigation = (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-navigation.php'
        );
        $layoutStyles = (string) file_get_contents(
            self::THEME_PATH . '/assets/scss/_layout.scss'
        );

        self::assertStringContainsString('/devenir-organisateur', $navigation);
        self::assertStringContainsString("['organisateur', 'chasse']", $navigation);
        self::assertStringContainsString('cta_render_single_hunt_enigmes_topbar_link', $navigation);
        self::assertStringContainsString('astra_render_mobile_header_column', $navigation);
        self::assertStringContainsString("['above', 'primary']", $navigation);
        self::assertStringContainsString('Énigmes', $navigation);
        self::assertStringContainsString('cta_single_hunt_enigmas_menu_visible', $navigation);
        self::assertStringContainsString('PlayerEnigmasAccessHandler::menuVisible', $navigation);
        self::assertStringContainsString('cta_get_single_hunt_enigmes_nav_url', $navigation);
        self::assertStringContainsString('cat-single-hunt', $navigation);
        self::assertStringContainsString('cta_get_primary_hunt_cta', $navigation);
        self::assertStringNotContainsString('single-hunt-story-link', $navigation);
        self::assertStringNotContainsString('single-hunt-riddles-link', $navigation);
        self::assertStringNotContainsString('single-hunt-manage-link', $navigation);
        self::assertStringContainsString('.cta-topbar-enigmes', $layoutStyles);
        self::assertStringContainsString('.cat-single-hunt .ast-mobile-menu-buttons', $layoutStyles);
    }

    public function testSingleHuntHomepageGuidesGuestsAndParticipantsWithoutOrganizerNextStep(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/home/single-hunt.php'
        );

        self::assertStringNotContainsString('single-hunt-home__journey', $template);
        self::assertStringNotContainsString('Créer mon compte', $template);
        self::assertStringNotContainsString('single-hunt-progress', $template);
        self::assertStringNotContainsString('<progress', $template);
        self::assertStringNotContainsString('Vos énigmes.', $template);
        self::assertStringNotContainsString('Rejoignez la chasse pour accéder aux énigmes.', $template);
        self::assertStringContainsString('cat_render_single_hunt_home_enigmas', (string) file_get_contents(
            self::THEME_PATH . '/template-parts/myaccount/dashboard-joueur.php'
        ));
        self::assertStringContainsString('cat_render_single_hunt_home_enigmas', (string) file_get_contents(
            self::THEME_PATH . '/template-parts/myaccount/dashboard-organisateur.php'
        ));
        self::assertStringContainsString('cat_render_single_hunt_home_enigmas', (string) file_get_contents(
            self::THEME_PATH . '/template-parts/myaccount/dashboard-admin.php'
        ));
        $account = (string) file_get_contents(self::THEME_PATH . '/inc/myaccount-functions.php');
        self::assertStringContainsString('function myaccount_hunt_lifecycle_switch_is_needed', $account);
        self::assertStringContainsString("['organisateur', 'admin']", $account);
        self::assertStringContainsString('cat_single_hunt_home_audience()', $account);
        self::assertStringNotContainsString('cta_should_prompt_hunt_join', $template);
        self::assertStringNotContainsString('Prochaine étape', $template);
        self::assertStringNotContainsString('Votre espace de gestion est prêt.', $template);
        self::assertStringNotContainsString('Modifiez la chasse ou consultez son activité.', $template);
        self::assertStringNotContainsString('Rejoignez la chasse pour révéler les énigmes.', $template);
        self::assertStringNotContainsString('Les énigmes vous attendent', $template);
        self::assertStringNotContainsString('home-enigmes', $template);
    }

    public function testSingleHuntHomepageLimitsPrimaryCtasAndRemovesRiddleParcours(): void
    {
        $template = $this->getSingleHuntTemplate();
        $navigation = (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-navigation.php'
        );

        self::assertSame(1, substr_count($template, "echo \$cta['cta_html']"));
        self::assertStringNotContainsString('single_hunt_riddles_access', $template);
        self::assertStringNotContainsString('chasse-partial-boucle-enigmes', $template);
        self::assertStringNotContainsString('/#home-enigmes', $navigation);
        self::assertStringContainsString('/#enigmes', $navigation);
        self::assertStringContainsString('cat_redirect_public_single_hunt', (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-home.php'
        ));
        self::assertStringContainsString('Participer', $navigation);
        self::assertStringNotContainsString('Découvrir l’aperçu', $navigation);
        self::assertStringNotContainsString('Rejoindre la chasse', $navigation);
        self::assertStringContainsString('Reprendre ma progression', $navigation);
        self::assertStringContainsString('cta_is_primary_hunt_staff', $navigation);
        self::assertStringContainsString("esc_html__('Voir'", $navigation);
        self::assertStringContainsString('cta_get_hunt_page_url', $navigation);
        self::assertStringContainsString("esc_html__('Énigmes'", $navigation);
        self::assertStringContainsString('joinLoginUrl', $navigation);
        self::assertStringNotContainsString('Voir tous les détails', $template);
        self::assertStringContainsString("home_url('/')", (string) file_get_contents(
            self::THEME_PATH . '/inc/single-hunt-home.php'
        ));
        self::assertStringNotContainsString(
            "add_query_arg(\n    'redirect_to',\n    \$huntUrl,",
            $this->getSingleHuntTemplate()
        );
    }

    public function testDemoModeProvidesAnAuthenticatedStatisticsResetShortcut(): void
    {
        $outils = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/shell/outils.php'
        );
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        $script = (string) file_get_contents(self::THEME_PATH . '/assets/js/reset-stats-card.js');

        self::assertStringContainsString('data-reset-stats', $outils);
        self::assertStringContainsString('cat_is_demo_mode', $outils);
        self::assertStringContainsString('cta_render_demo_reset_stats_button', $theme);
        self::assertStringNotContainsString("add_action('wp_footer', 'cta_render_demo_reset_stats_button'", $theme);
        self::assertStringContainsString("querySelectorAll('[data-reset-stats]')", $script);
    }

    public function testHuntPageDoesNotRenderTheObsoleteOrganizerBreadcrumb(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/single-chasse.php');

        self::assertStringNotContainsString("template-parts/common/breadcrumb", $template);
        self::assertStringNotContainsString('$breadcrumb_items', $template);
    }

    public function testCompactHuntPageSkipsVisibleEnigmesIntroBlock(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/single-chasse.php');
        $styles = (string) file_get_contents(self::THEME_PATH . '/assets/scss/_chasse.scss');

        self::assertMatchesRegularExpression(
            '/if \(\$compact_experience\)\s*:\s*\?>\s*<h2 class="screen-reader-text">/s',
            $template
        );
        self::assertStringContainsString("esc_html_e('Les énigmes', 'chassesautresor-com')", $template);
        self::assertStringContainsString('if (!$compact_experience)', $template);
        self::assertStringContainsString('titre-enigmes-wrapper', $template);
        self::assertStringContainsString('margin-top: var(--space-md);', $styles);
        self::assertStringNotContainsString('.titre-enigmes-wrapper {', $styles);
    }

    public function testCompactHuntEnigmaCardsUsePosterLayout(): void
    {
        $styles = (string) file_get_contents(self::THEME_PATH . '/assets/scss/_cartes.scss');
        $partial = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/enigme/chasse-partial-boucle-enigmes.php'
        );

        self::assertStringContainsString('.cards-grid--poster', $styles);
        self::assertStringContainsString('--carte-enigme-poster-max-width: 20rem;', $styles);
        self::assertStringContainsString('aspect-ratio: 3 / 4;', $styles);
        self::assertStringContainsString('max-width: var(--carte-enigme-poster-max-width);', $styles);
        self::assertStringContainsString('min-width: 0;', $styles);
        self::assertStringContainsString('object-fit: cover;', $styles);
        self::assertStringContainsString('.carte-enigme--statut-resolue,', $styles);
        self::assertStringContainsString('border-color: var(--color-success);', $styles);
        self::assertStringContainsString('.carte-enigme--statut-resolue .carte-enigme-image::after,', $styles);
        self::assertStringContainsString('.carte-enigme-footer {', $styles);
        self::assertStringContainsString('display: none;', $styles);
        self::assertStringContainsString('-webkit-line-clamp: 3;', $styles);
        self::assertStringContainsString(
            'rgba(6, 10, 31, 0.94)',
            $styles
        );
        self::assertStringContainsString("cards-grid cards-grid--poster", $partial);
        self::assertStringContainsString('cat_is_single_hunt_mode()', $partial);
        self::assertStringNotContainsString('carte-enigme-overlay', $partial);
        self::assertStringNotContainsString('carte-enigme-action', $partial);
        self::assertStringNotContainsString('carte-enigme-bouton', $partial);
    }

    public function testChasseEditionPanelHasBalancedDivMarkup(): void
    {
        $raw = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/chasse/chasse-edition-main.php'
        );

        $html = '';
        $offset = 0;
        $length = strlen($raw);
        while ($offset < $length) {
            $start = strpos($raw, '<?', $offset);
            if ($start === false) {
                $html .= substr($raw, $offset);
                break;
            }
            $html .= substr($raw, $offset, $start - $offset);
            $end = strpos($raw, '?>', $start);
            if ($end === false) {
                break;
            }
            $offset = $end + 2;
        }

        preg_match_all('/<div\b/i', $html, $opens);
        preg_match_all('/<\/div>/i', $html, $closes);

        self::assertSame(
            count($opens[0]),
            count($closes[0]),
            'chasse-edition-main.php must keep balanced <div> tags so cards stay inside the compact wrapper'
        );
    }

    public function testSingleHuntSeoProtectsDemoModeAndProvidesStructuredData(): void
    {
        $seo = (string) file_get_contents(self::THEME_PATH . '/inc/single-hunt-seo.php');

        self::assertStringContainsString('pre_get_document_title', $seo);
        self::assertStringContainsString('noindex,nofollow,noarchive', $seo);
        self::assertStringContainsString('application/ld+json', $seo);
        self::assertStringContainsString("'@type'      => 'WebPage'", $seo);
    }

    public function testSingleHuntAnalyticsCoversThePlayerFunnel(): void
    {
        $script = (string) file_get_contents(self::THEME_PATH . '/assets/js/single-hunt-analytics.js');

        self::assertStringNotContainsString('single_hunt_registration', $this->getSingleHuntTemplate());
        self::assertStringContainsString('single_hunt_engagement_start', $script);
        self::assertStringContainsString('single_hunt_riddle_open', $script);
        self::assertStringContainsString('single_hunt_riddle_resolved', $script);
    }

    private function getSingleHuntTemplate(): string
    {
        return (string) file_get_contents(self::THEME_PATH . '/template-parts/home/single-hunt.php');
    }
}
