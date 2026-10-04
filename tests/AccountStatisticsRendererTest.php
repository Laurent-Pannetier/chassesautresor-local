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

        if (!function_exists('cat_is_points_ui_enabled')) {
            function cat_is_points_ui_enabled(): bool {
                return true;
            }
        }

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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHidesPointsCardsWhenPointsUiIsDisabled(): void {
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
        function cat_is_points_ui_enabled(): bool {
            return false;
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
        $html = (new AccountStatisticsRenderer($pointsFactory, static fn (): int => 1))->render(1);

        self::assertStringNotContainsString('Points utilisés', $html);
        self::assertStringContainsString('Chasses gagnées : 1', $html);
    }
}
