<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Schedule publication of a legacy PDF solution attached to a riddle.
 */
class RiddleSolutionFileScheduler {
    public const HOOK = 'publier_solution_enigme';

    public static function resolveTimestamp(
        string $mode,
        ?int $delayDays,
        ?string $publicationTime,
        int $currentTimestamp
    ): ?int {
        return (new RiddleSolutionFilePolicyService())->getPublicationTimestamp(
            $mode,
            $delayDays,
            $publicationTime,
            $currentTimestamp
        );
    }

    public static function schedule(int $riddleId): void {
        if (get_post_type($riddleId) !== 'enigme') {
            return;
        }

        $delay = get_field('enigme_solution_delai', $riddleId);
        $publicationTime = get_field('enigme_solution_heure', $riddleId);
        $timestamp = self::resolveTimestamp(
            (string) get_field('enigme_solution_mode', $riddleId),
            $delay === null ? null : (int) $delay,
            $publicationTime === null ? null : (string) $publicationTime,
            time()
        );
        if ($timestamp === null) {
            return;
        }

        wp_clear_scheduled_hook(self::HOOK, [$riddleId]);
        wp_schedule_single_event($timestamp, self::HOOK, [$riddleId]);
    }
}
