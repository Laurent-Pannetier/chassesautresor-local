<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountHuntRiddleStatisticsRenderer;
use PHPUnit\Framework\TestCase;

final class AccountHuntRiddleStatisticsRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersReadablePerRiddleMetrics(): void
    {
        function __($value): string
        {
            return (string) $value;
        }
        function _n($single, $plural, $number): string
        {
            return ((int) $number === 1) ? (string) $single : (string) $plural;
        }
        function esc_html_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountHuntRiddleStatisticsRenderer.php';

        $rowsProvider = static fn (int $huntId): array => $huntId === 12 ? [[
            'id' => 40,
            'title' => 'Énigme Alpha',
            'participants' => 4,
            'tentatives' => 11,
            'trouves' => 2,
            'steps' => 3,
            'step_players' => 1,
            'ranking' => [
                ['username' => 'alice', 'tentatives' => 2],
                ['username' => 'bob', 'tentatives' => 4],
            ],
        ]] : [];

        $html = (new AccountHuntRiddleStatisticsRenderer($rowsProvider))->render(12);

        self::assertStringContainsString('myaccount-riddle-stats-list', $html);
        self::assertStringContainsString('myaccount-riddle-stats-metric', $html);
        self::assertStringContainsString('Énigme Alpha', $html);
        self::assertStringContainsString('Participants', $html);
        self::assertStringContainsString('3 (1 joueur)', $html);
        self::assertStringContainsString('1. alice (2 tentatives) · 2. bob (4 tentatives)', $html);
        self::assertStringContainsString('<dd>4</dd>', $html);
        self::assertStringContainsString('<dd>2</dd>', $html);
        self::assertStringContainsString('<dd>11</dd>', $html);
        self::assertStringNotContainsString('<table', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersEmptyStateWhenHuntIsMissing(): void
    {
        function __($value): string
        {
            return (string) $value;
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountHuntRiddleStatisticsRenderer.php';

        $html = (new AccountHuntRiddleStatisticsRenderer(static fn (): array => []))->render(0);

        self::assertStringContainsString('Aucune chasse gérée', $html);
        self::assertStringNotContainsString('<table', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersHuntFunnelYieldAndSteps(): void
    {
        function __($value): string
        {
            return (string) $value;
        }
        function _n($single, $plural, $number): string
        {
            return ((int) $number === 1) ? (string) $single : (string) $plural;
        }
        function esc_html_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_url($value): string
        {
            return (string) $value;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountHuntRiddleStatisticsRenderer.php';

        $dashboard = static fn (int $huntId, string $period): array => [
            'summary' => [
                'participants' => 6,
                'engagement_rate' => 40,
                'resolution_rate' => 25,
                'tentatives' => 18,
                'winners' => 1,
                'winner_cap' => 3,
            ],
            'riddles' => [[
                'id' => 40,
                'title' => 'Énigme Alpha',
                'participants' => 4,
                'tentatives' => 11,
                'trouves' => 2,
                'steps' => 2,
                'step_players' => 3,
                'ranking' => [
                    ['username' => 'alice', 'tentatives' => 2, 'date' => '2026-10-01 10:00:00'],
                ],
                'funnel' => [
                    'engaged' => 4,
                    'with_step' => 3,
                    'submitted' => 1,
                    'solved' => 2,
                ],
                'yield' => ['success' => 2, 'total' => 11],
                'pending' => 1,
                'median_attempts' => 3,
                'top_hint' => ['title' => 'Carte', 'unlocks' => 5],
                'steps_detail' => [[
                    'title' => 'La porte',
                    'reached' => 3,
                    'engaged' => 4,
                    'attempts' => 8,
                ]],
            ]],
        ];

        $html = (new AccountHuntRiddleStatisticsRenderer(null, $dashboard))
            ->render(12, 'semaine', '/progression/');

        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('periode=semaine', $html);
        self::assertStringContainsString('40 %', $html);
        self::assertStringContainsString('25 %', $html);
        self::assertStringContainsString('1/3', $html);
        self::assertStringContainsString('Engagés', $html);
        self::assertStringContainsString('2/11 · 18 %', $html);
        self::assertStringContainsString('Indice le plus débloqué : Carte (5)', $html);
        self::assertStringContainsString('La porte', $html);
        self::assertStringContainsString('3/4 joueurs · 8 tentatives', $html);
        self::assertStringContainsString('01/10/2026', $html);
        self::assertStringContainsString('Médiane pour résoudre', $html);
    }
}
