<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ZZGetSiteMessagesTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';
        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages.php';
        global $wpdb;
        $wpdb = new MessagesDummyWpdb();
        session_start();
        $_SESSION['cat_site_messages'] = [];
    }

    public function test_expired_message_is_purged(): void
    {
        global $wpdb;
        $repo = new UserMessageRepository($wpdb);
        $repo->insert(0, json_encode(['type' => 'info', 'content' => 'Expired']), 'site', '2022-12-31 23:59:59');
        $repo->insert(0, json_encode(['type' => 'info', 'content' => 'Active']), 'site', '2023-01-02 00:00:00');

        $html = get_site_messages();

        $this->assertStringContainsString('Active', $html);
        $this->assertStringNotContainsString('Expired', $html);
    }
}

class MessagesDummyWpdb
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
        $data['id'] = $this->insert_id;
        $this->data[$this->insert_id] = $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_results(string $sql, $output): array
    {
        return array_values($this->data);
    }

    public function query(string $sql): void
    {
        if (str_contains($sql, 'DELETE FROM')) {
            $now = current_time('mysql');
            $this->data = array_filter(
                $this->data,
                fn($r) => $r['expires_at'] === null || $r['expires_at'] >= $now
            );
        }
    }
}
