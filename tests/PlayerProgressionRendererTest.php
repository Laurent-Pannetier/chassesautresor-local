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
                    'status' => 'en_cours',
                    'steps_done' => 1,
                    'steps_total' => 3,
                    'attempts' => 4,
                    'last_activity' => '2026-10-03 09:00:00',
                ]],
            ],
            [
                ['id' => 12, 'title' => 'Chasse A', 'url' => '/progression/?chasse=12'],
                ['id' => 13, 'title' => 'Chasse B', 'url' => '/progression/?chasse=13'],
            ],
            12
        );

        self::assertStringContainsString('1/2', $html);
        self::assertStringContainsString('3/9', $html);
        self::assertStringContainsString('1 en attente de validation', $html);
        self::assertStringContainsString('>2<', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('Chasse B', $html);
        self::assertStringContainsString('En cours', $html);
        self::assertStringContainsString('Étapes trouvées', $html);
        self::assertStringContainsString('<dd>1</dd>', $html);
        self::assertStringNotContainsString('1/3', $html);
        self::assertStringContainsString('Reprendre', $html);
        self::assertStringContainsString('03/10/2026', $html);
        self::assertStringNotContainsString('<table', $html);
    }
}
