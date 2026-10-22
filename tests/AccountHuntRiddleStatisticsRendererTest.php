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
        function esc_attr($value): string
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
        ]] : [];

        $html = (new AccountHuntRiddleStatisticsRenderer($rowsProvider))->render(12);

        self::assertStringContainsString('progression-riddles__list', $html);
        self::assertStringContainsString('progression-mix', $html);
        self::assertStringContainsString('Énigme Alpha', $html);
        self::assertStringContainsString('4 joueurs engagés.', $html);
        self::assertStringContainsString('1 joueur a franchi au moins une étape.', $html);
        self::assertStringContainsString('2 joueurs ont résolu l’énigme.', $html);
        self::assertStringNotContainsString('Classement', $html);
        self::assertStringNotContainsString('<table', $html);
        self::assertStringNotContainsString('progression-funnel', $html);
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
                'steps_detail' => [
                    [
                        'title' => 'La porte',
                        'reached' => 3,
                        'engaged' => 4,
                        'attempts' => 8,
                    ],
                    [
                        'title' => 'Le couloir',
                        'reached' => 1,
                        'engaged' => 4,
                        'attempts' => 2,
                    ],
                ],
                'attempt_series' => [
                    ['label' => '06/10', 'total' => 4, 'success' => 1],
                    ['label' => '13/10', 'total' => 7, 'success' => 1],
                ],
                'attempt_series_grain' => 'week',
            ]],
        ];

        $html = (new AccountHuntRiddleStatisticsRenderer(null, $dashboard))
            ->render(12, 'semaine', '/progression/');

        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('periode=semaine', $html);
        self::assertStringContainsString('40 %', $html);
        self::assertStringContainsString('25 %', $html);
        self::assertStringContainsString('1/3', $html);
        self::assertStringContainsString('4 joueurs engagés.', $html);
        self::assertStringContainsString('3 joueurs ont franchi au moins une étape.', $html);
        self::assertStringContainsString('2 joueurs ont résolu l’énigme.', $html);
        self::assertStringContainsString('1 joueur attend une validation.', $html);
        self::assertStringContainsString('2/11 · 18 %', $html);
        self::assertStringContainsString('1 réponse', $html);
        self::assertStringContainsString('Indice le plus débloqué : Carte (5)', $html);
        self::assertStringContainsString('La porte', $html);
        self::assertStringContainsString('75 %', $html);
        self::assertStringContainsString('La plus forte chute de joueurs est à « Le couloir ».', $html);
        self::assertStringContainsString('Réponses finales par semaine', $html);
        self::assertStringContainsString('06/10', $html);
        self::assertStringContainsString('<polyline', $html);
        self::assertStringContainsString('Médiane pour résoudre', $html);
        self::assertStringContainsString('3 tentatives', $html);
        self::assertStringNotContainsString('progression-funnel', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAQuietRiddleSkipsEmptyFiguresAndStepChart(): void
    {
        function __($value): string
        {
            return (string) $value;
        }
        function _n($single, $plural, $number): string
        {
            return ((int) $number === 1) ? (string) $single : (string) $plural;
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

        $dashboard = static fn (): array => [
            'summary' => [
                'participants' => 1,
                'engagement_rate' => 0,
                'resolution_rate' => 0,
                'tentatives' => 0,
                'winners' => 0,
                'winner_cap' => 0,
            ],
            'riddles' => [[
                'id' => 7,
                'title' => 'Cascade de cadenas',
                'participants' => 1,
                'tentatives' => 0,
                'trouves' => 0,
                'steps' => 9,
                'step_players' => 0,
                'funnel' => [
                    'engaged' => 1,
                    'with_step' => 0,
                    'submitted' => 0,
                    'solved' => 0,
                ],
                'yield' => ['success' => 0, 'total' => 0],
                'pending' => 0,
                'median_attempts' => null,
                'steps_detail' => [
                    ['title' => 'direction', 'reached' => 0, 'engaged' => 1, 'attempts' => 0],
                    ['title' => 'molette', 'reached' => 0, 'engaged' => 1, 'attempts' => 0],
                ],
                'attempt_series' => [],
            ]],
        ];

        $html = (new AccountHuntRiddleStatisticsRenderer(null, $dashboard))->render(12);

        self::assertStringContainsString('1 joueur engagé.', $html);
        self::assertStringContainsString('Aucune des 9 étapes n’a été franchie.', $html);
        self::assertStringContainsString('Aucune réponse finale n’a été envoyée.', $html);
        self::assertStringContainsString('Pas encore d’étape', $html);
        self::assertStringNotContainsString('Rendement', $html);
        self::assertStringNotContainsString('Médiane', $html);
        self::assertStringNotContainsString('En attente', $html);
        self::assertStringNotContainsString('Classement', $html);
        self::assertStringNotContainsString('<table', $html);
        self::assertStringNotContainsString('progression-series', $html);
        self::assertStringNotContainsString('direction', $html);
    }
}
