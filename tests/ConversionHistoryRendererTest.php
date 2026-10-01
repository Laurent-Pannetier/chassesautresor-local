<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionHistoryRenderer;
use PHPUnit\Framework\TestCase;

final class ConversionHistoryRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersAdministratorRowWithUserAndLocalizedStatus(): void
    {
        function __(string $message, string $domain): string
        {
            return $message;
        }

        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function date_i18n(string $format, int $timestamp): string
        {
            return '01/10/2026 à 12:30';
        }

        function get_userdata(int $userId): object
        {
            return (object) ['display_name' => 'Joueuse test'];
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionHistoryRenderer.php';

        $html = (new ConversionHistoryRenderer())->rows([[
            'request_date' => '2026-10-01 12:30:00',
            'user_id' => 14,
            'amount_eur' => '12.50',
            'points' => -250,
            'request_status' => 'paid',
        ]], true);

        self::assertStringContainsString('Joueuse test', $html);
        self::assertStringContainsString('12.50 €', $html);
        self::assertStringContainsString('250', $html);
        self::assertStringContainsString('Réglé', $html);
    }
}
