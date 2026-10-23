<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HintPlayerGateClockTest extends TestCase {
    public function testHintGatesCompareARealUnixTimestamp(): void {
        $files = [
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationService.php',
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockAjaxHandler.php',
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddlePlayerPanelRenderer.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                "current_time('timestamp')",
                $source,
                $file
            );
        }
    }
}
