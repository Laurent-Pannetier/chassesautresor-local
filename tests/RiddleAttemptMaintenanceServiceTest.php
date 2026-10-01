<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\RiddleAttemptMaintenanceService;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
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

final class RiddleAttemptMaintenanceServiceTest extends TestCase
{
    /** @dataProvider resetProvider */
    public function testResetScopesMutations(string $action, array $expected): void
    {
        $service = new RiddleAttemptMaintenanceService(
            new MaintenanceAttemptRecorder(),
            new MaintenanceProgressRecorder()
        );

        self::assertSame($expected, $service->reset($action, 12));
    }

    /** @return array<string, array{string,array{attempts:int,statuses:int}}> */
    public function resetProvider(): array
    {
        return [
            'attempts' => ['attempts', ['attempts' => 3, 'statuses' => 0]],
            'statuses' => ['statuses', ['attempts' => 0, 'statuses' => 4]],
            'all' => ['all', ['attempts' => 3, 'statuses' => 4]],
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
