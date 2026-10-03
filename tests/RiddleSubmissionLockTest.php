<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleSubmissionLock;
use PHPUnit\Framework\TestCase;

final class RiddleSubmissionLockTest extends TestCase {
    public function testUsesDatabaseLockScopedToPlayerAndRiddle(): void {
        $database = new class {
            public array $queries = [];
            public int $result = 1;

            public function prepare(string $query, string $key): string {
                $this->queries[] = [$query, $key];
                return $query;
            }

            public function get_var(string $query): int {
                return $this->result;
            }
        };
        $lock = new RiddleSubmissionLock($database);

        self::assertTrue($lock->acquire(7, 42));
        $lock->release(7, 42);
        self::assertSame('cat_submission_7_42', $database->queries[0][1]);
        self::assertStringContainsString('GET_LOCK', $database->queries[0][0]);
        self::assertStringContainsString('RELEASE_LOCK', $database->queries[1][0]);
    }

    public function testRejectsInvalidIdentifiersAndBusyLock(): void {
        $database = new class {
            public int $result = 0;

            public function prepare(string $query, string $key): string {
                return $query;
            }

            public function get_var(string $query): int {
                return $this->result;
            }
        };
        $lock = new RiddleSubmissionLock($database);

        self::assertFalse($lock->acquire(0, 42));
        self::assertFalse($lock->acquire(7, 0));
        self::assertFalse($lock->acquire(7, 42));
    }
}
