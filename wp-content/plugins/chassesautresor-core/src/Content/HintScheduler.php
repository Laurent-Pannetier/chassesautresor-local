<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Own the lifecycle of the scheduled hint availability task.
 */
class HintScheduler
{
    public const HOOK = 'basculer_indices_programmes';
    public const PROCESS_HOOK = 'cat_process_due_programmed_hint';

    public static function schedule(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOOK);
        }
    }

    public static function unschedule(): void
    {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    public static function run(): void
    {
        $queryArgs = (new HintQueryService())->getDueProgrammedHintIdsQueryArgs(
            (string) current_time('mysql')
        );

        if ($queryArgs === []) {
            return;
        }

        foreach (get_posts($queryArgs) as $hintId) {
            do_action(self::PROCESS_HOOK, (int) $hintId);
        }
    }
}
