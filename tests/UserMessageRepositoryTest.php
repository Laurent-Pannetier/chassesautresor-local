<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!function_exists('current_time')) {
    function current_time(string $type): string
    {
        return '2023-01-01 00:00:00';
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/messages/class-user-message-repository.php';

/**
 * @covers UserMessageRepository
 */
class UserMessageRepositoryTest extends TestCase
{
    private DummyWpdb $wpdb;

    private UserMessageRepository $repo;

    protected function setUp(): void
    {
        $this->wpdb = new DummyWpdb();
        $this->repo = new UserMessageRepository($this->wpdb);
    }

    public function test_insert_stores_row_and_returns_id(): void
    {
        $id = $this->repo->insert(1, 'Salut', 'info', null);

        $this->assertSame(1, $id);
        $this->assertSame('Salut', $this->wpdb->data[$id]['message']);
    }

    public function test_update_modifies_row(): void
    {
        $id = $this->repo->insert(1, 'Hello', 'info', null);

        $this->repo->update($id, ['status' => 'success']);
        $rows = $this->repo->get(1);

        $this->assertSame('success', $rows[0]['status']);
    }

    public function test_delete_removes_row(): void
    {
        $id = $this->repo->insert(1, 'Hello', 'info', null);

        $this->repo->delete($id);
        $rows = $this->repo->get(1);

        $this->assertSame([], $rows);
    }

    public function test_get_filters_by_user_status_and_expiration(): void
    {
        $this->repo->insert(1, 'Active', 'info', null);
        $this->repo->insert(1, 'Expired', 'info', '2022-12-31 23:59:59');
        $this->repo->insert(2, 'Other', 'warning', '2023-01-02 00:00:00');

        $userMessages = $this->repo->get(1, null, false);
        $this->assertCount(1, $userMessages);

        $statusMessages = $this->repo->get(null, 'warning', null);
        $this->assertCount(1, $statusMessages);

        $expiredMessages = $this->repo->get(null, null, true);
        $this->assertCount(1, $expiredMessages);
    }

    public function test_purge_expired_deletes_rows(): void
    {
        $this->repo->insert(1, 'Old', 'info', '2022-12-31 23:59:59');
        $this->repo->insert(1, 'New', 'info', '2023-01-02 00:00:00');

        $this->repo->purgeExpired();

        $rows = $this->repo->get(1, null, null);
        $this->assertCount(1, $rows);
        $this->assertSame('New', $rows[0]['message']);
    }
}

class DummyWpdb
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

    public function update(string $table, array $data, array $where, array $format, array $whereFormat): void
    {
        $id = $where['id'];
        $this->data[$id] = array_merge($this->data[$id], $data);
    }

    public function delete(string $table, array $where, array $whereFormat): void
    {
        unset($this->data[$where['id']]);
    }

    public function prepare(string $query, array $params): string
    {
        return vsprintf(str_replace(['%d', '%s'], ['%d', '%s'], $query), $params);
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

        $now = current_time('mysql');
        if (str_contains($sql, 'expires_at IS NOT NULL AND expires_at < NOW()')) {
            $rows = array_filter(
                $rows,
                fn($r) => $r['expires_at'] !== null && $r['expires_at'] < $now
            );
        } elseif (str_contains($sql, '(expires_at IS NULL OR expires_at >= NOW())')) {
            $rows = array_filter(
                $rows,
                fn($r) => $r['expires_at'] === null || $r['expires_at'] >= $now
            );
        }

        return array_values($rows);
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
