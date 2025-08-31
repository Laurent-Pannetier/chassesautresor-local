<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_CLI')) {
    class WP_CLI
    {
        public static function log($message): void {}
        public static function success($message): void {}
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'timestamp'
            ? strtotime('2023-01-01 00:00:00')
            : '2023-01-01 00:00:00';
    }
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options);
    }
}

// Global storage for meta and transients.
$cat_test_user_meta = [];
$cat_test_transients = [];

if (!function_exists('get_users')) {
    function get_users($args)
    {
        return [1];
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single)
    {
        global $cat_test_user_meta;
        return $cat_test_user_meta[$user_id][$key] ?? [];
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key)
    {
        global $cat_test_user_meta;
        unset($cat_test_user_meta[$user_id][$key]);
    }
}

if (!function_exists('get_transient')) {
    function get_transient($key)
    {
        global $cat_test_transients;
        return $cat_test_transients[$key] ?? false;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($key)
    {
        global $cat_test_transients;
        unset($cat_test_transients[$key]);
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/cli/class-cat-cli-command.php';

/**
 * @covers Cat_CLI_Command
 */
class MessageMigrationCommandTest extends TestCase
{
    private MigrationDummyWpdb $wpdb;

    protected function setUp(): void
    {
        global $cat_test_user_meta, $cat_test_transients, $wpdb;
        $cat_test_user_meta = [
            1 => [
                '_myaccount_messages' => [
                    'k1' => [
                        'text'       => 'Persist',
                        'type'       => 'info',
                        'expires_at' => '2023-02-01 00:00:00',
                    ],
                ],
                '_myaccount_flash_messages' => [
                    ['text' => 'Flash', 'type' => 'error'],
                ],
            ],
        ];
        $cat_test_transients = [
            'cat_site_messages' => [
                ['type' => 'warning', 'content' => 'Global'],
            ],
        ];
        $this->wpdb = new MigrationDummyWpdb();
        $wpdb       = $this->wpdb;
    }

    public function test_migrate_messages_moves_all_data(): void
    {
        $cmd = new Cat_CLI_Command();
        $cmd->migrate_messages();

        global $cat_test_user_meta, $cat_test_transients;

        $repo = new UserMessageRepository($this->wpdb);
        $this->assertCount(2, $repo->get(1, null, null));
        $this->assertCount(1, $repo->get(0, 'site', null));

        $this->assertArrayNotHasKey('_myaccount_messages', $cat_test_user_meta[1] ?? []);
        $this->assertArrayNotHasKey('_myaccount_flash_messages', $cat_test_user_meta[1] ?? []);
        $this->assertArrayNotHasKey('cat_site_messages', $cat_test_transients);
    }
}

class MigrationDummyWpdb
{
    public string $prefix = 'wp_';

    public int $insert_id = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $data = [];

    public function insert(string $table, array $data, array $format): void
    {
        $this->insert_id++;
        $data['id']             = $this->insert_id;
        $this->data[$this->insert_id] = $data;
    }

    public function get_results(string $sql, $output): array
    {
        $rows = array_values($this->data);

        if (preg_match('/user_id = (\d+)/', $sql, $m)) {
            $rows = array_filter($rows, fn ($r) => (int) $r['user_id'] === (int) $m[1]);
        }

        if (preg_match("/status = '([^']+)'/", $sql, $m)) {
            $rows = array_filter($rows, fn ($r) => $r['status'] === $m[1]);
        }

        return array_values($rows);
    }

    public function delete(string $table, array $where, array $whereFormat): void
    {
        unset($this->data[$where['id']]);
    }
}
