<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    if (!function_exists(__NAMESPACE__ . '\\wp_next_scheduled')) {
        function wp_next_scheduled(string $hook) {
            global $schedulerScheduledTimestamp;

            return $schedulerScheduledTimestamp;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\wp_schedule_event')) {
        function wp_schedule_event(int $timestamp, string $recurrence, string $hook): void {
            global $schedulerScheduleCall;
            $schedulerScheduleCall = compact('timestamp', 'recurrence', 'hook');
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\wp_unschedule_event')) {
        function wp_unschedule_event(int $timestamp, string $hook): void {
            global $schedulerUnscheduleCall;
            $schedulerUnscheduleCall = compact('timestamp', 'hook');
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\time')) {
        function time(): int {
            return 100;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\current_time')) {
        function current_time(string $type): string {
            return '2026-09-30 12:00:00';
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\get_posts')) {
        function get_posts(array $args): array {
            global $schedulerQueryArgs;
            $schedulerQueryArgs = $args;

            return [12, 24];
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\do_action')) {
        function do_action(string $hook, int $solutionId): void {
            global $schedulerProcessCalls;
            $schedulerProcessCalls[] = [$hook, $solutionId];
        }
    }
}

namespace {
    use ChassesAuTresor\Core\Content\SolutionScheduler;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionAvailabilityService.php';
    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionScheduler.php';

    class SolutionSchedulerTest extends TestCase {
        protected function setUp(): void {
            global $schedulerScheduledTimestamp, $schedulerScheduleCall, $schedulerUnscheduleCall;
            global $schedulerQueryArgs, $schedulerProcessCalls;
            $schedulerScheduledTimestamp = false;
            $schedulerScheduleCall = null;
            $schedulerUnscheduleCall = null;
            $schedulerQueryArgs = null;
            $schedulerProcessCalls = [];
        }

        public function testScheduleRegistersHourlyTaskWhenMissing(): void {
            global $schedulerScheduleCall;

            SolutionScheduler::schedule();

            $this->assertSame(
                ['timestamp' => 100, 'recurrence' => 'hourly', 'hook' => SolutionScheduler::HOOK],
                $schedulerScheduleCall
            );
        }

        public function testScheduleKeepsExistingTask(): void {
            global $schedulerScheduledTimestamp, $schedulerScheduleCall;
            $schedulerScheduledTimestamp = 50;

            SolutionScheduler::schedule();

            $this->assertNull($schedulerScheduleCall);
        }

        public function testUnscheduleRemovesExistingTask(): void {
            global $schedulerScheduledTimestamp, $schedulerUnscheduleCall;
            $schedulerScheduledTimestamp = 50;

            SolutionScheduler::unschedule();

            $this->assertSame(
                ['timestamp' => 50, 'hook' => SolutionScheduler::HOOK],
                $schedulerUnscheduleCall
            );
        }

        public function testRunProcessesEveryDueSolution(): void {
            global $schedulerQueryArgs, $schedulerProcessCalls;

            SolutionScheduler::run();

            $this->assertSame('solution', $schedulerQueryArgs['post_type']);
            $this->assertSame(
                [
                    [SolutionScheduler::PROCESS_HOOK, 12],
                    [SolutionScheduler::PROCESS_HOOK, 24],
                ],
                $schedulerProcessCalls
            );
        }
    }
}
