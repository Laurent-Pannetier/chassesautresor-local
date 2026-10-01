<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionModalRenderer;
use PHPUnit\Framework\TestCase;

final class ConversionModalRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersInsufficientBalanceMessageWithMinimum(): void
    {
        function __(string $message, string $domain): string
        {
            return $message;
        }

        function esc_html__(string $message, string $domain): string
        {
            return $message;
        }

        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionModalRenderer.php';

        $html = (new ConversionModalRenderer())->render('INSUFFICIENT_POINTS', 0, 500, 120, 0.05);

        self::assertStringContainsString('Solde insuffisant', $html);
        self::assertStringContainsString('500 points', $html);
        self::assertStringContainsString('class="close-modal"', $html);
    }
}
