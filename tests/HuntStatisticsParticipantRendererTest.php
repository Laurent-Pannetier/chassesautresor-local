<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatisticsParticipantRenderer;
use PHPUnit\Framework\TestCase;

final class HuntStatisticsParticipantRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReturnsEmptyFragmentWithoutParticipants(): void
    {
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsParticipantRenderer.php';

        $html = (new HuntStatisticsParticipantRenderer())->render(
            12,
            [],
            ['page' => 1, 'limit' => 25, 'order' => 'ASC'],
            0,
            0,
            'inscription'
        );

        self::assertSame('', $html);
    }
}
