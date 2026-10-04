<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountToolsRenderer;
use PHPUnit\Framework\TestCase;

final class AccountToolsRendererTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersInjectedSettingsAndAdministrativeForms(): void {
        function esc_html_e($value): void {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html__($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr_e($value): void {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function wp_nonce_field(string $action, string $name): void {
            echo '<input name="' . $name . '" data-action="' . $action . '">';
        }
        function checked(bool $checked): void {
            if ($checked) {
                echo 'checked="checked"';
            }
        }

        if (!function_exists('cat_is_points_ui_enabled')) {
            function cat_is_points_ui_enabled(): bool {
                return true;
            }
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountToolsRenderer.php';

        $renderer = new AccountToolsRenderer(static fn (): float => 72.5, static fn (): bool => true);
        $html = $renderer->render();

        self::assertStringContainsString('1 000 points = <strong>72.5 €</strong>', $html);
        self::assertStringContainsString('checked="checked"', $html);
        self::assertStringContainsString('gestion_points_nonce', $html);
        self::assertStringContainsString('modifier_taux_conversion_nonce', $html);
    }
}
