<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    function wp_next_scheduled(string $hook)
    {
        global $schedulerScheduledTimestamp;

        return $schedulerScheduledTimestamp;
    }

    function wp_schedule_event(int $timestamp, string $recurrence, string $hook): void
    {
        global $schedulerScheduleCall;
        $schedulerScheduleCall = compact('timestamp', 'recurrence', 'hook');
    }

    function wp_unschedule_event(int $timestamp, string $hook): void
    {
        global $schedulerUnscheduleCall;
        $schedulerUnscheduleCall = compact('timestamp', 'hook');
    }

    function time(): int
    {
        return 100;
    }

    function current_time(string $type): string
    {
        return '2026-09-29 12:00:00';
    }

    function get_posts(array $args): array
    {
        global $schedulerQueryArgs;
        $schedulerQueryArgs = $args;

        return [12, 24];
    }

    function do_action(string $hook, int $hintId): void
    {
        global $schedulerProcessCalls;
        $schedulerProcessCalls[] = [$hook, $hintId];
    }
}

namespace {
    use ChassesAuTresor\Core\Content\HintScheduler;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/HintQueryService.php';
    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/HintScheduler.php';

    class HintSchedulerTest extends TestCase
    {
        protected function setUp(): void
        {
            global $schedulerScheduledTimestamp, $schedulerScheduleCall, $schedulerUnscheduleCall;
            global $schedulerQueryArgs, $schedulerProcessCalls;
            $schedulerScheduledTimestamp = false;
            $schedulerScheduleCall = null;
            $schedulerUnscheduleCall = null;
            $schedulerQueryArgs = null;
            $schedulerProcessCalls = [];
        }

        public function testScheduleRegistersHourlyTaskWhenMissing(): void
        {
            global $schedulerScheduleCall;

            HintScheduler::schedule();

            $this->assertSame(
                ['timestamp' => 100, 'recurrence' => 'hourly', 'hook' => HintScheduler::HOOK],
                $schedulerScheduleCall
            );
        }

        public function testScheduleKeepsExistingTask(): void
        {
            global $schedulerScheduledTimestamp, $schedulerScheduleCall;
            $schedulerScheduledTimestamp = 50;

            HintScheduler::schedule();

            $this->assertNull($schedulerScheduleCall);
        }

        public function testUnscheduleRemovesExistingTask(): void
        {
            global $schedulerScheduledTimestamp, $schedulerUnscheduleCall;
            $schedulerScheduledTimestamp = 50;

            HintScheduler::unschedule();

            $this->assertSame(
                ['timestamp' => 50, 'hook' => HintScheduler::HOOK],
                $schedulerUnscheduleCall
            );
        }

        public function testUnscheduleIgnoresMissingTask(): void
        {
            global $schedulerUnscheduleCall;

            HintScheduler::unschedule();

            $this->assertNull($schedulerUnscheduleCall);
        }

        public function testRunProcessesEveryDueHint(): void
        {
            global $schedulerQueryArgs, $schedulerProcessCalls;

            HintScheduler::run();

            $this->assertSame('programme', $schedulerQueryArgs['meta_query'][0]['value']);
            $this->assertSame(
                [
                    [HintScheduler::PROCESS_HOOK, 12],
                    [HintScheduler::PROCESS_HOOK, 24],
                ],
                $schedulerProcessCalls
            );
        }
    }
}
