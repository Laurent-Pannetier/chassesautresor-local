<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationsTable;
use ChassesAuTresor\Core\Trophies\TrophyAttributionsTable;
use PHPUnit\Framework\TestCase;

if (!function_exists('update_option')) {
    function update_option(string $name, $value): bool
    {
        $GLOBALS['options'][$name] = $value;

        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return $GLOBALS['options'][$name] ?? $default;
    }
}

final class GamificationTableWpdbStub
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

final class NotificationsAndTrophiesTableTest extends TestCase
{
    protected $backupGlobals = false;

    public function testNotificationTablesKeepEventsAndPerUserStateSeparate(): void
    {
        global $wpdb, $dbDeltaSql;

        $wpdb = new GamificationTableWpdbStub();
        $dbDeltaSql = '';
        NotificationsTable::install();

        self::assertStringContainsString('CREATE TABLE wp_notifications', $dbDeltaSql);
        self::assertStringContainsString('UNIQUE KEY event_key (event_key)', $dbDeltaSql);
        self::assertStringContainsString('CREATE TABLE wp_notification_recipients', $dbDeltaSql);
        self::assertStringContainsString(
            'UNIQUE KEY notification_user (notification_id, user_id)',
            $dbDeltaSql
        );
        self::assertStringContainsString('overlay_seen_at DATETIME NULL', $dbDeltaSql);
    }

    public function testTrophyLedgerEnforcesOneAwardPerRecipient(): void
    {
        global $wpdb, $dbDeltaSql;

        $wpdb = new GamificationTableWpdbStub();
        $dbDeltaSql = '';
        TrophyAttributionsTable::install();

        self::assertStringContainsString('CREATE TABLE wp_trophy_attributions', $dbDeltaSql);
        self::assertStringContainsString(
            'UNIQUE KEY trophy_recipient (trophy_id, recipient_type, recipient_id)',
            $dbDeltaSql
        );
    }

    public function testPluginBootstrapsBothMigrations(): void
    {
        $bootstrap = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core/chassesautresor-core.php'
        );

        self::assertStringContainsString("NotificationsTable::class, 'install'", $bootstrap);
        self::assertStringContainsString("NotificationsTable::class, 'maybeUpgrade'", $bootstrap);
        self::assertStringContainsString("TrophyAttributionsTable::class, 'install'", $bootstrap);
        self::assertStringContainsString("TrophyAttributionsTable::class, 'maybeUpgrade'", $bootstrap);
    }
}
