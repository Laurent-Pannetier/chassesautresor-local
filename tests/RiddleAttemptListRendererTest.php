<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptListRenderer;
use PHPUnit\Framework\TestCase;

final class RiddleAttemptListRendererTest extends TestCase
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
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListRenderer.php';

        $html = (new RiddleAttemptListRenderer())->render(['tentatives' => []]);

        self::assertSame('<p>Aucune tentative de soumission.</p>', $html);
    }
}
