<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationOverlayAjaxHandler;
use ChassesAuTresor\Core\Notifications\NotificationReadAjaxHandler;
use PHPUnit\Framework\TestCase;

final class GamificationShellTest extends TestCase
{
    public function testNotificationHandlersRegisterAuthenticatedEndpoints(): void
    {
        $hooks = [];
        $addAction = static function (string $hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };

        NotificationReadAjaxHandler::register($addAction);
        NotificationOverlayAjaxHandler::register($addAction);

        self::assertSame(
            [NotificationReadAjaxHandler::class, 'handle'],
            $hooks['wp_ajax_cat_notification_mark_read']
        );
        self::assertSame(
            [NotificationOverlayAjaxHandler::class, 'next'],
            $hooks['wp_ajax_cat_notification_overlay_next']
        );
        self::assertSame(
            [NotificationOverlayAjaxHandler::class, 'heartbeat'],
            $hooks['wp_ajax_cat_notification_overlay_heartbeat']
        );
        self::assertSame(
            [NotificationOverlayAjaxHandler::class, 'seen'],
            $hooks['wp_ajax_cat_notification_overlay_seen']
        );
        self::assertArrayNotHasKey('wp_ajax_nopriv_cat_notification_overlay_next', $hooks);
    }

    public function testShellReplacesBothPlaceholdersWithRealTemplateParts(): void
    {
        $root = dirname(__DIR__);
        $placeholder = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/templates/shell-placeholder.php'
        );
        $topbar = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/template-parts/shell/topbar.php'
        );
        $sidebar = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/template-parts/shell/sidebar.php'
        );

        self::assertStringContainsString(
            "get_template_part('template-parts/shell/trophees')",
            $placeholder
        );
        self::assertStringContainsString(
            "get_template_part('template-parts/shell/notifications')",
            $placeholder
        );
        self::assertStringContainsString('cat_user_trophy_count', $topbar);
        self::assertStringNotContainsString('app-topbar__trophy-count">0', $topbar);
        self::assertStringContainsString('app-nav__badge', $sidebar);
    }
}
