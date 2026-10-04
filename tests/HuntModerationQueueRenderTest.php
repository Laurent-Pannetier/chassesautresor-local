<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HuntModerationQueueRenderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersPendingModerationActionsWithoutValidateButton(): void
    {
        function current_user_can($cap): bool
        {
            return $cap === 'administrator';
        }
        function cat_is_single_hunt_mode(): bool
        {
            return true;
        }
        function cat_get_managed_hunt_id_for_user(int $userId = 0): int
        {
            return 42;
        }
        function get_post_type($id)
        {
            return (int) $id === 42 ? 'chasse' : '';
        }
        function get_field($key, $id = null)
        {
            return $key === 'chasse_cache_statut_validation' && (int) $id === 42
                ? 'en_attente'
                : null;
        }
        function get_the_title($id)
        {
            return (int) $id === 42 ? 'Antre de feu' : '';
        }
        function get_permalink($id)
        {
            return (int) $id === 42 ? 'https://example.com/chasse/42' : '';
        }
        function admin_url($path = ''): string
        {
            return 'https://example.com/wp-admin/' . ltrim((string) $path, '/');
        }
        function wp_nonce_field($action, $name): void
        {
            echo '<input type="hidden" name="' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '">';
        }
        function esc_url($url): string
        {
            return (string) $url;
        }
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
        function esc_js($value): string
        {
            return (string) $value;
        }
        function __($value): string
        {
            return (string) $value;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        $html = cat_render_hunt_moderation_queue_card();

        self::assertStringContainsString('hunt-moderation-queue-card', $html);
        self::assertStringContainsString('Antre de feu', $html);
        self::assertStringContainsString('btn-correction', $html);
        self::assertStringContainsString('value="bannir"', $html);
        self::assertStringContainsString('form-traitement-validation-chasse', $html);
        self::assertStringNotContainsString('value="valider"', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReturnsEmptyWhenNoPendingHunt(): void
    {
        function current_user_can($cap): bool
        {
            return $cap === 'administrator';
        }
        function cat_is_single_hunt_mode(): bool
        {
            return true;
        }
        function cat_get_managed_hunt_id_for_user(int $userId = 0): int
        {
            return 42;
        }
        function get_post_type($id)
        {
            return 'chasse';
        }
        function get_field($key, $id = null)
        {
            return 'creation';
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/hunt-lifecycle-functions.php';

        self::assertSame('', cat_render_hunt_moderation_queue_card());
    }
}
