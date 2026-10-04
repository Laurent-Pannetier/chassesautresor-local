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
        self::assertStringContainsString("get_template_part('template-parts/home/platform')", $template);
        self::assertStringContainsString("get_template_part('template-parts/home/single-hunt')", $template);
    }

    public function testSingleHuntHomepageHasNoCatalogControls(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/home/single-hunt.php'
        );

        self::assertStringContainsString('cat_get_primary_hunt_id()', $template);
        self::assertStringContainsString('cat_is_demo_mode()', $template);
        self::assertStringContainsString('single-hunt-home__facts', $template);
        self::assertStringContainsString('single-hunt-home__story', $template);
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

    public function testSingleHuntNavigationExposesAUniqueAlwaysVisibleEnigmesLink(): void
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

        self::assertStringContainsString('single-hunt-home__journey', $template);
        self::assertStringContainsString('Créer mon compte', $template);
        self::assertStringContainsString('$isEngaged', $template);
        self::assertStringContainsString('single-hunt-progress', $template);
        self::assertStringContainsString('<progress', $template);
        self::assertStringContainsString('Reprenez là où vous vous êtes arrêté.', $template);
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
        self::assertStringContainsString('#chasse-enigmes-wrapper', $navigation);
        self::assertStringContainsString('cta_get_single_hunt_enigmes_nav_url', $navigation);
        self::assertStringContainsString("get_permalink(\$huntId)", $navigation);
    }

    public function testDemoModeProvidesAnAuthenticatedStatisticsResetShortcut(): void
    {
        $dashboard = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/myaccount/dashboard-organisateur.php'
        );
        $adminDashboard = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/myaccount/dashboard-admin.php'
        );
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        $script = (string) file_get_contents(self::THEME_PATH . '/assets/js/reset-stats-card.js');

        self::assertStringContainsString('data-reset-stats', $dashboard);
        self::assertStringContainsString('data-reset-stats', $adminDashboard);
        self::assertStringContainsString('cat_is_demo_mode', $dashboard);
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

        self::assertStringContainsString('single_hunt_registration', $this->getSingleHuntTemplate());
        self::assertStringContainsString('single_hunt_engagement_start', $script);
        self::assertStringContainsString('single_hunt_riddle_open', $script);
        self::assertStringContainsString('single_hunt_riddle_resolved', $script);
    }

    private function getSingleHuntTemplate(): string
    {
        return (string) file_get_contents(self::THEME_PATH . '/template-parts/home/single-hunt.php');
    }
}
