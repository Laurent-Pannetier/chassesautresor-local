<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    function wp_next_scheduled(string $hook)
    {
        global $hintScheduledTimestamp;

        return $hintScheduledTimestamp;
    }

    function wp_schedule_event(int $timestamp, string $recurrence, string $hook): void
    {
        global $hintScheduleCall;
        $hintScheduleCall = compact('timestamp', 'recurrence', 'hook');
    }

    function wp_unschedule_event(int $timestamp, string $hook): void
    {
        global $hintUnscheduleCall;
        $hintUnscheduleCall = compact('timestamp', 'hook');
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
        global $hintQueryArgs;
        $hintQueryArgs = $args;

        return [12, 24];
    }

    function do_action(string $hook, int $hintId): void
    {
        global $hintProcessCalls;
        $hintProcessCalls[] = [$hook, $hintId];
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
            global $hintScheduledTimestamp, $hintScheduleCall, $hintUnscheduleCall;
            global $hintQueryArgs, $hintProcessCalls;
            $hintScheduledTimestamp = false;
            $hintScheduleCall = null;
            $hintUnscheduleCall = null;
            $hintQueryArgs = null;
            $hintProcessCalls = [];
        }

        public function testScheduleRegistersHourlyTaskWhenMissing(): void
        {
            global $hintScheduleCall;

            HintScheduler::schedule();

            $this->assertSame(
                ['timestamp' => 100, 'recurrence' => 'hourly', 'hook' => HintScheduler::HOOK],
                $hintScheduleCall
            );
        }

        public function testScheduleKeepsExistingTask(): void
        {
            global $hintScheduledTimestamp, $hintScheduleCall;
            $hintScheduledTimestamp = 50;

            HintScheduler::schedule();

            $this->assertNull($hintScheduleCall);
        }

        public function testUnscheduleRemovesExistingTask(): void
        {
            global $hintScheduledTimestamp, $hintUnscheduleCall;
            $hintScheduledTimestamp = 50;

            HintScheduler::unschedule();

            $this->assertSame(
                ['timestamp' => 50, 'hook' => HintScheduler::HOOK],
                $hintUnscheduleCall
            );
        }

        public function testUnscheduleIgnoresMissingTask(): void
        {
            global $hintUnscheduleCall;

            HintScheduler::unschedule();

            $this->assertNull($hintUnscheduleCall);
        }

        public function testRunProcessesEveryDueHint(): void
        {
            global $hintQueryArgs, $hintProcessCalls;

            HintScheduler::run();

            $this->assertSame('programme', $hintQueryArgs['meta_query'][0]['value']);
            $this->assertSame(
                [
                    [HintScheduler::PROCESS_HOOK, 12],
                    [HintScheduler::PROCESS_HOOK, 24],
                ],
                $hintProcessCalls
            );
        }
    }
}
