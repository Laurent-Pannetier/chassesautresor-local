<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleRetryConfiguration;
use ChassesAuTresor\Core\Progress\RiddleRetryPolicyService;
use ChassesAuTresor\Core\Progress\RiddleRetryRepository;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $message): string
    {
        return $message;
    }
}

final class RiddleRetryWpdbStub
{
    public string $prefix = 'wp_';
    public ?object $row = null;
    public array $preparedArguments = [];
    public string $preparedQuery = '';
    public int $queryResult = 1;
    public string $query = '';
    public int $deleteResult = 0;
    public array $deleteArguments = [];

    public function prepare(string $query, ...$arguments): string
    {
        $this->preparedQuery = $query;
        $this->preparedArguments = $arguments;

        return $query;
    }

    public function get_row(string $query): ?object
    {
        return $this->row;
    }

    public function query(string $query): int
    {
        $this->query = $query;

        return $this->queryResult;
    }

    public function delete(string $table, array $where, array $format): int
    {
        $this->deleteArguments = [$table, $where, $format];

        return $this->deleteResult;
    }
}

final class RiddleRetryPolicyServiceTest extends TestCase
{
    private RiddleRetryWpdbStub $wpdb;
    private RiddleRetryPolicyService $service;

    protected function setUp(): void
    {
        $GLOBALS['riddle_retry_fields'] = [
            10 => [RiddleRetryConfiguration::FIELD_NAME => 300],
        ];
        $this->wpdb = new RiddleRetryWpdbStub();
        $this->service = new RiddleRetryPolicyService(
            new RiddleRetryRepository($this->wpdb),
            new RiddleRetryConfiguration(
                static fn (string $name, int $postId) => $GLOBALS['riddle_retry_fields'][$postId][$name] ?? null
            ),
            static fn (): DateTimeImmutable => new DateTimeImmutable('2026-10-03 12:00:00', new DateTimeZone('UTC'))
        );
    }

    public function testNoPreviousFailureAllowsSubmission(): void
    {
        self::assertSame(
            [
                'blocked' => false,
                'retry_at' => null,
                'server_now' => '2026-10-03T12:00:00Z',
                'remaining_seconds' => 0,
                'message' => '',
            ],
            $this->service->getState(7, 10)
        );
    }

    public function testActiveDelayReturnsStableUtcContract(): void
    {
        $this->wpdb->row = (object) [
            'retry_at_utc' => '2026-10-03 12:05:00',
            'source_tentative_uid' => 'attempt-1',
        ];

        $state = $this->service->getState(7, 10);

        self::assertTrue($state['blocked']);
        self::assertSame('2026-10-03T12:05:00Z', $state['retry_at']);
        self::assertSame(300, $state['remaining_seconds']);
        self::assertNotSame('', $state['message']);
        self::assertSame([7, 10], $this->wpdb->preparedArguments);
    }

    public function testSubmissionIsAllowedAtRetryInstant(): void
    {
        $this->wpdb->row = (object) ['retry_at_utc' => '2026-10-03 12:00:00'];

        self::assertFalse($this->service->getState(7, 10)['blocked']);
    }

    public function testOnlyFailureRenewsDelay(): void
    {
        foreach (['bon', 'variante', 'attente'] as $result) {
            self::assertTrue($this->service->renewAfterFailure(7, 10, $result, 'attempt-1'));
        }
        self::assertSame('', $this->wpdb->query);

        self::assertTrue($this->service->renewAfterFailure(7, 10, 'faux', 'attempt-1'));
        self::assertStringContainsString('INSERT INTO wp_enigme_delais_soumission', $this->wpdb->query);
        self::assertSame(
            [7, 10, '2026-10-03 12:05:00', 'attempt-1', '2026-10-03 12:00:00'],
            $this->wpdb->preparedArguments
        );
    }

    public function testMissingOrZeroConfigurationDisablesDelay(): void
    {
        $GLOBALS['riddle_retry_fields'][10][RiddleRetryConfiguration::FIELD_NAME] = 0;
        $this->wpdb->row = (object) ['retry_at_utc' => '2026-10-03 12:05:00'];

        self::assertFalse($this->service->getState(7, 10)['blocked']);
        self::assertTrue($this->service->renewAfterFailure(7, 10, 'faux', 'attempt-1'));
        self::assertSame('', $this->wpdb->query);
    }

    public function testClearForRiddleDelegatesToRepository(): void
    {
        $this->wpdb->deleteResult = 2;

        self::assertSame(2, $this->service->clearForRiddle(10));
        self::assertSame(
            ['wp_enigme_delais_soumission', ['enigme_id' => 10], ['%d']],
            $this->wpdb->deleteArguments
        );
    }
}
