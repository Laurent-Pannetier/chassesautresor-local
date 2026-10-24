<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatisticsParticipantRenderer;
use PHPUnit\Framework\TestCase;

final class RiddleStatisticsParticipantRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersEmptyStateWithoutThemeTemplate(): void
    {
        function esc_html__(string $message, string $domain): string
        {
            return $message;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsParticipantRenderer.php';

        $html = (new RiddleStatisticsParticipantRenderer())->render(
            12,
            [],
            ['page' => 1, 'limit' => 25, 'order' => 'ASC'],
            0,
            0,
            'date'
        );

        self::assertSame('<p>Aucun participant engagé.</p>', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersHintCountAndUnlockDetail(): void
    {
        function __($value): string
        {
            return (string) $value;
        }
        function esc_html__($value): string
        {
            return (string) $value;
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr__($value): string
        {
            return (string) $value;
        }
        function _n($single, $plural, $number): string
        {
            return ((int) $number === 1) ? (string) $single : (string) $plural;
        }
        function get_field(): string
        {
            return 'automatique';
        }
        function mysql2date($format, $date): string
        {
            return (string) $date;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsParticipantRenderer.php';

        $html = (new RiddleStatisticsParticipantRenderer())->render(
            12,
            [
                [
                    'user_id' => 7,
                    'username' => 'alice',
                    'date_engagement' => '2026-10-01 10:00:00',
                    'nb_tentatives' => 2,
                    'nb_indices' => 2,
                    'trouve' => 0,
                ],
                [
                    'user_id' => 8,
                    'username' => 'bob',
                    'date_engagement' => '2026-10-02 10:00:00',
                    'nb_tentatives' => 0,
                    'nb_indices' => 0,
                    'trouve' => 1,
                ],
            ],
            ['page' => 1, 'limit' => 25, 'order' => 'ASC'],
            2,
            1,
            'date'
        );

        self::assertStringContainsString('>Indices<', $html);
        self::assertStringContainsString('data-user-id="7"', $html);
        self::assertStringContainsString('2 indices débloqués', $html);
        self::assertStringContainsString('Aucun indice', $html);
        self::assertStringContainsString('>—</span>', $html);

        $detail = (new RiddleStatisticsParticipantRenderer())->hintsDetail([
            ['title' => 'Carte', 'unlocked_at' => '2026-10-24 13:04:00'],
        ]);
        self::assertStringContainsString('Carte', $detail);
        self::assertStringContainsString('24/10/2026 13:04', $detail);
        self::assertStringContainsString(
            'Aucun indice débloqué.',
            (new RiddleStatisticsParticipantRenderer())->hintsDetail([])
        );
    }
}
