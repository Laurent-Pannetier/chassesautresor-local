<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsTable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsTable.php';

if (!function_exists('update_option')) {
    function update_option(string $name, $value): bool
    {
        $GLOBALS['options'][$name] = $value;

        return true;
    }
}

class PointsTableWpdb
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

class PointsTableTest extends TestCase
{
    protected $backupGlobals = false;

    public function testInstallCreatesCompletePointsLedger(): void
    {
        global $wpdb, $dbDeltaSql;

        $wpdb = new PointsTableWpdb();
        $dbDeltaSql = '';

        PointsTable::install();

        $this->assertStringContainsString('CREATE TABLE wp_user_points', $dbDeltaSql);
        $this->assertStringContainsString("'indice'", $dbDeltaSql);
        $this->assertStringContainsString('request_status', $dbDeltaSql);
        $this->assertStringContainsString('KEY user_id (user_id)', $dbDeltaSql);
        $this->assertStringContainsString('KEY created_at (created_at)', $dbDeltaSql);
        $this->assertSame('1', PointsTable::SCHEMA_VERSION);
    }
}
