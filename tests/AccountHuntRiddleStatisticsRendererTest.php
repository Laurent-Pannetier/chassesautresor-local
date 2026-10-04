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
    public function testRendersRiddleOverviewTable(): void
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

        self::assertStringContainsString('myaccount-riddle-stats-table', $html);
        self::assertStringContainsString('Énigme Alpha', $html);
        self::assertStringContainsString('3 (1 joueur)', $html);
        self::assertStringContainsString('1. alice · 2. bob', $html);
        self::assertStringContainsString('>4</td>', $html);
        self::assertStringContainsString('>2</td>', $html);
        self::assertStringContainsString('>11</td>', $html);
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
}
