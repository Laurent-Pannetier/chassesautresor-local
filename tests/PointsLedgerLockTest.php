<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsRepository;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0)
    {
        return '2026-10-24 12:00:00';
    }
}

final class PointsLedgerLockTest extends TestCase
{
    public function testDebitIsRefusedWhenTheLockedBalanceIsTooLow(): void
    {
        $database = new PointsLedgerLockDatabase();
        $repository = new PointsRepository($database);

        $repository->addPoints(3, 10, 'grant', 'admin');
        $this->assertTrue($repository->debitIfAvailable(3, 4, 'hint', 'indice', 1));
        $this->assertFalse($repository->debitIfAvailable(3, 9, 'hint', 'indice', 2));

        $this->assertSame(6, $repository->getBalance(3));
        $this->assertCount(2, $database->rows);
    }

    public function testConversionCannotOverdrawTheLockedBalance(): void
    {
        $database = new PointsLedgerLockDatabase();
        $repository = new PointsRepository($database);
        $repository->addPoints(3, 6, 'grant', 'admin');

        $this->assertGreaterThan(0, $repository->logConversionRequest(3, -6, 1.5));
        $this->assertSame(0, $repository->logConversionRequest(3, -1, 0.2));
        $this->assertSame(0, $repository->getBalance(3));
    }

    public function testAHeldLockBlocksAnotherDebit(): void
    {
        $database = new PointsLedgerLockDatabase();
        $database->locks['cat_points_3'] = true;
        $repository = new PointsRepository($database);

        $this->assertFalse($repository->debitIfAvailable(3, 1, 'hint', 'indice', 1));
        $this->assertSame([], $database->rows);
    }
}

final class PointsLedgerLockDatabase
{
    public string $prefix = 'wp_';

    public int $insert_id = 0;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, bool> */
    public array $locks = [];

    public function prepare(string $query, ...$args): string
    {
        return $query . "\n" . json_encode($args);
    }

    public function get_var($query)
    {
        $parsed = $this->parse($query);
        if (str_contains($parsed['query'], 'GET_LOCK')) {
            $name = (string) $parsed['args'][0];
            if (!empty($this->locks[$name])) {
                return 0;
            }
            $this->locks[$name] = true;

            return 1;
        }
        if (str_contains($parsed['query'], 'RELEASE_LOCK')) {
            unset($this->locks[(string) $parsed['args'][0]]);

            return 1;
        }
        if (str_contains($parsed['query'], 'SELECT balance')) {
            $userId = (int) $parsed['args'][0];
            $balance = null;
            foreach ($this->rows as $row) {
                if ((int) $row['user_id'] === $userId) {
                    $balance = (int) $row['balance'];
                }
            }

            return $balance;
        }

        return null;
    }

    public function insert($table, $data, $format)
    {
        $this->insert_id++;
        $data['id'] = $this->insert_id;
        $this->rows[] = $data;

        return 1;
    }

    public function update($table, $data, $where, $format = null, $whereFormat = null)
    {
        return 1;
    }

    /**
     * @return array{query:string,args:array<int, mixed>}
     */
    private function parse($query): array
    {
        $query = (string) $query;
        if (!str_contains($query, "\n")) {
            return ['query' => $query, 'args' => []];
        }

        [$sql, $json] = explode("\n", $query, 2);
        $args = json_decode($json, true);

        return ['query' => $sql, 'args' => is_array($args) ? $args : []];
    }
}
