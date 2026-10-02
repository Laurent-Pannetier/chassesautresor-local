<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepProgressTable;
use PHPUnit\Framework\TestCase;

if (!function_exists('update_option')) {
    function update_option(string $name, $value): bool {
        $GLOBALS['options'][$name] = $value;
        return true;
    }
}

final class RiddleStepProgressTableWpdbStub {
    public string $prefix = 'wp_';

    public function get_charset_collate(): string {
        return 'utf8mb4_unicode_ci';
    }
}

final class RiddleStepProgressTableTest extends TestCase {
    protected $backupGlobals = false;

    public function testInstallCreatesTheStepProgressTableAndIndexes(): void {
        global $wpdb, $dbDeltaSql;

        $wpdb = new RiddleStepProgressTableWpdbStub();
        $dbDeltaSql = '';

        RiddleStepProgressTable::install();

        self::assertStringContainsString('CREATE TABLE wp_enigme_etapes_progression', $dbDeltaSql);
        self::assertStringContainsString('UNIQUE KEY user_step (user_id, etape_id)', $dbDeltaSql);
        self::assertStringContainsString('KEY user_riddle (user_id, enigme_id)', $dbDeltaSql);
        self::assertStringContainsString('KEY step_status (etape_id, statut)', $dbDeltaSql);
        self::assertSame('1', RiddleStepProgressTable::SCHEMA_VERSION);
    }
}
