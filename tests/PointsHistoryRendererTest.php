<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsHistoryRenderer;
use PHPUnit\Framework\TestCase;

final class PointsHistoryRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersLinkedReasonAndSignedVariation(): void
    {
        function get_the_title(int $postId): string
        {
            return 'Chasse test';
        }

        function get_permalink(int $postId): string
        {
            return 'https://example.test/chasse';
        }

        function esc_url(string $url): string
        {
            return $url;
        }

        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function wp_kses_post(string $html): string
        {
            return $html;
        }

        function mysql2date(string $format, string $date): string
        {
            return '01/10/2026';
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsHistoryRenderer.php';

        $html = (new PointsHistoryRenderer())->rows([[
            'id' => 7,
            'points' => 25,
            'balance' => 125,
            'request_date' => '2026-10-01 10:00:00',
            'origin_type' => 'chasse',
            'origin_id' => 42,
            'reason' => 'Récompense #42',
        ]]);

        self::assertStringContainsString('https://example.test/chasse', $html);
        self::assertStringContainsString('Chasse test', $html);
        self::assertStringContainsString('+25', $html);
    }
}
