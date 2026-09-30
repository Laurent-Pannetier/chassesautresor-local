<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Own the lifecycle of the scheduled solution publication task.
 */
class SolutionScheduler {
    public const HOOK = 'basculer_solutions_programme';
    public const PROCESS_HOOK = 'cat_process_due_programmed_solution';

    public static function schedule(): void {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOOK);
        }
    }

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    public static function run(): void {
        $queryArgs = (new SolutionAvailabilityService())->getDueSolutionIdsQueryArgs(
            (string) current_time('mysql')
        );

        foreach (get_posts($queryArgs) as $solutionId) {
            do_action(self::PROCESS_HOOK, (int) $solutionId);
        }
    }
}
