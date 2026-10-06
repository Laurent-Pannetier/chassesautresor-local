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

    public function testFooterIsCompactLegalBarWithoutRedundantNavOrNewsletter(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/footer/site-footer.php'
        );
        $helpers = (string) file_get_contents(self::THEME_PATH . '/inc/footer-functions.php');

        self::assertStringContainsString('cat-site-footer--compact', $template);
        self::assertStringContainsString('data-experience-mode', $template);
        self::assertStringContainsString('cat-site-footer__legal', $template);
        self::assertStringContainsString('cat-site-footer__copyright', $template);
        self::assertStringNotContainsString('cat-site-footer__nav', $template);
        self::assertStringNotContainsString('cat-site-footer__brand', $template);
        self::assertStringNotContainsString('newsletter', $template);
        self::assertStringNotContainsString('mc4wp', $template);
        self::assertStringNotContainsString('Devenir organisateur', $helpers);
        self::assertStringNotContainsString('cta_get_footer_nav_columns', $helpers);
        self::assertStringNotContainsString('newsletter', $helpers);
    }

    public function testFooterStylesStayCompactAndDropLegacyWidgetRules(): void
    {
        $styles = (string) file_get_contents(self::THEME_PATH . '/assets/scss/_layout.scss');

        self::assertStringContainsString('.cat-site-footer', $styles);
        self::assertStringContainsString('.cat-site-footer__legal', $styles);
        self::assertStringContainsString('.cat-site-footer--demo', $styles);
        self::assertStringNotContainsString('.cat-site-footer__nav', $styles);
        self::assertStringNotContainsString('.cat-site-footer__brand', $styles);
        self::assertStringNotContainsString('.newsletter-group', $styles);
        self::assertStringNotContainsString('.lien-organisateur', $styles);
        self::assertStringNotContainsString('.mc4wp-form', $styles);
    }
}
