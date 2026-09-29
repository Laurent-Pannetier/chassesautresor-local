<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptRepository.php';

class RiddleAttemptWpdbStub
{
    public string $prefix = 'wp_';
    public ?string $status = null;
    public int $updateResult = 1;
    public array $preparedArguments = [];
    public array $updateArguments = [];
    public ?object $row = null;

    public function prepare(string $query, ...$arguments): string
    {
        $this->preparedArguments = $arguments;
        return $query;
    }

    public function get_var(string $query): ?string
    {
        return $this->status;
    }

    public function get_row(string $query): ?object
    {
        return $this->row;
    }

    public function update(string $table, array $data, array $where, array $format, array $whereFormat): int
    {
        $this->updateArguments = [$table, $data, $where, $format, $whereFormat];
        return $this->updateResult;
    }
}

class RiddleAttemptRepositoryTest extends TestCase
{
    public function testUserRiddleStatusIsReadWithBothIdentifiers(): void
    {
        $wpdb = new RiddleAttemptWpdbStub();
        $wpdb->status = 'resolue';
        $repository = new RiddleAttemptRepository($wpdb);

        $this->assertSame('resolue', $repository->findUserRiddleStatus(7, 10));
        $this->assertSame([7, 10], $wpdb->preparedArguments);
    }

    public function testPendingAttemptIsUpdatedWithAtomicConditions(): void
    {
        $wpdb = new RiddleAttemptWpdbStub();
        $repository = new RiddleAttemptRepository($wpdb);

        $this->assertTrue($repository->markPendingAsProcessed('attempt-1', 'bon'));
        $this->assertSame('wp_enigme_tentatives', $wpdb->updateArguments[0]);
        $this->assertSame(['resultat' => 'bon', 'traitee' => 1], $wpdb->updateArguments[1]);
        $this->assertSame(
            ['tentative_uid' => 'attempt-1', 'resultat' => 'attente', 'traitee' => 0],
            $wpdb->updateArguments[2]
        );

        $wpdb->updateResult = 0;
        $this->assertFalse($repository->markPendingAsProcessed('attempt-1', 'faux'));
    }

    public function testSuccessfulAttemptLookupUsesUserAndRiddleIdentifiers(): void
    {
        $wpdb = new RiddleAttemptWpdbStub();
        $repository = new RiddleAttemptRepository($wpdb);

        $this->assertFalse($repository->hasSuccessfulAttempt(7, 10));
        $this->assertSame([7, 10], $wpdb->preparedArguments);

        $wpdb->status = '1';
        $this->assertTrue($repository->hasSuccessfulAttempt(7, 10));
    }

    public function testLatestPendingAttemptLookupUsesUserAndRiddleIdentifiers(): void
    {
        $wpdb = new RiddleAttemptWpdbStub();
        $wpdb->row = (object) ['id' => 42, 'date_tentative' => '2026-09-29 12:00:00'];
        $repository = new RiddleAttemptRepository($wpdb);

        $this->assertSame($wpdb->row, $repository->findLatestPendingForUserAndRiddle(7, 10));
        $this->assertSame([7, 10], $wpdb->preparedArguments);

        $wpdb->row = null;
        $this->assertNull($repository->findLatestPendingForUserAndRiddle(7, 10));
    }
}
