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
        self::assertStringNotContainsString('ont résolu l’énigme.', $html);
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
        self::assertStringNotContainsString('ont résolu l’énigme.', $html);
        self::assertStringNotContainsString('attend une validation.', $html);
        self::assertStringContainsString('Résolus, validation et étapes décrivent l’état actuel.', $html);
        self::assertStringContainsString('18 %', $html);
        self::assertStringContainsString('2 réussites sur 11 tentatives', $html);
        self::assertStringContainsString('À valider', $html);
        self::assertStringContainsString('1 réponse', $html);
        self::assertStringContainsString('progression-scope', $html);
        self::assertStringContainsString('État actuel', $html);
        self::assertStringContainsString('Indice le plus débloqué : Carte, 5 joueurs', $html);
        self::assertStringContainsString('La porte', $html);
        self::assertStringContainsString('75 %', $html);
        self::assertStringContainsString('La plus forte chute de joueurs est à « Le couloir ».', $html);
        self::assertStringContainsString('Réponses finales par semaine', $html);
        self::assertStringContainsString(
            'Semaine du 13/10 : 7 tentatives finales, peu de réussites.',
            $html
        );
        self::assertStringContainsString('<details class="progression-series" open>', $html);
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOrganizerCardSurfacesTheQueueTheHintAndTheSpike(): void
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
        function apply_filters($hook, $value, ...$args)
        {
            if ($hook === 'chassesautresor_edition_url') {
                return '/outils/?enigme=' . (int) $args[1] . '&section=' . (string) $args[2];
            }

            return $value;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountHuntRiddleStatisticsRenderer.php';

        $dashboard = static fn (): array => [
            'summary' => [
                'participants' => 43,
                'engagement_rate' => 0,
                'resolution_rate' => 0,
                'tentatives' => 54,
                'winners' => 0,
                'winner_cap' => 0,
            ],
            'riddles' => [
                [
                    'id' => 3,
                    'title' => 'Facile',
                    'participants' => 10,
                    'funnel' => ['engaged' => 10, 'with_step' => null, 'submitted' => 0, 'solved' => 5],
                    'yield' => ['success' => 5, 'total' => 10],
                    'pending' => 0,
                    'median_attempts' => 1,
                ],
                [
                    'id' => 9,
                    'title' => 'Dur',
                    'participants' => 10,
                    'funnel' => ['engaged' => 10, 'with_step' => null, 'submitted' => 0, 'solved' => 1],
                    'yield' => ['success' => 1, 'total' => 10],
                    'pending' => 0,
                    'median_attempts' => 4,
                    'attempt_series' => [
                        ['label' => '05/10', 'total' => 2, 'success' => 0],
                        ['label' => '12/10', 'total' => 4, 'success' => 1],
                    ],
                    'attempt_series_grain' => 'week',
                ],
                [
                    'id' => 8,
                    'title' => 'Le grand jour',
                    'participants' => 43,
                    'funnel' => ['engaged' => 43, 'with_step' => null, 'submitted' => 6, 'solved' => 12],
                    'yield' => ['success' => 12, 'total' => 54],
                    'pending' => 6,
                    'median_attempts' => 3,
                    'top_hint' => ['title' => 'clue-antre-de-feu', 'unlocks' => 25],
                    'attempt_series' => [
                        ['label' => '12/10', 'date' => '2026-10-12', 'total' => 4, 'success' => 1, 'open' => false],
                        [
                            'label' => '19/10',
                            'date' => '2026-10-19',
                            'total' => 36,
                            'success' => 8,
                            'open' => true,
                        ],
                    ],
                    'attempt_series_grain' => 'week',
                ],
            ],
        ];

        $html = (new AccountHuntRiddleStatisticsRenderer(null, $dashboard))->render(12, 'semaine', '/progression/');

        self::assertLessThan(strpos($html, 'Dur'), strpos($html, 'Le grand jour'));
        self::assertLessThan(strpos($html, 'Facile'), strpos($html, 'Dur'));
        self::assertStringContainsString('Sans réponse finale', $html);
        self::assertStringNotContainsString('En cours', $html);
        self::assertStringContainsString('22 %', $html);
        self::assertStringContainsString('12 réussites sur 54 tentatives', $html);
        self::assertStringContainsString('progression-figures__item--alert', $html);
        self::assertStringContainsString('href="/outils/?enigme=8&section=tentatives"', $html);
        self::assertStringContainsString('6 réponses', $html);
        self::assertStringContainsString('Indice le plus débloqué : Antre de Feu, 25 joueurs sur 43', $html);
        self::assertStringContainsString(
            'Semaine du 19 octobre, encore en cours : 36 tentatives finales, peu de réussites.',
            $html
        );
        self::assertStringContainsString('<details class="progression-series" open>', $html);
        self::assertStringContainsString('<details class="progression-series">', $html);
        self::assertLessThan(
            strpos($html, '<details class="progression-series">'),
            strpos($html, '<details class="progression-series" open>')
        );
    }
}
