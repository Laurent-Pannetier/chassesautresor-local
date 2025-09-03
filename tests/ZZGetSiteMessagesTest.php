<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'timestamp'
            ? time()
            : gmdate('Y-m-d H:i:s', time());
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}

if (!function_exists('wp_date')) {
    function wp_date(string $format, int $timestamp, ?DateTimeZone $timezone = null): string
    {
        $dt = new DateTime('@' . $timestamp);
        if ($timezone instanceof DateTimeZone) {
            $dt->setTimezone($timezone);
        }
        return $dt->format($format);
    }
}

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
        $rows = array_values($this->data);

        if (preg_match('/user_id = (\d+)/', $sql, $m)) {
            $rows = array_filter($rows, fn($r) => (int) $r['user_id'] === (int) $m[1]);
        }

        if (preg_match("/status = '([^']+)'/", $sql, $m)) {
            $rows = array_filter($rows, fn($r) => $r['status'] === $m[1]);
        }

        if (preg_match("/\(expires_at IS NULL OR expires_at >= '([^']+)'\)/", $sql, $m)) {
            $rows = array_filter(
                $rows,
                fn($r) => $r['expires_at'] === null || $r['expires_at'] >= $m[1]
            );
        } elseif (preg_match("/expires_at IS NOT NULL AND expires_at < '([^']+)'/", $sql, $m)) {
            $rows = array_filter(
                $rows,
                fn($r) => $r['expires_at'] !== null && $r['expires_at'] < $m[1]
            );
        }

        return array_values($rows);
    }

    public function prepare(string $query, array $params): string
    {
        $placeholders = array_map(
            fn($p) => is_int($p) ? $p : "'{$p}'",
            $params
        );

        return vsprintf($query, $placeholders);
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
