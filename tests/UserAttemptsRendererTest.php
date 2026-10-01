<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\UserAttemptsRenderer;
use PHPUnit\Framework\TestCase;

final class UserAttemptsRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersConfiguredEmptyMessage(): void
    {
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptsRenderer.php';

        $html = (new UserAttemptsRenderer())->rows([
            'tentatives' => [],
            'filtered_total' => 0,
            'no_results_message' => 'Aucun résultat.',
        ]);

        self::assertStringContainsString('class="tentatives-empty"', $html);
        self::assertStringContainsString('Aucun résultat.', $html);
    }
}
