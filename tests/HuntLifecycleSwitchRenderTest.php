<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HuntLifecycleSwitchRenderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersStateBadgeAndLifecycleSwitchMarkup(): void
    {
        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function checked($checked): void
        {
            if ($checked) {
                echo 'checked="checked"';
            }
        }
        function disabled($disabled): void
        {
            if ($disabled) {
                echo 'disabled="disabled"';
            }
        }
        function cat_get_hunt_lifecycle_view(int $huntId = 0): array
        {
            return [
                'hunt_id' => 12,
                'state' => 'pending',
                'checked' => false,
                'disabled' => false,
                'status_label' => 'Demande de validation en attente',
                'badge_label' => 'En attente',
                'badge_icon' => 'fa-hourglass-half',
                'help' => 'Aide',
            ];
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        $html = cat_render_hunt_lifecycle_switch(12);

        self::assertStringContainsString('hunt-lifecycle-card__state-badge', $html);
        self::assertStringContainsString('fa-hourglass-half', $html);
        self::assertStringContainsString('En attente', $html);
        self::assertStringContainsString('switch-control--lifecycle', $html);
        self::assertStringContainsString('data-hunt-lifecycle-label-off', $html);
        self::assertStringContainsString('data-hunt-lifecycle-label-on', $html);
        self::assertStringContainsString('is-current', $html);
        self::assertStringNotContainsString('fa-toggle-on', $html);
    }
}