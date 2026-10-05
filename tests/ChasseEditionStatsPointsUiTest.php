<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ChasseEditionStatsPointsUiTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testChasseEditionStatsGatePointsUiBehindSiteSetting(): void
    {
        $edition = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/chasse/chasse-edition-main.php'
        );
        $partial = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/chasse/partials/chasse-partial-enigmes.php'
        );
        $enigmeEdition = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/enigme/enigme-edition-main.php'
        );
        $orgStats = (string) file_get_contents(
            self::THEME_PATH . '/template-parts/organisateur/panneaux/organisateur-edition-statistiques.php'
        );

        self::assertStringContainsString('cat_is_points_ui_enabled()', $edition);
        self::assertStringContainsString('if ($points_ui_enabled)', $edition);
        self::assertStringContainsString("'show_points'", $edition);
        self::assertStringContainsString('$points_ui_enabled', $edition);

        self::assertStringContainsString('$show_points', $partial);
        self::assertStringContainsString('if ($show_points)', $partial);

        self::assertStringContainsString('cat_is_points_ui_enabled()', $enigmeEdition);
        self::assertStringContainsString('if ($points_ui_enabled)', $enigmeEdition);

        self::assertStringContainsString('cat_is_points_ui_enabled()', $orgStats);
        self::assertStringContainsString('if ($points_ui_enabled)', $orgStats);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnigmesPartialHidesPointsColumnWhenDisabled(): void
    {
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function esc_html__(string $value, string $domain = ''): string
        {
            return $value;
        }

        function esc_url($value): string
        {
            return (string) $value;
        }

        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function get_permalink($id): string
        {
            return 'https://example.test/enigme/' . (int) $id;
        }

        $args = [
            'title' => 'Énigmes',
            'total' => 10,
            'show_points' => false,
            'cols_etiquette' => [2, 3, 4],
            'enigmes' => [[
                'id' => 3,
                'titre' => 'Énigme A',
                'engagements' => 4,
                'tentatives' => 8,
                'points' => 120,
                'resolutions' => 2,
            ]],
        ];

        ob_start();
        include self::THEME_PATH . '/template-parts/chasse/partials/chasse-partial-enigmes.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Énigme A', $html);
        self::assertStringContainsString('Tentatives', $html);
        self::assertStringContainsString('Trouvées', $html);
        self::assertStringNotContainsString('>Points<', $html);
        self::assertStringNotContainsString('>120<', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnigmesPartialShowsPointsColumnWhenEnabled(): void
    {
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function esc_html__(string $value, string $domain = ''): string
        {
            return $value;
        }

        function esc_url($value): string
        {
            return (string) $value;
        }

        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        function get_permalink($id): string
        {
            return 'https://example.test/enigme/' . (int) $id;
        }

        $args = [
            'title' => 'Énigmes',
            'total' => 10,
            'show_points' => true,
            'cols_etiquette' => [2, 3, 4, 5],
            'enigmes' => [[
                'id' => 3,
                'titre' => 'Énigme A',
                'engagements' => 4,
                'tentatives' => 8,
                'points' => 120,
                'resolutions' => 2,
            ]],
        ];

        ob_start();
        include self::THEME_PATH . '/template-parts/chasse/partials/chasse-partial-enigmes.php';
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Points', $html);
        self::assertStringContainsString('120', $html);
    }
}
