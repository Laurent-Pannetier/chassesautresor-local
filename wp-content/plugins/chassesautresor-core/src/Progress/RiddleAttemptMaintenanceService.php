<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class RiddleAttemptMaintenanceService
{
    private RiddleAttemptService $attempts;
    private HuntProgressService $progress;

    public function __construct(RiddleAttemptService $attempts, HuntProgressService $progress)
    {
        $this->attempts = $attempts;
        $this->progress = $progress;
    }

    /** @return array{attempts:int,statuses:int}|null */
    public function reset(string $action, int $riddleId): ?array
    {
        if ($riddleId <= 0 || !in_array($action, ['attempts', 'statuses', 'all'], true)) {
            return null;
        }

        return [
            'attempts' => in_array($action, ['attempts', 'all'], true)
                ? $this->attempts->deleteForRiddle($riddleId)
                : 0,
            'statuses' => in_array($action, ['statuses', 'all'], true)
                ? $this->progress->deleteRiddleStatuses($riddleId)
                : 0,
        ];
    }
}
