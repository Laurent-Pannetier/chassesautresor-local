<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages.php';

if (!function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return $GLOBALS['options'][$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $name, $value): bool
    {
        $GLOBALS['options'][$name] = $value;

        return true;
    }
}

class UserMessagesTableWpdb
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }
}

class UserMessagesTableTest extends TestCase
{
    protected $backupGlobals = false;

    public function test_install_creates_table(): void
    {
        global $wpdb, $dbDeltaSql;
        $wpdb       = new UserMessagesTableWpdb();
        $dbDeltaSql = '';
        cat_install_user_messages_table();
        $this->assertStringContainsString('CREATE TABLE wp_user_messages', $dbDeltaSql);
        $this->assertStringContainsString('locale VARCHAR(10)', $dbDeltaSql);
        $this->assertStringContainsString('KEY user_id (user_id)', $dbDeltaSql);
        $this->assertStringContainsString('KEY status (status)', $dbDeltaSql);
        $this->assertStringContainsString('KEY expires_at (expires_at)', $dbDeltaSql);
        $this->assertSame('1', ChassesAuTresor\Core\Messages\UserMessagesTable::SCHEMA_VERSION);
    }
}
