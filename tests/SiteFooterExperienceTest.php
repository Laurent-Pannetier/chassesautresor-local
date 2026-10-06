<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SiteFooterExperienceTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testFooterFunctionsAreLoadedByTheme(): void
    {
        $functions = (string) file_get_contents(self::THEME_PATH . '/functions.php');

        self::assertStringContainsString("require_once \$inc_path . 'footer-functions.php';", $functions);
    }

    public function testFooterTemplateUsesExperienceAwareRendererInsteadOfAstraFooter(): void
    {
        $footer = (string) file_get_contents(self::THEME_PATH . '/footer.php');

        self::assertStringContainsString('cta_render_site_footer', $footer);
        self::assertStringNotContainsString('astra_footer();', $footer);
    }

    public function testFooterMarkupHasNoNewsletterAndExposesExperienceMode(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/footer/site-footer.php'
        );
        $helpers = (string) file_get_contents(self::THEME_PATH . '/inc/footer-functions.php');

        self::assertStringContainsString('data-experience-mode', $template);
        self::assertStringContainsString('cat-site-footer', $template);
        self::assertStringNotContainsString('newsletter', $template);
        self::assertStringNotContainsString('mc4wp', $template);
        self::assertStringNotContainsString('newsletter', $helpers);
        self::assertStringNotContainsString('mc4wp', $helpers);
    }

    public function testOrganizerApplicationLinkIsPlatformOnly(): void
    {
        $helpers = (string) file_get_contents(self::THEME_PATH . '/inc/footer-functions.php');

        self::assertStringContainsString('cta_get_footer_platform_columns', $helpers);
        self::assertStringContainsString('cta_get_footer_single_hunt_columns', $helpers);
        self::assertStringContainsString('cat_are_organizer_applications_open', $helpers);
        self::assertStringContainsString('Devenir organisateur', $helpers);

        $platformFnStart = strpos($helpers, 'function cta_get_footer_platform_columns');
        $singleFnStart = strpos($helpers, 'function cta_get_footer_single_hunt_columns');
        self::assertNotFalse($platformFnStart);
        self::assertNotFalse($singleFnStart);

        $platformFn = substr($helpers, $platformFnStart, $singleFnStart - $platformFnStart);
        $afterPlatform = substr($helpers, $singleFnStart);

        self::assertStringContainsString('Devenir organisateur', $platformFn);
        self::assertStringContainsString('cat_are_organizer_applications_open', $platformFn);
        self::assertStringNotContainsString('Devenir organisateur', $afterPlatform);
    }

    public function testFooterStylesMatchSiteChromeAndDropLegacyNewsletterWidgetRules(): void
    {
        $styles = (string) file_get_contents(self::THEME_PATH . '/assets/scss/_layout.scss');

        self::assertStringContainsString('.cat-site-footer', $styles);
        self::assertStringContainsString('.cat-site-footer--demo', $styles);
        self::assertStringContainsString('.cat-site-footer__link--accent', $styles);
        self::assertStringNotContainsString('.newsletter-group', $styles);
        self::assertStringNotContainsString('.lien-organisateur', $styles);
        self::assertStringNotContainsString('.mc4wp-form', $styles);
    }
}
