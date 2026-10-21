<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EditionShellChromeTest extends TestCase {
    public function testShellEditionActiveTabUsesTheDashboardAccent(): void {
        $styles = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_edition.scss'
        );
        $start = strpos($styles, 'body.shell-edition .edition-tabs {');
        $this->assertNotFalse($start);
        $chunk = substr($styles, $start, 1200);

        $this->assertStringContainsString('border-radius: 999px;', $chunk);
        $this->assertStringContainsString('background: var(--shell-accent);', $chunk);
        $this->assertStringContainsString('color: var(--shell-accent-text);', $chunk);
        $this->assertStringNotContainsString('var(--color-primary)', $chunk);
    }
}
