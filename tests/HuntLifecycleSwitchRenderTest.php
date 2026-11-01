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
        self::assertStringContainsString('État chasse', $html);
        self::assertStringContainsString('fa-hourglass-half', $html);
        self::assertStringContainsString('En attente', $html);
        self::assertStringContainsString('switch-control--lifecycle', $html);
        self::assertStringContainsString('data-hunt-lifecycle-label-off', $html);
        self::assertStringContainsString('hunt-lifecycle-card__label-on is-action', $html);
        self::assertStringNotContainsString('hunt-lifecycle-card__label-off is-action', $html);
        self::assertStringNotContainsString('Éditer / Activer', $html);
        self::assertStringNotContainsString('hunt-lifecycle-card__status', $html);
        self::assertStringNotContainsString('is-current', $html);
        self::assertStringNotContainsString('fa-toggle-on', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testActivationGapMarkerExplainsTheBlockingItems(): void
    {
        function esc_attr($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_html($value): string
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_url($value): string
        {
            return (string) $value;
        }
        function esc_html_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function esc_attr_e($value): void
        {
            echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        function __($text, $domain = null): string
        {
            return (string) $text;
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
                'state' => 'editable',
                'checked' => false,
                'disabled' => true,
                'badge_label' => 'Éditable',
                'badge_icon' => 'fa-pen',
                'help' => 'Complétez la chasse et ses énigmes pour pouvoir activer.',
                'gaps' => [
                    [
                        'label' => 'Énigme « Coffre » — Réponse automatique : indiquez une réponse.',
                        'url' => 'https://example.test/outils/?enigme=4',
                    ],
                ],
            ];
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        $html = cat_render_hunt_lifecycle_switch(12);

        self::assertStringContainsString('hunt-lifecycle-card__gaps', $html);
        self::assertStringContainsString('L’activation est bloquée par :', $html);
        self::assertStringContainsString(
            'Énigme « Coffre » — Réponse automatique : indiquez une réponse.',
            $html
        );
        self::assertStringContainsString('title="Activation impossible :', $html);
        self::assertStringContainsString('anchor="hunt-activation-gaps-btn-12"', $html);
    }
}