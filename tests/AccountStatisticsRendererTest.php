<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountStatisticsRenderer;
use PHPUnit\Framework\TestCase;

final class AccountStatisticsRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersStatisticsWithoutAThemeTemplateDependency(): void {
        function __($value): string {
            return (string) $value;
        }
        function esc_html__($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountStatisticsRenderer.php';

        $pointsFactory = static fn (): object => new class {
            public function getTotalUsed(): int {
                return 125;
            }

            public function getTotalInCirculation(): int {
                return 875;
            }
        };
        $winsCounter = static fn (int $userId): int => $userId === 7 ? 3 : 0;
        $html = (new AccountStatisticsRenderer($pointsFactory, $winsCounter))->render(7);

        self::assertStringContainsString('Points utilisés', $html);
        self::assertStringContainsString('125', $html);
        self::assertStringContainsString('875', $html);
        self::assertStringContainsString('Chasses gagnées : 3', $html);
    }
}
