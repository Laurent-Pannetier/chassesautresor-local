<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamTable;
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

final class TeamTableWpdbStub
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

final class TeamTableTest extends TestCase
{
    protected $backupGlobals = false;

    public function testInstallCreatesTheThreeTables(): void
    {
        global $wpdb, $dbDeltaSql;

        $wpdb = new TeamTableWpdbStub();
        $dbDeltaSql = '';
        $GLOBALS['options'] = [];

        TeamTable::install();

        self::assertStringContainsString('CREATE TABLE wp_equipes', $dbDeltaSql);
        self::assertStringContainsString('UNIQUE KEY nom_key (nom_key)', $dbDeltaSql);
        self::assertStringContainsString('UNIQUE KEY join_code (join_code)', $dbDeltaSql);
        self::assertStringContainsString('CREATE TABLE wp_equipe_membres', $dbDeltaSql);
        self::assertStringContainsString('UNIQUE KEY equipe_user (equipe_id, user_id)', $dbDeltaSql);
        self::assertStringContainsString('CREATE TABLE wp_equipe_places', $dbDeltaSql);
        self::assertStringContainsString('PRIMARY KEY  (user_id)', $dbDeltaSql);
        self::assertSame('1', TeamTable::SCHEMA_VERSION);
    }

    public function testBootstrapRegistersTheInstaller(): void
    {
        $bootstrap = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core/chassesautresor-core.php'
        );

        self::assertStringContainsString("require_once __DIR__ . '/src/Teams/TeamTable.php'", $bootstrap);
        self::assertStringContainsString("TeamTable::class, 'install'", $bootstrap);
        self::assertStringContainsString("TeamTable::class, 'maybeUpgrade'", $bootstrap);
        self::assertStringContainsString('TeamRequestHandler::register', $bootstrap);
    }
}
