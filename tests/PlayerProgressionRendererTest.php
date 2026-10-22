<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\PlayerProgressionRenderer;
use PHPUnit\Framework\TestCase;

final class PlayerProgressionRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersIndicatorsAndRiddleActions(): void
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
        function esc_html__($value): string
        {
            return esc_html($value);
        }
        function esc_attr__($value): string
        {
            return esc_attr($value);
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/PlayerProgressionRenderer.php';

        $html = (new PlayerProgressionRenderer())->render(
            [
                'resolved' => 1,
                'resolvable' => 2,
                'engaged' => 1,
                'total' => 2,
                'attempts_success' => 3,
                'attempts_total' => 9,
                'attempts_pending' => 1,
                'hints' => 2,
                'riddles' => [[
                    'id' => 40,
                    'title' => 'Alpha',
                    'url' => '/enigme/40',
                    'thumbnail_url' => '/alpha.jpg',
                    'status' => 'en_cours',
                    'steps_done' => 1,
                    'steps_total' => 3,
                    'attempts' => 4,
                    'last_activity' => '2026-10-03 09:00:00',
                    'rank' => 1,
                ], [
                    'id' => 41,
                    'title' => 'Beta',
                    'url' => '/enigme/41',
                    'thumbnail_url' => '',
                    'status' => 'non_commencee',
                    'steps_done' => 0,
                    'steps_total' => 0,
                    'attempts' => 0,
                    'last_activity' => '',
                    'rank' => 0,
                ], [
                    'id' => 42,
                    'title' => 'Gamma',
                    'url' => '/enigme/42',
                    'thumbnail_url' => '',
                    'status' => 'en_cours',
                    'steps_done' => 2,
                    'steps_total' => 4,
                    'attempts' => 0,
                    'last_activity' => '',
                    'rank' => 2,
                ]],
            ],
            [
                ['id' => 12, 'title' => 'Chasse A', 'url' => '/progression/?chasse=12'],
                ['id' => 13, 'title' => 'Chasse B', 'url' => '/progression/?chasse=13'],
            ],
            12
        );

        self::assertStringContainsString('1/2', $html);
        self::assertStringContainsString('Commencées', $html);
        self::assertStringContainsString('Tentatives', $html);
        self::assertStringNotContainsString('3/9', $html);
        self::assertStringContainsString('>2<', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('Chasse B', $html);
        self::assertStringContainsString('En cours', $html);
        self::assertStringContainsString('Étapes franchies', $html);
        self::assertStringContainsString('<strong>1</strong>', $html);
        self::assertStringNotContainsString('1/3', $html);
        self::assertStringNotContainsString('Reprendre', $html);
        self::assertStringContainsString('Voir mes 4 soumissions', $html);
        self::assertStringContainsString('>Classement<', $html);
        self::assertStringContainsString('1er', $html);
        self::assertStringContainsString('2e', $html);
        self::assertStringContainsString('Pas de classement', $html);
        self::assertStringNotContainsString('Classement de la chasse', $html);
        self::assertStringNotContainsString('En tête sur cette énigme', $html);
        self::assertStringNotContainsString('Capitaine', $html);
        self::assertStringNotContainsString('Alice', $html);
        self::assertStringContainsString('/alpha.jpg', $html);
        self::assertStringContainsString('03/10/2026', $html);
        self::assertStringContainsString('progression-data-table--riddles', $html);
        self::assertStringContainsString('colspan="7"', $html);

        $ranking = (new PlayerProgressionRenderer())->renderRanking([
            'leaderboard' => [
                'rows' => [[
                    'user_id' => 7,
                    'username' => 'Capitaine',
                    'team_name' => '',
                    'resolved' => 2,
                    'reached_at' => '2026-10-02 14:00:00',
                ]],
                'page' => 1,
                'pages' => 1,
                'current_user_id' => 7,
                'current_position' => 1,
            ],
            'riddles' => [[
                'id' => 40,
                'title' => 'Alpha',
                'ranking' => [
                    'rows' => [],
                    'page' => 1,
                    'pages' => 1,
                    'total' => 0,
                    'current_user_id' => 7,
                ],
            ], [
                'id' => 41,
                'title' => 'Beta',
                'ranking' => [
                    'rows' => [[
                        'user_id' => 8,
                        'username' => 'Alice',
                        'team_name' => 'Corsaires',
                        'solved' => 1,
                        'steps_done' => 4,
                        'elapsed' => 90,
                    ], [
                        'user_id' => 7,
                        'username' => 'Capitaine',
                        'team_name' => '',
                        'solved' => 0,
                    ]],
                    'page' => 1,
                    'pages' => 1,
                    'total' => 2,
                    'current_user_id' => 7,
                ],
            ]],
        ]);

        self::assertStringContainsString('Classement de la chasse', $ranking);
        self::assertStringContainsString('Capitaine', $ranking);
        self::assertStringContainsString('Solo', $ranking);
        self::assertStringContainsString('Pas encore de classement.', $ranking);
        self::assertStringContainsString('Alice', $ranking);
        self::assertStringContainsString('Corsaires', $ranking);
        self::assertStringContainsString('Résolue', $ranking);
        self::assertStringContainsString('En progression', $ranking);
        self::assertStringContainsString('data-riddle-ranking', $ranking);
        self::assertStringContainsString('is-current', $ranking);
        self::assertStringNotContainsString('steps_done', $ranking);
        self::assertStringNotContainsString('elapsed', $ranking);
        self::assertStringNotContainsString('2026-10-02', $ranking);
        self::assertStringNotContainsString('reached_at', $ranking);
        self::assertStringNotContainsString('En tête sur cette énigme', $ranking);
    }
}
