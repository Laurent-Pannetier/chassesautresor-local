<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\RiddleAttemptMaintenanceService;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleRetryConfiguration;
use ChassesAuTresor\Core\Progress\RiddleRetryPolicyService;
use ChassesAuTresor\Core\Progress\RiddleRetryRepository;
use PHPUnit\Framework\TestCase;

final class MaintenanceAttemptRecorder extends RiddleAttemptService
{
    public function __construct()
    {
    }

    public function deleteForRiddle(int $riddleId): int
    {
        return $riddleId === 12 ? 3 : 0;
    }
}

final class MaintenanceProgressRecorder extends HuntProgressService
{
    public function __construct()
    {
    }

    public function deleteRiddleStatuses(int $riddleId): int
    {
        return $riddleId === 12 ? 4 : 0;
    }
}

final class MaintenanceRetryWpdbStub
{
    public string $prefix = 'wp_';
    public int $deleteResult = 0;

    public function delete(string $table, array $where, array $format): int
    {
        return $this->deleteResult;
    }
}

final class RiddleAttemptMaintenanceServiceTest extends TestCase
{
    /** @dataProvider resetProvider */
    public function testResetScopesMutations(string $action, array $expected): void
    {
        $database = new MaintenanceRetryWpdbStub();
        $database->deleteResult = 2;
        $service = new RiddleAttemptMaintenanceService(
            new MaintenanceAttemptRecorder(),
            new MaintenanceProgressRecorder(),
            new RiddleRetryPolicyService(
                new RiddleRetryRepository($database),
                new RiddleRetryConfiguration(static fn (): int => 0)
            )
        );

        self::assertSame($expected, $service->reset($action, 12));
    }

    /** @return array<string, array{string,array{attempts:int,statuses:int,retry_delays:int}}> */
    public function resetProvider(): array
    {
        return [
            'attempts' => ['attempts', ['attempts' => 3, 'statuses' => 0, 'retry_delays' => 2]],
            'statuses' => ['statuses', ['attempts' => 0, 'statuses' => 4, 'retry_delays' => 0]],
            'all' => ['all', ['attempts' => 3, 'statuses' => 4, 'retry_delays' => 2]],
        ];
    }

    public function testInvalidResetIsIgnored(): void
    {
        $service = new RiddleAttemptMaintenanceService(
            new MaintenanceAttemptRecorder(),
            new MaintenanceProgressRecorder()
        );

        self::assertNull($service->reset('unknown', 12));
        self::assertNull($service->reset('all', 0));
    }
}
