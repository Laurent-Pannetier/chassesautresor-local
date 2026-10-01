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
}
