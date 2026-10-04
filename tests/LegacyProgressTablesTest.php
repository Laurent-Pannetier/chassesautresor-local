<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\EngagementsTable;
use ChassesAuTresor\Core\Progress\HintUnlocksTable;
use ChassesAuTresor\Core\Progress\RiddleAttemptsTable;
use ChassesAuTresor\Core\Progress\RiddleUserStatusesTable;
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

final class LegacyProgressTablesWpdbStub
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

final class LegacyProgressTablesTest extends TestCase
{
    protected $backupGlobals = false;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['options'] = [];
        $GLOBALS['wpdb'] = new LegacyProgressTablesWpdbStub();
        $GLOBALS['dbDeltaSql'] = '';
    }

    public function testEngagementsTableInstall(): void
    {
        EngagementsTable::install();

        self::assertStringContainsString('CREATE TABLE wp_engagements', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('enigme_id BIGINT UNSIGNED NULL', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('UNIQUE KEY user_indice (user_id, indice_id)', $GLOBALS['dbDeltaSql']);
        self::assertSame('1', EngagementsTable::SCHEMA_VERSION);
    }

    public function testHintUnlocksTableInstall(): void
    {
        HintUnlocksTable::install();

        self::assertStringContainsString('CREATE TABLE wp_indices_deblocages', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('points_depenses INT UNSIGNED NOT NULL DEFAULT 0', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('UNIQUE KEY user_indice (user_id, indice_id)', $GLOBALS['dbDeltaSql']);
        self::assertSame('1', HintUnlocksTable::SCHEMA_VERSION);
    }

    public function testRiddleAttemptsTableInstallIncludesStepColumn(): void
    {
        RiddleAttemptsTable::install();

        self::assertStringContainsString('CREATE TABLE wp_enigme_tentatives', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('etape_id BIGINT UNSIGNED NULL', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('UNIQUE KEY tentative_uid (tentative_uid)', $GLOBALS['dbDeltaSql']);
        self::assertSame('1', RiddleAttemptsTable::SCHEMA_VERSION);
    }

    public function testRiddleUserStatusesTableInstall(): void
    {
        RiddleUserStatusesTable::install();

        self::assertStringContainsString('CREATE TABLE wp_enigme_statuts_utilisateur', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString('PRIMARY KEY  (user_id, enigme_id)', $GLOBALS['dbDeltaSql']);
        self::assertStringContainsString("'soumis'", $GLOBALS['dbDeltaSql']);
        self::assertSame('1', RiddleUserStatusesTable::SCHEMA_VERSION);
    }

    public function testBootstrapRegistersLegacyTableInstallers(): void
    {
        $bootstrap = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/chassesautresor-core.php'
        );

        foreach ([
            'EngagementsTable',
            'HintUnlocksTable',
            'RiddleAttemptsTable',
            'RiddleUserStatusesTable',
        ] as $class) {
            self::assertStringContainsString(
                "ChassesAuTresor\\Core\\Progress\\{$class}::class, 'install'",
                $bootstrap
            );
            self::assertStringContainsString(
                "ChassesAuTresor\\Core\\Progress\\{$class}::class, 'maybeUpgrade'",
                $bootstrap
            );
        }
    }
}
