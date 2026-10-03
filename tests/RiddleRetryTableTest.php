<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleRetryTable;
use PHPUnit\Framework\TestCase;

final class RiddleRetryTableWpdbStub
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

final class RiddleRetryTableTest extends TestCase
{
    protected $backupGlobals = false;

    public function testInstallCreatesTableWithBusinessKey(): void
    {
        global $wpdb, $dbDeltaSql;

        $wpdb = new RiddleRetryTableWpdbStub();
        $dbDeltaSql = '';

        RiddleRetryTable::install();

        self::assertStringContainsString('CREATE TABLE wp_enigme_delais_soumission', $dbDeltaSql);
        self::assertStringContainsString('PRIMARY KEY  (user_id, enigme_id)', $dbDeltaSql);
        self::assertStringContainsString('retry_at_utc DATETIME NOT NULL', $dbDeltaSql);
        self::assertSame('1', RiddleRetryTable::SCHEMA_VERSION);
    }
}
