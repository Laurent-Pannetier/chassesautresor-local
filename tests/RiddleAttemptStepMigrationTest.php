<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptStepMigration;
use PHPUnit\Framework\TestCase;

if (!function_exists('update_option')) {
    function update_option(string $name, $value): bool {
        $GLOBALS['options'][$name] = $value;
        return true;
    }
}

final class RiddleAttemptStepMigrationWpdbStub {
    public string $prefix = 'wp_';
    public bool $tableExists = true;
    public bool $columnExists = false;
    public array $queries = [];

    public function prepare(string $query, ...$arguments): string {
        return str_replace('%s', "'{$arguments[0]}'", $query);
    }

    public function get_var(string $query): ?string {
        if (str_starts_with($query, 'SHOW TABLES')) {
            return $this->tableExists ? 'wp_enigme_tentatives' : null;
        }

        return $this->columnExists ? 'etape_id' : null;
    }

    public function query(string $query): int {
        $this->queries[] = $query;
        return 1;
    }
}

final class RiddleAttemptStepMigrationTest extends TestCase {
    protected $backupGlobals = false;

    public function testAddsTheOptionalStepColumnAndIndex(): void {
        global $wpdb;

        $wpdb = new RiddleAttemptStepMigrationWpdbStub();
        RiddleAttemptStepMigration::install();

        self::assertCount(1, $wpdb->queries);
        self::assertStringContainsString('ADD etape_id BIGINT UNSIGNED NULL', $wpdb->queries[0]);
        self::assertStringContainsString('ADD KEY etape_id (etape_id)', $wpdb->queries[0]);
    }

    public function testDoesNotAlterAnAlreadyMigratedTable(): void {
        global $wpdb;

        $wpdb = new RiddleAttemptStepMigrationWpdbStub();
        $wpdb->columnExists = true;
        RiddleAttemptStepMigration::install();

        self::assertSame([], $wpdb->queries);
    }

    public function testWaitsUntilTheLegacyAttemptTableExists(): void {
        global $wpdb;

        $wpdb = new RiddleAttemptStepMigrationWpdbStub();
        $wpdb->tableExists = false;
        RiddleAttemptStepMigration::install();

        self::assertSame([], $wpdb->queries);
    }
}
