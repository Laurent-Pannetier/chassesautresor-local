<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EditionShellChromeTest extends TestCase
{
    public function testEditionScreenDropsTheDuplicateTitleAboveThePanel(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $placeholder = (string) file_get_contents($theme . '/templates/shell-placeholder.php');
        $start = strpos($placeholder, 'app-outils--edition');
        $end = strpos($placeholder, 'cta_shell_render_edition($edition)');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertGreaterThan($start, $end);
        $branch = substr($placeholder, $start, $end - $start);
        $this->assertStringNotContainsString('app-placeholder__title', $branch);

        $riddle = (string) file_get_contents($theme . '/template-parts/enigme/enigme-edition-main.php');
        $hunt = (string) file_get_contents($theme . '/template-parts/chasse/chasse-edition-main.php');
        $this->assertStringContainsString('<h1>', $riddle);
        $this->assertStringContainsString('Édition énigme', $riddle);
        $this->assertStringContainsString('<h1>', $hunt);
        $this->assertStringContainsString('Édition chasse', $hunt);

        $styles = (string) file_get_contents($theme . '/assets/scss/_edition.scss');
        $this->assertStringContainsString('.edition-panel-header h1,', $styles);
        $this->assertStringContainsString('text-transform: none;', $styles);
    }

    public function testScrollToTopIsDisabledSiteWide(): void
    {
        $functions = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/functions.php'
        );

        $this->assertStringContainsString(
            "add_filter( 'astra_get_option_scroll-to-top-enable', '__return_false' )",
            $functions
        );
    }

    public function testIntermediateStepsStayReadableInNightMode(): void
    {
        $styles = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_edition.scss'
        );

        $this->assertStringContainsString('background: var(--shell-surface-2);', $styles);
        $this->assertStringContainsString('color: var(--shell-text);', $styles);
        $this->assertStringNotContainsString('background: var(--color-white);', $this->stepCardBlock($styles));
    }

    public function testFooterReachesTheBottomOfTheDashboardMenu(): void
    {
        $styles = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_app-shell.scss'
        );

        $this->assertStringContainsString('--shell-savebar-offset: 4.75rem;', $styles);
        $this->assertStringContainsString('padding-bottom: var(--shell-savebar-offset);', $styles);
        $this->assertStringContainsString(
            'height: calc(100dvh - var(--shell-topbar-height) - var(--shell-savebar-offset));',
            $styles
        );
        $this->assertStringContainsString('flex: 1 1 auto;', $styles);
    }

    private function stepCardBlock(string $styles): string
    {
        $start = strpos($styles, '.riddle-step-card {');
        $end = strpos($styles, '.riddle-step-card.is-dragging');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($styles, $start, $end - $start);
    }
}
